<?php
namespace NeosRulez\FrontendLogin\DataSource;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\ContentRepository\Domain\Model\NodeInterface;

class GroupDataSource extends AbstractDataSource {

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var \NeosRulez\FrontendLogin\Domain\Repository\UserGroupRepository
     */
    protected $userGroupRepository;

    /**
     * @var string
     */
    protected static $identifier = 'neosrulez-frontendlogin-usergroups';

    /**
     * @param NodeInterface $node The node that is currently edited (optional)
     * @param array $arguments Additional arguments (key / value)
     * @return array
     */
    public function getData(NodeInterface $node = NULL, array $arguments = Array())
    {
        $options = [];
        $userGroups = $this->userGroupRepository->findAll();
        if(!empty($userGroups)) {
            foreach ($userGroups as $userGroup) {
                $options[] = [
                    'label' => $userGroup->getName(),
                    'value' => $this->persistenceManager->getIdentifierByObject($userGroup)
                ];
            }
        }
        return $options;

    }

}
