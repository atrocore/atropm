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

namespace ProjectManagement\Repositories;

use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Class AbstractRepository
 */
abstract class AbstractRepository extends Base
{
    protected function calculateEntityTotal(?Entity $entity): void
    {
        if (empty($entity)) {
            return;
        }

        $issues = $entity->get('issues');

        $totalIssues = $issues->count();
        $openIssues = 0;

        if ($totalIssues > 0) {
            foreach ($issues as $issue) {
                if (empty($issue->get('closed'))) {
                    $openIssues++;
                }
            }
        }

        $entity->set('totalIssues', $totalIssues);
        $entity->set('openIssues', $openIssues);

        $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
    }
}
