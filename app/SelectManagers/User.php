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
use Espo\ORM\Entity;

/**
 * Class User
 */
class User extends \Espo\SelectManagers\User
{
    /**
     * @inheritDoc
     */
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        if (!empty($params['where'])) {
            foreach ($params['where'] as &$where) {
                $method = $where['value'] . 'Filter';
                if (method_exists($this, $method)) {
                    $this->$method($where);
                }
            }
            unset($where);
        }

        return parent::getSelectParams($params, $withAcl, $checkWherePermission);
    }

    protected function issueAssignedUsersFilter(array &$where): void
    {
        $teamsIds = $where['data']['teamsIds'];
        if (empty($teamsIds)){
            $teamsIds = ['no-such-id'];
        }

        /** @var Entity $project */
        $project = $this->getEntityManager()->getEntity('Project', $where['data']['projectId']);
        if (!empty($project)) {
            $teamsIds = array_merge($teamsIds, $project->getLinkMultipleIdList('teams'));
        }

        $conn = $this->getEntityManager()->getConnection();

        $res = $conn->createQueryBuilder()
            ->select('user_id')
            ->from('team_user')
            ->where('team_id IN (:teamsIds)')
            ->andWhere('deleted = :false')
            ->setParameter('teamsIds', $teamsIds, Connection::PARAM_STR_ARRAY)
            ->setParameter('false', false, ParameterType::BOOLEAN)
            ->fetchAllAssociative();

        $where = [
            'type'      => 'in',
            'attribute' => 'id',
            'value'     => array_column($res, 'user_id'),
        ];
    }

    /**
     * @inheritDoc
     */
    protected function access(&$result)
    {
        \Espo\Core\SelectManagers\Base::access($result);

        if (!$this->getUser()->isAdmin()) {
            $result['whereClause'][] = array(
                'isActive' => true
            );
        }
        $result['whereClause'][] = array(
            'isSuperAdmin' => false
        );
    }
}
