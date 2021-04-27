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

