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
 * Class Issue
 */
class Issue extends Base
{

    /**
     * @param mixed $result
     */
    protected function boolFilterOnlyOpen(&$result)
    {
        $result['whereClause'][] = [
            'closed!=' => true
        ];
    }

    /**
     * @param mixed $result
     */
    protected function boolFilterOnlyClosed(&$result)
    {
        $result['whereClause'][] = [
            'closed' => true
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

        /** @var Connection $conn */
        $conn = $this->getEntityManager()->getConnection();

        $sth = $pdo->prepare(
            "SELECT i.id FROM {$conn->quoteIdentifier('issue')} i LEFT JOIN {$conn->quoteIdentifier('project')} p ON p.id=i.project_id LEFT JOIN {$conn->quoteIdentifier('entity_team')} et ON et.entity_id=p.id WHERE i.deleted=:false AND p.deleted=:false AND et.deleted=:false AND et.entity_type='Project' AND et.team_id IN ('$sqlTeamsIds')"
        );
        $sth->bindValue(':false', false, ParameterType::BOOLEAN);
        $sth->execute();
        $d['id'] = $sth->fetchAll(\PDO::FETCH_COLUMN);

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
}
