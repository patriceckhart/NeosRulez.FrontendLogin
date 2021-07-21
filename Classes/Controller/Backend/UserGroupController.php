<?php
namespace NeosRulez\FrontendLogin\Controller\Backend;

/*
 * This file is part of the NeosRulez.FrontendLogin package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;

class UserGroupController extends ActionController
{

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Repository\UserGroupRepository
     */
    protected $userGroupRepository;


    /**
     * @return void
     */
    public function indexAction():void
    {
        $userGroups = $this->userGroupRepository->findAll();
        $this->view->assign('userGroups', $userGroups);
    }

    /**
     * @return void
     */
    public function newAction():void
    {

    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\UserGroup $userGroup
     * @return void
     */
    public function createAction(\NeosRulez\FrontendLogin\Domain\Model\UserGroup $userGroup):void
    {
        $this->userGroupRepository->add($userGroup);
        $this->redirect('index');
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\UserGroup $userGroup
     * @return void
     */
    public function editAction(\NeosRulez\FrontendLogin\Domain\Model\UserGroup $userGroup):void
    {
        $this->view->assign('userGroup', $userGroup);
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\UserGroup $userGroup
     * @return void
     */
    public function updateAction(\NeosRulez\FrontendLogin\Domain\Model\UserGroup $userGroup):void
    {
        $this->userGroupRepository->update($userGroup);
        $this->redirect('index');
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\UserGroup $userGroup
     * @return void
     */
    public function changeAction(\NeosRulez\FrontendLogin\Domain\Model\UserGroup $userGroup):void
    {
        if($userGroup->getActive()) {
            $userGroup->setActive(false);
        } else {
            $userGroup->setActive(true);
        }
        $this->userGroupRepository->update($userGroup);
        $this->persistenceManager->persistAll();
        $this->redirect('index');
    }

    /**
     * @param \NeosRulez\FrontendLogin\Domain\Model\UserGroup $userGroup
     * @return void
     */
    public function deleteAction(\NeosRulez\FrontendLogin\Domain\Model\UserGroup $userGroup):void
    {
        $this->userGroupRepository->remove($userGroup);
        $this->persistenceManager->persistAll();
        $this->redirect('index');
    }

}
