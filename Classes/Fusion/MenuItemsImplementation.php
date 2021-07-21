<?php
namespace NeosRulez\FrontendLogin\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\Exception as FusionException;
use Neos\Fusion\Exception;
use Neos\Flow\Persistence\PersistenceManagerInterface;

/**
 * A Fusion MenuItems object
 */
class MenuItemsImplementation extends \Neos\Neos\Fusion\MenuItemsImplementation
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
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


    /**
     * Builds the array of menu items containing those items which match the
     * configuration set for this Menu object.
     *
     * @throws FusionException
     * @return array An array of menu items and further information
     */
    protected function buildItems()
    {
        $items = [];

        if ($this->getItemCollection() !== null) {
            $menuLevelCollection = $this->getItemCollection();
        } else {
            $entryParentNode = $this->findMenuStartingPoint();
            if ($entryParentNode === null) {
                return $items;
            }
            $menuLevelCollection = $entryParentNode->getChildNodes($this->getFilter());
        }

        $items = $this->buildMenuLevelRecursive($menuLevelCollection);

        $filteredItems = [];

        $loggedInUserRoles = [];
        $loggedInUserUserGroups = [];
        $isBackend = false;
        if($this->userService->getCurrentUser()) {
            $loggedInUserRoles = $this->userService->getAllRoles($this->userService->getCurrentUser());
            if($this->userRepository->findByUsername($this->userService->username())->getFirst()) {
                $currentUserGroups = $this->userRepository->findByUsername($this->userService->username())->getFirst()->getUserGroups();
                if(!empty($currentUserGroups)) {
                    foreach ($currentUserGroups as $currentUserGroup) {
                        $loggedInUserUserGroups[] = $this->persistenceManager->getIdentifierByObject($currentUserGroup);
                    }
                }
            } else {
                $isBackend = true;
            }
        }

        foreach ($items as $item) {
            if(!$item['node']->hasProperty('roles')) {
                if(!$item['node']->hasProperty('userGroups')) {
                    $filteredItems[] = $item;
                }
            }
            if($item['node']->hasProperty('roles')) {
                $nodeRoles = $item['node']->getProperty('roles');
                if(!empty($nodeRoles)) {
                    foreach ($nodeRoles as $nodeRole) {
                        if(in_array($nodeRole, $loggedInUserRoles)) {
                            $filteredItems[] = $item;
                        }
                    }
                }
            }
            if($item['node']->hasProperty('userGroups')) {
                $userGroups = $item['node']->getProperty('userGroups');
                if(!empty($userGroups)) {
                    foreach ($userGroups as $userGroup) {
                        if(in_array($userGroup, $loggedInUserUserGroups)) {
                            $filteredItems[] = $item;
                        }
                        if($isBackend) {
                            $filteredItems[] = $item;
                        }
                    }
                } else {
                    $filteredItems[] = $item;
                }
            }
        }

        return $filteredItems;
    }

}
