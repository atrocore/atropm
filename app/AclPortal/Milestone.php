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
use Espo\Entities\User;
use Espo\ORM\Entity;

/**
 * Class Milestone
 */
class Milestone extends Base
{
    /**
     * @inheritDoc
     */
    public function checkInAccount(User $user, Entity $entity)
    {
        $accountIdList = $user->getLinkMultipleIdList('accounts');

        if (count($accountIdList) == 0) {
            return false;
        }

        if (!empty($project = $entity->get('project')) && !empty($project->get('accountId'))) {
            if (in_array($project->get('accountId'), $accountIdList)) {
                return true;
            }
        }

        $accountsIds = implode("','", $accountIdList);

        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare(
            "SELECT i.milestone_id FROM `issue` AS i LEFT JOIN `project` AS p ON p.id=i.project_id WHERE i.deleted=0 AND p.deleted=0 AND p.account_id IN ('$accountsIds') AND i.milestone_id IS NOT NULL"
        );
        $sth->execute();

        return in_array($entity->get('id'), $sth->fetchAll(\PDO::FETCH_COLUMN));
    }
}

