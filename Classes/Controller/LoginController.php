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
     * @param ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there was none
     * @return void
     */
    protected function onAuthenticationSuccess(ActionRequest $originalRequest = null)
    {
        $username = $this->loginRepository->getUsername();
        $user = $this->loginRepository->checkIfUserExist($username);
        $active = $user->getActive();

        if($active) {
            $redirectSuccessNode = $this->request->getArgument('redirectSuccess');
            $redirectSuccess = $this->getNodeUri($redirectSuccessNode);
            $this->redirectToUri($redirectSuccess);
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
        $this->redirect('login', 'user');
    }

}
