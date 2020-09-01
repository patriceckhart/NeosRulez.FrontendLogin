<?php
namespace NeosRulez\FrontendLogin\Domain\Repository;

/*
 * This file is part of the NeosRulez.FrontendLogin package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use Neos\Flow\Security\Policy\PolicyService;

/**
 * @Flow\Scope("singleton")
 */
class LoginRepository extends Repository
{

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    public function checkIfUserExist($username) {
        $class = '\NeosRulez\FrontendLogin\Domain\Model\Login';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching($query->equals('username', $username))->execute()->getFirst();
        return $result;
    }

    public function getRoles() {
        $roles = $this->policyService->getRoles();
        foreach ($roles as $i => $value) {
            if (strpos($i, 'Neos.') !== false) {

            } else {
                $roleList[] = $i;
            }
        }
        return $roleList;
    }

}
