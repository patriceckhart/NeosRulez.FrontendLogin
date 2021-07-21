<?php
namespace NeosRulez\FrontendLogin\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class RoleFusion extends AbstractFusionObject {

    /**
     * @var \Neos\Flow\Security\Context
     * @Flow\Inject
     */
    protected $securityContext;

    /**
     * @return bool
     */
    public function evaluate():bool
    {
        $roles = $this->fusionValue('roles');
        $result = false;
        if(!empty($roles)) {
            foreach ($roles as $value) {
                if($this->securityContext->hasRole($value)) {
                    $result = true;
                }
            }
        }
        return $result;
    }

}
