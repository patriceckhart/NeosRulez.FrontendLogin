<?php
namespace NeosRulez\FrontendLogin\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class UserFusion extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Service\UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Repository\UserRepository
     */
    protected $userRepository;


    /**
     * @return void
     */
    public function evaluate()
    {
        return $this->userRepository->findByUsername($this->userService->username())->getFirst();
    }

}
