<?php
/*
* This file is part of AtroPM.
*
* AtroPM - Open Source Project Management application.
* Copyright (C) 2021 AtroCore UG (haftungsbeschränkt).
* Website: https://atrocore.com
*
* AtroPM is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* AtroPM is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with AtroPIM. If not, see http://www.gnu.org/licenses/.
*
* The interactive user interfaces in modified source and object code versions
* of this program must display Appropriate Legal Notices, as required under
* Section 5 of the GNU General Public License version 3.
*
* In accordance with Section 7(b) of the GNU General Public License version 3,
* these Appropriate Legal Notices must retain the display of the "AtroPM" word.
*/

declare(strict_types=1);

namespace ProjectManagement\SelectManagers;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Espo\Core\SelectManagers\Base;

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

        /** @var Connection $conn */
        $conn = $this->getEntityManager()->getConnection();

        $pdo = $this->getEntityManager()->getPDO();

        $sth = $pdo->prepare(
            "SELECT m.id FROM {$conn->quoteIdentifier('milestone')} m LEFT JOIN {$conn->quoteIdentifier('project')} p ON p.id=m.project_id LEFT JOIN {$conn->quoteIdentifier('entity_team')} et ON et.entity_id=p.id WHERE m.deleted=:false AND p.deleted=:false AND et.deleted=:false AND et.entity_type='Project' AND et.team_id IN ('$sqlTeamsIds')"
        );
        $sth->bindValue(':false', false, ParameterType::BOOLEAN);
        $sth->execute();

        $d['id'] = array_merge($sth->fetchAll(\PDO::FETCH_COLUMN), \ProjectManagement\Acl\Milestone::getMilestoneIdsByIssues($this->getEntityManager(), $d['teamsAccess.id']));

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
        $accountId = $this->getUser()->get('accountId');

        /** @var Connection $conn */
        $conn = $this->getEntityManager()->getConnection();

        if (!empty($accountId)) {
            $d['project.accountId'] = $accountId;

            $res = $conn->createQueryBuilder()
                ->select('i.milestone_id')
                ->from($conn->quoteIdentifier('issue'), 'i')
                ->leftJoin('i', $conn->quoteIdentifier('project'), 'p', 'p.id = i.project_id')
                ->where('i.deleted = :false AND p.deleted = :false AND p.account_id = :accountId AND i.milestone_id IS NOT NULL')
                ->setParameter('false', false, ParameterType::BOOLEAN)
                ->setParameter('accountId', $accountId)
                ->fetchAssociative();

            $d['id'] = $res['milestone_id'] ?? 'no-such-id';
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
