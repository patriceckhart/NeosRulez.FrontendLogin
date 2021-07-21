<?php
namespace NeosRulez\FrontendLogin\DataSource;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Security\Policy\PolicyService;

class RoleDataSource extends AbstractDataSource {

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @var string
     */
    protected static $identifier = 'neosrulez-frontendlogin-roles';

    /**
     * @param NodeInterface $node The node that is currently edited (optional)
     * @param array $arguments Additional arguments (key / value)
     * @return array
     */
    public function getData(NodeInterface $node = NULL, array $arguments = Array())
    {
        $options = [];
        $roles = $this->policyService->getRoles();
        foreach ($roles as $i => $value) {
            if (strpos($i, 'Neos.') !== false) {
            } else {
                $options[] = ['label' => $i, 'value' => $i];
            }
        }
        return $options;

    }

}
