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

