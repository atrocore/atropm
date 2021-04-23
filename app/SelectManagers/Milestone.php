<?php
/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
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

use Treo\Core\SelectManagers\Base;

/**
 * Class Milestone
 */
class Milestone extends Base
{
    protected $additionalFilterTypeList = ['inCategory', 'isUserFromTeams', 'inProjectAndParentGroups'];

    /**
     * @param $field
     * @param $value
     * @param $result
     */
    public function applyInProjectAndParentGroups($field, $value, &$result)
    {
        $projectEntity = $this->getEntityManager()->getEntity('Project', $value);

        $result['whereClause']['OR'] = [
            [
                ['projectId' => $value]
            ],
            [
                ['groupId' => [$projectEntity->get('groupId')]]
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    protected function accessOnlyTeam(&$result)
    {
        $this->setDistinct(true, $result);
        $this->addLeftJoin(['teams', 'teamsAccess'], $result);

        $d = ['teamsAccess.id' => $this->getUser()->getLinkMultipleIdList('teams')];

        $sqlTeamsIds = implode("','", $d['teamsAccess.id']);

        $pdo = $this->getEntityManager()->getPDO();

        $sth = $pdo->prepare(
            "SELECT m.id FROM `milestone` AS m LEFT JOIN `project` AS p ON p.id=m.project_id LEFT JOIN `entity_team` AS et ON et.entity_id=p.id WHERE m.deleted=0 AND p.deleted=0 AND et.deleted=0 AND et.entity_type='Project' AND et.team_id IN ('$sqlTeamsIds')"
        );
        $sth->execute();

        $d['id'] = array_merge($sth->fetchAll(\PDO::FETCH_COLUMN), \ProjectManagement\Acl\Milestone::getMilestoneIdsByIssues($pdo, $d['teamsAccess.id']));

        if ($this->hasOwnerUserField()) {
            $d['ownerUserId'] = $this->getUser()->id;
        }

        if ($this->hasAssignedUserField()) {
            $d['assignedUserId'] = $this->getUser()->id;
        }

        if ($this->hasCreatedByField() && !$this->hasAssignedUserField() && !$this->hasOwnerUserField()) {
            $d['createdById'] = $this->getUser()->id;
        }

        $result['whereClause'][] = ['OR' => $d];
    }

    /**
     * @inheritDoc
     */
    protected function accessPortalOnlyAccount(&$result)
    {
        $d = [];
        $accountIdList = $this->getUser()->getLinkMultipleIdList('accounts');

        if (count($accountIdList)) {
            $d['project.accountId'] = $accountIdList;

            $accountsIds = implode("','", $accountIdList);

            $pdo = $this->getEntityManager()->getPDO();
            $sth = $pdo->prepare(
                "SELECT i.milestone_id FROM `issue` AS i LEFT JOIN `project` AS p ON p.id=i.project_id WHERE i.deleted=0 AND p.deleted=0 AND p.account_id IN ('$accountsIds') AND i.milestone_id IS NOT NULL"
            );
            $sth->execute();

            $d['id'] = $sth->fetchAll(\PDO::FETCH_COLUMN);
        }

        $contactId = $this->getUser()->get('contactId');
        if ($contactId) {
            if ($this->getSeed()->hasAttribute('contactId')) {
                $d['contactId'] = $contactId;
            }
            if ($this->getSeed()->hasRelation('contacts')) {
                $this->addLeftJoin(['contacts', 'contactsAccess'], $result);
                $this->setDistinct(true, $result);
                $d['contactsAccess.id'] = $contactId;
            }
        }

        if ($this->getSeed()->hasAttribute('createdById')) {
            $d['createdById'] = $this->getUser()->id;
        }

        if (!empty($d)) {
            $result['whereClause'][] = [
                'OR' => $d
            ];
        } else {
            $result['whereClause'][] = [
                'id' => null
            ];
        }
    }
}
