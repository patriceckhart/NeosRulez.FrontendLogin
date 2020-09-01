<?php

namespace NeosRulez\FrontendLogin\Domain\Service;

class UserService extends \Neos\Neos\Domain\Service\UserService {

    /**
     * @return User The currently logged in user, or null
     * @api
     */
    public function getCurrentUser() {
        if ($this->securityContext->canBeInitialized() === false) {
            return null;
        }
        $tokens = $this->securityContext->getAuthenticationTokens();
        foreach ($tokens as $token) {
            $account = $token->getAccount();
            if ($account === null) {
                continue;
            }
            $user = $this->partyService->getAssignedPartyOfAccount($account);
            if ($user !== null) {
                return $user;
            }
        }
        return null;
    }


}
