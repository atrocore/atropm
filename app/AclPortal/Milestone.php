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
        if (empty($accountIdList)) {
            $accountIdList = [];
        }

        if (!empty($user->get('accountId'))) {
            $accountIdList[] = $user->get('accountId');
        }

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

