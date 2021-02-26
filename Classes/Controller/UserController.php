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
     * @var \NeosRulez\FrontendLogin\Domain\Repository\LoginRepository
     */
    protected $loginRepository;

    /**
     * @var \Neos\Flow\Security\AccountRepository
     * @Flow\Inject
     */
    protected $accountRepository;

    /**
     * @var \Neos\Flow\Security\Authentication\AuthenticationManagerInterface
     * @Flow\Inject
     */
    protected $authenticationManager;

    /**
     * @var \Neos\Flow\Security\AccountFactory
     * @Flow\Inject
     */
    protected $accountFactory;

    /**
     * @var \Neos\Flow\Security\Account
     * @Flow\Inject
     */
    protected $account;

    /**
     * @var \Neos\Flow\Security\Context
     * @Flow\Inject
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var \Neos\Neos\Service\LinkingService
     */
    protected $linkingService;

    /**
     * @var \Neos\Flow\Security\Cryptography\HashService
     * @Flow\Inject
     */
    protected $hashService;

    /**
     * @Flow\Inject
     * @var \Neos\Neos\Domain\Service\UserService
     */
    protected $userService;

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
     * @return void
     */
    public function indexAction()
    {
        $users = $this->userRepository->findAll();
        $combined_users = [];
        foreach ($users as $user) {
            $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($user->getUsername(), "NeosRulez.FrontendLogin:NeosFrontend");
            $roles = $this->loginRepository->getRoles();
            $combined_roles = [];
            foreach ($roles as $role) {
                $checked = false;
                foreach ($account->getRoles() as $accountrole) {
                    if($accountrole==$role) {
                        $checked = true;
                    }
                }
                $combined_roles[] = ['role' => $role, 'checked' => $checked];
            }
            $combined_users[] = [$user, 'roles' => $combined_roles];
        }
        $this->view->assign('users', $combined_users);
    }

    /**
     * @return void
     */
    public function loginAction()
    {
        $this->view->assign('attributes',$this->request->getInternalArgument('__attributes'));
        $this->view->assign('allowreset',$this->settings['passwordReset']);
        $this->view->assign('redirectSuccess',$this->request->getInternalArgument('__redirectSuccess'));
        $this->view->assign('redirectSuccessNodeUri',$this->request->getInternalArgument('__redirectSuccessNodeUri'));
        $this->view->assign('inBackend',$this->request->getInternalArgument('__inBackend'));
        $this->view->assign('username',$this->loginRepository->getUsername());
    }

    /**
     * @return void
     */
    public function resetAction()
    {
        $this->view->assign('attributes',$this->request->getInternalArgument('__attributes'));
        $this->view->assign('subject',$this->request->getInternalArgument('__subject'));
    }

    /**
     * @return void
     */
    public function newAction()
    {
        $this->view->assign('roles',$this->loginRepository->getRoles());

        $countries = $this->settings['registration']['countries']['values'];
        $count = -1;
        foreach ($countries as $i => $country) {
            $count = $count+1;
            $countryitems[] = ['short' => $i, 'label' => $country['label']];
        }
        $this->view->assign('countries',$countryitems);
        $this->view->assign('defaultcountry',$this->settings['registration']['formfields']['country']['default']);
        $this->view->assign('formfields',$this->settings['registration']['formfields']);
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return void
     */
    public function editAction($user)
    {
        $this->newAction();
        $this->view->assign('user',$user);
        $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($user->getUsername(), "NeosRulez.FrontendLogin:NeosFrontend");
        $roles = $this->loginRepository->getRoles();
        $combined_roles = [];
        foreach ($roles as $role) {
            $checked = false;
            foreach ($account->getRoles() as $accountrole) {
                if($accountrole==$role) {
                    $checked = true;
                }
            }
            $combined_roles[] = ['role' => $role, 'checked' => $checked];
        }
        $this->view->assign('combinedroles', $combined_roles);
        $this->view->assign('account', $account);
        $this->view->assign('edit',1);
    }

    /**
     * @return void
     */
    public function setnewpasswordAction() {

        $args = $this->request->getArguments();

        if($args['username']) {
            $result = $this->userRepository->checkIfUserExist($args['username']);

            if($result) {
                $email = $result->getEmail();
                $password = $pwd = bin2hex(openssl_random_pseudo_bytes(8));

                $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($email, "NeosRulez.FrontendLogin:NeosFrontend");
                $account->setCredentialsSource($this->hashService->hashPassword($password));
                $this->accountRepository->update($account);

                $props['username'] = $result->getUsername();
                $props['password'] = $password;

                $subject = $args['subject'];
                $view = new \Neos\FluidAdaptor\View\StandaloneView();
                $view->setTemplatePathAndFilename($this->settings['mail']['templates']['newpassword']);
                $view->assignMultiple(['props' => $props]);

                $mail = new \Neos\SwiftMailer\Message();
                $mail
                    ->setFrom($this->settings['adminMail'])
                    ->setTo(array($result->getEmail() => $result->getFirstname().' '.$result->getLastname()))
                    ->setSubject($subject);
                $mail->setBody($view->render(), 'text/html');
                $mail->send();

                $this->addFlashMessage('Ihr Passwort wurde zurück gesetzt und per E-Mail versandt.');

            } else {
                $this->addFlashMessage('Es existiert bereits kein Benutzer mit dieser E-Mail Adresse.', 'Fehler!', \Neos\Error\Messages\Message::SEVERITY_ERROR);
            }
        }

        $this->redirect('user','user');

    }

    /**
     * @return void
     * @Flow\SkipCsrfProtection
     */
    public function registrationAction()
    {
        $this->view->assign('attributes',$this->request->getInternalArgument('__attributes'));
        $countries = $this->settings['registration']['countries']['values'];
        $count = -1;
        foreach ($countries as $i => $country) {
            $count = $count+1;
            $countryitems[] = ['short' => $i, 'label' => $country['label']];
        }
        $this->view->assign('countries',$countryitems);
        $this->view->assign('defaultcountry',$this->settings['registration']['formfields']['country']['default']);
        $this->view->assign('formfields',$this->settings['registration']['formfields']);
        $this->view->assign('subject',$this->request->getInternalArgument('__subject'));
        $this->view->assign('redirectSuccess',$this->request->getInternalArgument('__redirectSuccess'));
        $this->view->assign('redirectSuccessNodeUri',$this->request->getInternalArgument('__redirectSuccessNodeUri'));
        $this->view->assign('inBackend',$this->request->getInternalArgument('__inBackend'));
    }

    /**
     * @return void
     */
    public function createAction()
    {
        $args = $this->request->getArguments();
        $args['email'] = strtolower($args['email']);

        $fe = false;
        $action = 'index';

        if (array_key_exists('subject', $args)) {
            $fe = true;
            $action = 'registration';
        }

        if (array_key_exists('user', $args)) {
            $args = $args['user'];
        }

        if($this->loginRepository->checkIfUserExist($args['email'])) {
            $this->addFlashMessage('Es existiert bereits ein Benutzer mit diesem Benutzernamen.', 'Fehler!', \Neos\Error\Messages\Message::SEVERITY_ERROR);
        } else {
            if($args['password']==$args['password2']) {
                if (array_key_exists('role', $args)) {
                    $defaultRole = $args['role'];
                } else {
                    $defaultRole[] = $this->settings['registration']['defaultRole'];
                }
                $authenticationProviderName = 'NeosRulez.FrontendLogin:NeosFrontend';

                $user = new \NeosRulez\FrontendLogin\Domain\Model\User();

                if($args['salutation']) {
                    $user->setSalutation($args['salutation']);
                }
                if($args['company']) {
                    $user->setCompany($args['company']);
                }
                if($args['firstname']) {
                    $user->setFirstname($args['firstname']);
                }
                if($args['lastname']) {
                    $user->setLastname($args['lastname']);
                }
                if($args['address']) {
                    $user->setAddress($args['address']);
                }
                if($args['zip']) {
                    $user->setZip($args['zip']);
                }
                if($args['city']) {
                    $user->setCity($args['city']);
                }
                if($args['country']) {
                    $user->setCountry($args['country']);
                }
                if($args['phone']) {
                    $user->setPhone($args['phone']);
                }
                if($args['email']) {
                    $user->setEmail($args['email']);
                }
                $user->setUsername($args['email']);

                $user->setActive($this->settings['registration']['autoActive']);

                $this->userRepository->add($user);

                $this->userService->createUser($args['email'], $args['password'], $args['firstname'], $args['lastname'], $defaultRole, $authenticationProviderName);

                if(isset($args['subject'])) {
                    $subject = $args['subject'];
                    $view = new \Neos\FluidAdaptor\View\StandaloneView();
                    $view->setTemplatePathAndFilename($this->settings['mail']['templates']['registration']);
                    $view->assignMultiple(['props' => $args]);

                    $mail = new \Neos\SwiftMailer\Message();
                    $mail
                        ->setFrom($this->settings['adminMail'])
                        ->setTo(array($args['email'] => $args['firstname'].' '.$args['lastname']))
                        ->setSubject($subject);
                    $mail->setBody($view->render(), 'text/html');
                    $mail->send();

                    unset($args['subject']);
                    unset($args['password']);
                    unset($args['password2']);
                }

                if(isset($args['subject'])) {
                    $this->addFlashMessage('Ihr Account wurde erstellt.');
                }

            }
        }

        if(isset($args['redirectSuccess'])) {
//            $redirectSuccess = $this->getNodeUri($args['redirectSuccess']);
            $redirectSuccess = $args['redirectSuccess'];
            $this->redirectToUri($redirectSuccess);
        } else {
            $this->redirect($action,'user');
        }

    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return void
     */
    public function updateAction($user) {
        $this->userRepository->update($user);
        $this->redirect('index','user');
    }

    /**
     * @param string $email
     * @return void
     */
    public function updaterolesAction($email) {
        $args = $this->request->getArguments();
        $roles = [];
        foreach ($args['login']['role'] as $role) {
            $roles[] = new \Neos\Flow\Security\Policy\Role($role);
        }
        $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($email, "NeosRulez.FrontendLogin:NeosFrontend");
        $account->setRoles($roles);
        $this->accountRepository->update($account);
        $this->redirect('index','user');
    }

    /**
     * @return void
     */
    public function updatepasswordAction() {
        $args = $this->request->getArguments();
        if($args['password']==$args['password2']) {
            $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($args['email'], "NeosRulez.FrontendLogin:NeosFrontend");
            $account->setCredentialsSource($this->hashService->hashPassword($args['password']));
            $this->accountRepository->update($account);
        } else {
            $this->addFlashMessage('Passwörter sind nicht identisch', 'Fehler!', \Neos\Error\Messages\Message::SEVERITY_ERROR);
        }

        $this->redirect('index','user');
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\User $user
     * @return void
     */
    public function changeAction($user) {
        $status = $user->getActive();
        if($status==1) {
            $user->setActive(0);
        } else {
            $user->setActive(1);
        }
        $this->userRepository->update($user);
        $this->persistenceManager->persistAll();
        $this->redirect('index','user');
    }

    /**
     * @return string
     */
    public function getNodeUri($node) {
        $url = $this->linkingService->createNodeUri(
            $this->getControllerContext(),
            $node,
            null,
            'html',
            'false',
            [],
            '',
            false,
            []
        );
        return $url;
    }

}
