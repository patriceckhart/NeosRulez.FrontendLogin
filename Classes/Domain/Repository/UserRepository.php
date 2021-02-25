<?php
namespace NeosRulez\FrontendLogin\Domain\Repository;

/*
 * This file is part of the NeosRulez.FrontendLogin package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class UserRepository extends Repository
{

    public function findByUsername($username) {
        $class = '\NeosRulez\FrontendLogin\Domain\Model\User';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching($query->equals('username', $username))->execute()->getFirst();
        return $result;
    }

}
