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

namespace ProjectManagement\AclPortal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Espo\Core\AclPortal\Base;
use Espo\Entities\User as UserEntity;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * Class User
 */
class User extends Base
{
    public static function getProjectsUsersIds(EntityManager $entityManager, array $accountIdList): array
    {
        /** @var Connection $conn */
        $conn = $entityManager->getConnection();

        $accountsIds = implode("','", $accountIdList);

        $sth = $entityManager->getPDO()->prepare(
            "SELECT DISTINCT u.id FROM {$conn->quoteIdentifier('project')} p LEFT JOIN {$conn->quoteIdentifier('entity_team')} et ON et.entity_id=p.id LEFT JOIN {$conn->quoteIdentifier('team_user')} tu ON tu.team_id=et.team_id LEFT JOIN {$conn->quoteIdentifier('user')} u ON u.id=tu.user_id WHERE p.account_id IN ('$accountsIds') AND et.entity_type='Project' AND u.deleted=:false"
        );
        $sth->bindValue(':false', false, ParameterType::BOOLEAN);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @inheritDoc
     */
    public function checkInAccount(UserEntity $user, Entity $entity)
    {
        $accountIdList = $user->getLinkMultipleIdList('accounts');
        if (count($accountIdList)) {
            if (in_array($user->get('id'), self::getProjectsUsersIds($this->getEntityManager(), $accountIdList))) {
                return true;
            }
        }

        return false;
    }
}

