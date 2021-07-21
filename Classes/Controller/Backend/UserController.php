<?php
namespace NeosRulez\FrontendLogin\Controller\Backend;

/*
 * This file is part of the NeosRulez.FrontendLogin package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;

class UserController extends ActionController
{

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Repository\UserRepository
     */
    protected $userRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Repository\UserGroupRepository
     */
    protected $userGroupRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Service\UserService
     */
    protected $userService;

    /**
     * @var \Neos\Flow\Security\AccountRepository
     * @Flow\Inject
     */
    protected $accountRepository;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Translator
     */
    protected $translator;


    /**
     * @return void
     */
    public function indexAction():void
    {
        $users = $this->userRepository->findAll();
        if(!empty($users)) {
            foreach ($users as $user) {
                $roles = $this->userService->getAllRoles($this->userService->getUser($user->getUsername(), 'NeosRulez.FrontendLogin:NeosFrontend'));
                $frontendRoles = [];
                if(!empty($roles)) {
                    foreach ($roles as $i => $role) {
                        if (strpos($i, 'Neos.') !== false) {
                        } else {
                            $frontendRoles[] = $role;
                        }
                    }
                }
                $user->roles = $frontendRoles;
            }
        }
        $this->view->assign('users', $users);
    }

    /**
     * @return void
     */
    public function newAction():void
    {
        $this->view->assign('roles', $this->userService->getRoles());
        $this->view->assign('userGroups', $this->userGroupRepository->findByActive(true));
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return void
     */
    public function createAction(\NeosRulez\FrontendLogin\Domain\Model\User $user):void
    {
        if(count($this->userRepository->findByUsername($user->getEmail())) > 0) {
            $this->addFlashMessage($this->translator->translateById('failure.userexist', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), $this->translator->translateById('failure.title', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), \Neos\Error\Messages\Message::SEVERITY_ERROR);
        } else {
            $password = $this->userService->generatePassword();
            if($this->request->hasArgument('password1') && $this->request->hasArgument('password2')) {
                $password1 = $this->request->getArgument('password1');
                $password2 = $this->request->getArgument('password2');
                if($password1 != $password2) {
                    $this->addFlashMessage($this->translator->translateById('failure.password', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), $this->translator->translateById('failure.title', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), \Neos\Error\Messages\Message::SEVERITY_ERROR);
                    $this->redirect('index');
                } else {
                    $password = $password1;
                }
            }
            $username = $user->getEmail();
            if($this->request->hasArgument('username')) {
                $username = $this->request->getArgument('username');
            }
            $roles = [$this->settings['registration']['defaultRole']];
            if($this->request->hasArgument('roles')) {
                $roles = $this->request->getArgument('roles');
            }
            $neosUser = $this->userService->createUser($username, $password, $user->getFirstname(), $user->getLastname(), $roles, 'NeosRulez.FrontendLogin:NeosFrontend');
            $user->setAccount($neosUser->getAccounts()[0]);
            $user->setUsername($username);
            $user->setActive($this->settings['registration']['autoActive']);
            $this->userRepository->add($user);
            $this->addFlashMessage($this->translator->translateById('success.usercreated.backend', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin') . ' ' . $user->getUsername());
        }
        $this->redirect('index');
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return void
     */
    public function editAction(\NeosRulez\FrontendLogin\Domain\Model\User $user):void
    {
        $user->roles = $this->userService->getAllRoles($this->userService->getUser($user->getUsername(), 'NeosRulez.FrontendLogin:NeosFrontend'));
        $this->view->assign('user', $user);

        $this->view->assign('username', $user->getUsername());
        $this->view->assign('fakepassword', '3858f62230ac3c915f300c664312c63f');

        $roles = $this->userService->getRoles();
        $userRoles = [];
        if(!empty($roles)) {
            foreach ($roles as $i => $role) {
                $userRoles[$i]['name'] = $role;
                if(in_array($role, $user->roles)) {
                    $userRoles[$i]['checked'] = true;
                } else {
                    $userRoles[$i]['checked'] = false;
                }
            }
        }
        $this->view->assign('roles', $userRoles);

        $userGroupsByUser = $user->getUsergroups();

        $userGroups = $this->userGroupRepository->findByActive(true);
        $groups = $userGroups;
        if(!empty($userGroupsByUser)) {
            $groups = [];
        }
        if(!empty($userGroups)) {
            foreach ($userGroups as $i => $userGroup) {
                $userGroupIdentifier = $this->persistenceManager->getIdentifierByObject($userGroup);
                $groups[$i] = [
                    'identifier' => $userGroupIdentifier,
                    'name' => $userGroup->getName(),
                ];
                if(!empty($userGroupsByUser)) {
                    foreach ($userGroupsByUser as $userGroupByUser) {
                        $userGroupByUserIdentifier = $this->persistenceManager->getIdentifierByObject($userGroupByUser);
                        if($userGroupByUserIdentifier == $userGroupIdentifier) {
                            $groups[$i]['checked'] = true;
                        }
                    }
                }
            }
        }
        $this->view->assign('userGroups', $groups);
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return void
     */
    public function updateAction(\NeosRulez\FrontendLogin\Domain\Model\User $user):void
    {
        $newUsername = $this->request->getArgument('newusername');
        $password1 = $this->request->getArgument('password1');
        $password2 = $this->request->getArgument('password2');

        if($user->getUsername() != $newUsername) {
            if(count($this->userRepository->findByUsername($newUsername)) > 0) {
                $this->addFlashMessage($this->translator->translateById('failure.userexist', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), $this->translator->translateById('failure.title', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), \Neos\Error\Messages\Message::SEVERITY_ERROR);
                $this->redirect('index');
            }
        }

        if($password1 != '3858f62230ac3c915f300c664312c63f' && $password2 != '3858f62230ac3c915f300c664312c63f') {
            if($password1 == $password2) {
                $this->userService->setUserPassword($this->userService->getUser($user->getUsername(), 'NeosRulez.FrontendLogin:NeosFrontend'), $password1);
            } else {
                $this->addFlashMessage($this->translator->translateById('failure.password', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), $this->translator->translateById('failure.title', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.FrontendLogin'), \Neos\Error\Messages\Message::SEVERITY_ERROR);
                $this->redirect('index');
            }
        }

        $roles = $this->userService->getRoles();
        if(!empty($roles)) {
            foreach ($roles as $role) {
                $this->userService->removeRoleFromUser($this->userService->getUser($user->getUsername(), 'NeosRulez.FrontendLogin:NeosFrontend'), $role);
            }
        }

        $userRoles = $this->request->getArgument('roles');
        if(!empty($userRoles)) {
            foreach ($userRoles as $userRole) {
                $this->userService->addRoleToUser($this->userService->getUser($user->getUsername(), 'NeosRulez.FrontendLogin:NeosFrontend'), $userRole);
            }
        }

        $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($user->getUsername(), 'NeosRulez.FrontendLogin:NeosFrontend');
        $this->persistenceManager->whitelistObject($account);
        $account->setAccountIdentifier($newUsername);
        $this->persistenceManager->update($account);

        $user->setUsername($newUsername);

        $this->userRepository->update($user);
        $this->persistenceManager->persistAll();
        $this->redirect('index');
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return void
     */
    public function changeAction(\NeosRulez\FrontendLogin\Domain\Model\User $user):void
    {
        if($user->getActive()) {
            $user->setActive(0);
            $this->userService->deactivateUser($this->userService->getUser($user->getUsername(), 'NeosRulez.FrontendLogin:NeosFrontend'));
        } else {
            $user->setActive(1);
            $this->userService->activateUser($this->userService->getUser($user->getUsername(), 'NeosRulez.FrontendLogin:NeosFrontend'));
        }
        $this->userRepository->update($user);
        $this->persistenceManager->persistAll();
        $this->redirect('index');
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return void
     */
    public function deleteAction(\NeosRulez\FrontendLogin\Domain\Model\User $user):void
    {
        $this->userService->deleteUser($this->userService->getUser($user->getUsername(), 'NeosRulez.FrontendLogin:NeosFrontend'));
        $this->userRepository->remove($user);
        $this->persistenceManager->persistAll();
        $this->redirect('index');
    }

}
