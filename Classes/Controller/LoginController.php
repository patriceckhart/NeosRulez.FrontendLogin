<?php
namespace NeosRulez\FrontendLogin\Controller;

/*
 * This file is part of the NeosRulez.FrontendLogin package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\Controller\AbstractAuthenticationController;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Security\RequestPatternInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;


class LoginController extends AbstractAuthenticationController
{

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Repository\LoginRepository
     */
    protected $loginRepository;

    /**
     * @var \Neos\Flow\Security\Authentication\AuthenticationManagerInterface
     * @Flow\Inject
     */
    protected $authenticationManager;

    /**
     * @var \Neos\Flow\Security\AccountRepository
     * @Flow\Inject
     */
    protected $accountRepository;

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
     * @var \Neos\Flow\Security\Cryptography\HashService
     * @Flow\Inject
     */
    protected $hashService;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Translator
     */
    protected $translator;

    /**
     * @var \Neos\Flow\Security\Context
     * @Flow\Inject
     */
    protected $securityContext;

    /**
     * @var string
     * @Flow\Identity
     * @Flow\Validate(type="NotEmpty")
     */
    protected $accountIdentifier;

    /**
     * @Flow\Inject
     * @var \Neos\Neos\Service\LinkingService
     */
    protected $linkingService;

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
        $this->view->assign('logins',$this->loginRepository->findAll());
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
     * @param \NeosRulez\FrontendLogin\Domain\Model\Login $login
     * @return void
     */
    public function editAction($login)
    {
        $this->newAction();
        $this->view->assign('login',$login);
        $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($login->getUsername(), "NeosRulez.FrontendLogin:Frontend");
        foreach ($account->getRoles() as $role) {
            $this->view->assign('role',$role);
        }
        $this->view->assign('edit',1);
    }

    /**
     * @return void
     */
    public function loginAction()
    {
        $this->view->assign('attributes',$this->request->getInternalArgument('__attributes'));
        $this->view->assign('allowreset',$this->settings['passwordReset']);

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
    public function setnewpasswordAction() {

        $args = $this->request->getArguments();

        if($args['username']) {
            $result = $this->loginRepository->checkIfUserExist($args['username']);

            if($result) {
                $email = $result->getEmail();
                $password = $pwd = bin2hex(openssl_random_pseudo_bytes(8));

                $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($email, "NeosRulez.FrontendLogin:Frontend");
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

        $this->redirect('login','login');

    }

    /**
     * @return void
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
    }

    /**
     * @return void
     */
    public function createAction()
    {
        $args = $this->request->getArguments();

        $fe = false;

        if (array_key_exists('login', $args)) {
            $args = $args['login'];
            $fe = true;
        }

        if($this->loginRepository->checkIfUserExist($args['email'])) {
            $this->addFlashMessage('Es existiert bereits ein Benutzer mit diesem Benutzernamen.', 'Fehler!', \Neos\Error\Messages\Message::SEVERITY_ERROR);
        } else {
            if($args['password']==$args['password2']) {
                $defaultRole = $this->settings['registration']['defaultRole'];
                $authenticationProviderName = 'NeosRulez.FrontendLogin:Frontend';
                $account = $this->accountFactory->createAccountWithPassword($args['email'], $args['password'], array($defaultRole), $authenticationProviderName);
                $account->setCredentialsSource($this->hashService->hashPassword($args['password']));
                $this->accountRepository->add($account);


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

                $login = new \NeosRulez\FrontendLogin\Domain\Model\Login();

                if($args['salutation']) {
                    $login->setSalutation($args['salutation']);
                }
                if($args['company']) {
                    $login->setCompany($args['company']);
                }
                if($args['firstname']) {
                    $login->setFirstname($args['firstname']);
                }
                if($args['lastname']) {
                    $login->setLastname($args['lastname']);
                }
                if($args['address']) {
                    $login->setAddress($args['address']);
                }
                if($args['zip']) {
                    $login->setZip($args['zip']);
                }
                if($args['city']) {
                    $login->setCity($args['city']);
                }
                if($args['country']) {
                    $login->setCountry($args['country']);
                }
                if($args['phone']) {
                    $login->setPhone($args['phone']);
                }
                if($args['email']) {
                    $login->setEmail($args['email']);
                }
                $login->setUsername($args['email']);

                $login->setActive($this->settings['registration']['autoActive']);

                $this->loginRepository->add($login);

                if(isset($args['subject'])) {
                    $this->addFlashMessage('Ihr Account wurde erstellt.');
                }
            } else {
                $this->redirect('index','login');
            }
        }
        if($fe) {
            $this->redirectToUri('/');
        } else {
            $this->redirect('index','login');
        }

    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\Login $login
     * @return void
     */
    public function updateAction($login) {
        $this->loginRepository->update($login);
        $this->redirect('index','login');
    }

    /**
     * @return void
     */
    public function updatepasswordAction() {
        $args = $this->request->getArguments();
        if($args['password']==$args['password2']) {
            $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($args['email'], "NeosRulez.FrontendLogin:Frontend");
            $account->setCredentialsSource($this->hashService->hashPassword($args['password']));
            $this->accountRepository->update($account);
        } else {
            $this->addFlashMessage('Passwörter sind nicht identisch', 'Fehler!', \Neos\Error\Messages\Message::SEVERITY_ERROR);
        }

        $this->redirect('index','login');
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\Login $login
     * @return void
     */
    public function changeAction($login) {
        $status = $login->getActive();
        if($status==1) {
            $login->setActive(0);
        } else {
            $login->setActive(1);
        }
        $this->loginRepository->update($login);
        $this->persistenceManager->persistAll();
        $this->redirect('index','login');
    }

    /**
     * Is called if authentication was successful. If there has been an
     * intercepted request due to security restrictions, you might want to use
     * something like the following code to restart the originally intercepted
     * request:
     *
     * if ($originalRequest !== NULL) {
     *     $this->redirectToRequest($originalRequest);
     * }
     * $this->redirect('someDefaultActionAfterLogin');
     *
     * @param ActionRequest $originalRequest
     * @return string
     */
    protected function onAuthenticationSuccess(ActionRequest $originalRequest = null) {

        $redirectSuccess = $this->request->getInternalArgument('__redirectSuccess');

        if($redirectSuccess) {
            $uri = $this->getNodeUri($redirectSuccess);
        } else {
            $uri = '/';
        }

        $username = $this->loginRepository->getUsername();
        $user = $this->loginRepository->checkIfUserExist($username);
        $active = $user->getActive();

        if($active) {
            $this->redirectToUri($uri);
        } else {
            $this->redirect('logout');
        }

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

    /**
     * Is called if authentication failed.
     *
     * Override this method in your login controller to take any
     * custom action for this event. Most likely you would want
     * to redirect to some action showing the login form again.
     *
     * @param \Neos\Flow\Security\Exception\AuthenticationRequiredException $exception The exception thrown while the authentication process
     * @return void
     */
    protected function onAuthenticationFailure(\Neos\Flow\Security\Exception\AuthenticationRequiredException $exception = null)
    {

    }

    /**
     * return void
     */
    public function logoutAction()
    {
        parent::logoutAction();
        $this->redirect('login', 'login');
    }

}
