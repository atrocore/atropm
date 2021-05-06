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

namespace ProjectManagement\Acl;

use Espo\Core\Acl\Base;
use Espo\Entities\User;
use Espo\ORM\Entity;

/**
 * Class Milestone
 */
class Milestone extends Base
{
    public static function getMilestoneIdsByIssues(\PDO $pdo, array $userTeamIdList): array
    {
        $sqlTeamsIds = implode("','", $userTeamIdList);

        $sth = $pdo->prepare(
            "SELECT i.milestone_id FROM `issue` AS i LEFT JOIN `project` AS p ON p.id=i.project_id LEFT JOIN `entity_team` AS et ON et.entity_id=p.id WHERE i.deleted=0 AND p.deleted=0 AND et.deleted=0 AND et.entity_type='Project' AND et.team_id IN ('$sqlTeamsIds') AND milestone_id IS NOT NULL"
        );
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @inheritDoc
     */
    public function checkInTeam(User $user, Entity $entity)
    {
        $userTeamIdList = $user->getLinkMultipleIdList('teams');

        if (!$entity->hasRelation('teams') || !$entity->hasAttribute('teamsIds')) {
            return false;
        }

        if (in_array($entity->get('id'), self::getMilestoneIdsByIssues($this->getEntityManager()->getPDO(), $userTeamIdList))) {
            return true;
        }

        $entityTeamIdList = $entity->getLinkMultipleIdList('teams');

        if (!empty($project = $entity->get('project'))) {
            $entityTeamIdList = array_merge($entityTeamIdList, $project->getLinkMultipleIdList('teams'));
        }

        if (empty($entityTeamIdList)) {
            return false;
        }

        foreach ($userTeamIdList as $id) {
            if (in_array($id, $entityTeamIdList)) {
                return true;
            }
        }
        return false;
    }
}

