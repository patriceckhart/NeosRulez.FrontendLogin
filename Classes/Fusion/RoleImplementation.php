<?php
namespace NeosRulez\FrontendLogin\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class RoleImplementation extends AbstractFusionObject {

    /**
     * @var \Neos\Flow\Security\Context
     * @Flow\Inject
     */
    protected $securityContext;

    /**
     * @return boolean
     */
    public function evaluate() {
        $roles = $this->fusionValue('roles');
        $result = false;
        if(!empty($roles)) {
            foreach ($roles as $i => $value) {
                if($this->securityContext->hasRole($value)) {
                    $result = true;
                }
            }
        }
        return $result;
    }

}