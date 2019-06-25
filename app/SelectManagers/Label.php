<?php
/**
 * Project Management
 * TreoLabs Premium Module
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see <https://treolabs.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

declare(strict_types=1);

namespace ProjectManagement\SelectManagers;

class Label extends \Espo\Core\SelectManagers\Base
{
    protected $additionalFilterTypeList = ['inCategory', 'isUserFromTeams', 'inProjectAndParentGroups'];

    /**
     * @param $field
     * @param $value
     * @param $result
     */
    public function applyInProjectAndParentGroups($field, $value, &$result)
    {
        $groups = [];
        $projectEntity = $this->getEntityManager()->getEntity('Project', $value);
        $this->setGroup($projectEntity->get('groupId'), $groups);

        $result['whereClause']['OR'] = [
            [
                ['parentType' => 'Project', 'parentId' => $value]
            ],
            [
                ['parentType' => 'Group', 'parentId' => $groups]
            ]
        ];
    }

    /**
     * @param $groupId
     * @param $groups
     */
    private function setGroup($groupId, &$groups)
    {
        $groups[] = $groupId;
        $groupEntity = $this->getEntityManager()->getEntity('Group', $groupId);
        if ($groupEntity->get('parentGroupId')) {
            $this->setGroup($groupEntity->get('parentGroupId'), $groups);
        }
    }
}
