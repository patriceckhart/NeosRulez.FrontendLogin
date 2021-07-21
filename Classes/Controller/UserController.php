<?php
namespace NeosRulez\FrontendLogin\Controller;

/*
 * This file is part of the NeosRulez.FrontendLogin package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;

class UserController extends ActionController
{

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Repository\UserRepository
     */
    protected $userRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Service\UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Service\MailService
     */
    protected $mailService;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Translator
     */
    protected $translator;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings) {
        $this->settings = $settings;
    }


    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return bool
     */
    public function createAction(\NeosRulez\FrontendLogin\Domain\Model\User $user):bool
    {
        if(count($this->userRepository->findByUsername($user->getEmail())) > 0) {
            $this->addFlashMessage($this->translator->translateById('failure.userexist', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), $this->translator->translateById('failure.title', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), \Neos\Error\Messages\Message::SEVERITY_ERROR);
        } else {
            $password = $this->userService->generatePassword();
            $neosUser = $this->userService->createUser($user->getEmail(), $password, $user->getFirstname(), $user->getLastname(), [$this->settings['registration']['defaultRole']], 'NeosRulez.FrontendLogin:NeosFrontend');
            $user->setAccount($neosUser->getAccounts()[0]);
            $user->setUsername($user->getEmail());
            $user->setActive($this->settings['registration']['autoActive']);
            $this->userRepository->add($user);
            $this->mailService->sendMail(['username' => $user->getEmail(), 'password' => $password], $this->settings['adminMail'], $user->getEmail(), 'Registration', $this->settings['mail']['templates']['registration']);
            $this->addFlashMessage($this->translator->translateById('success.usercreated', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin') . ' ' .  $user->getEmail());
        }
        return true;
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return bool
     */
    public function updateAction(\NeosRulez\FrontendLogin\Domain\Model\User $user):bool
    {
        $this->userRepository->update($user);
        $this->addFlashMessage($this->translator->translateById('success.userupdated', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'));
        return true;
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return string
     */
    public function resetAction(\NeosRulez\FrontendLogin\Domain\Model\User $user):string
    {
        $password = $this->userService->generatePassword();
        $this->userService->setUserPassword($this->userService->getUser($user->getUsername(), 'NeosRulez.FrontendLogin:NeosFrontend'), $password);
        $this->mailService->sendMail(['username' => $user->getUsername(), 'password' => $password], $this->settings['adminMail'], $this->userRepository->findByUsername($user->getUsername())->getFirst()->getEmail(), 'New password', $this->settings['mail']['templates']['resetPassword']);
        $this->addFlashMessage($this->translator->translateById('success.passwordsent', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'));
        return $password;
    }

    /**
     * @param string $password1
     * @param string $password2
     * @return bool
     */
    public function updatePasswordAction(string $password1, string $password2):bool
    {
        if($password1 == $password2) {
            $this->userService->setUserPassword($this->userService->getUser($this->userService->username(), 'NeosRulez.FrontendLogin:NeosFrontend'), $password1);
        } else {
            $this->addFlashMessage($this->translator->translateById('failure.password', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), $this->translator->translateById('failure.title', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), \Neos\Error\Messages\Message::SEVERITY_ERROR);
        }
        return true;
    }

}
