<?php
namespace NeosRulez\FrontendLogin\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Flow\Persistence\PersistenceManagerInterface;

class UserGroupFusion extends AbstractFusionObject {

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
     * @return bool
     */
    public function evaluate():bool
    {
        $userGroups = $this->fusionValue('userGroups');
        $result = false;
        if(!empty($userGroups)) {
            if($this->userService->getCurrentUser()) {
                if($this->userRepository->findByUsername($this->userService->username())->getFirst()) {
                    $currentUserGroups = $this->userRepository->findByUsername($this->userService->username())->getFirst()->getUserGroups();
                    if(!empty($currentUserGroups)) {
                        foreach ($currentUserGroups as $currentUserGroup) {
                            $currentUserGroupIdentifier = $this->persistenceManager->getIdentifierByObject($currentUserGroup);
                            foreach ($userGroups as $userGroup) {
                                if($userGroup == $currentUserGroupIdentifier) {
                                    $result = true;
                                }
                            }
                        }
                    }
                } else {
                    $result = true;
                }
            }
        }
        return $result;
    }

}
