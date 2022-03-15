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
*
* This software is not allowed to be used in Russia and Belarus.
*/

declare(strict_types=1);

namespace ProjectManagement\AclPortal;

use Espo\Core\AclPortal\Base;
use Espo\Entities\User as UserEntity;
use Espo\ORM\Entity;

/**
 * Class User
 */
class User extends Base
{
    public static function getProjectsUsersIds(\PDO $pdo, array $accountIdList): array
    {
        $accountsIds = implode("','", $accountIdList);

        $sth = $pdo->prepare(
            "SELECT DISTINCT u.id FROM `project` AS p LEFT JOIN entity_team AS et ON et.entity_id=p.id LEFT JOIN team_user AS tu ON tu.team_id=et.team_id LEFT JOIN user AS u ON u.id=tu.user_id WHERE p.account_id IN ('$accountsIds') AND et.entity_type='Project' AND u.deleted=0"
        );
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
            if (in_array($user->get('id'), self::getProjectsUsersIds($this->getEntityManager()->getPDO(), $accountIdList))) {
                return true;
            }
        }

        return false;
    }
}

