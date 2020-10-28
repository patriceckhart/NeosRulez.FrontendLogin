<?php
namespace NeosRulez\FrontendLogin\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class ForbiddenRedirectImplementation extends AbstractFusionObject {

    /**
     * @return void
     */
    public function evaluate() {
        header('HTTP/1.0 403 Forbidden');
        exit;
    }

}