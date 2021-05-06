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

namespace ProjectManagement\Repositories;

use Espo\ORM\Entity;

/**
 * Class Issue
 */
class Issue extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->isNew()) {
            $entity->set('position', $this->findPosition((string)$entity->get('status')));
        } else {
            if ($entity->isAttributeChanged('status')) {
                $entity->set('position', $this->findPosition((string)$entity->get('status')));
            }
        }

        parent::beforeSave($entity, $options);
    }

    protected function findPosition(string $status): int
    {
        $last = $this
            ->select(['position'])
            ->where(['status' => $status])
            ->order('position', 'DESC')
            ->findOne();

        return empty($last) ? 1 : $last->get('position') + 1;
    }

    /**
     * @inheritDoc
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        if ($entity->isAttributeChanged('projectId')) {
            $this->calculateEntityTotal($entity->get('project'));
        }

        if ($entity->isAttributeChanged('milestoneId')) {
            $this->calculateEntityTotal($entity->getFetched('milestone'));
            $this->calculateEntityTotal($entity->get('milestone'));
        }

        // set "Date Completed" in Expenses of current Issue if state has changed to "closed"
        if ($entity->isAttributeChanged('closed') && $entity->get('closed')) {
            $expenses = $entity->get('expenses');
            if ($expenses->count() > 0) {
                foreach ($expenses as $expense) {
                    $expense->set('dateCompleted', date('Y-m-d'));
                    $this->getEntityManager()->saveEntity($expense);
                }
            }
        }

        parent::afterSave($entity, $options);
    }

    /**
     * @inheritDoc
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        $this->calculateEntityTotal($entity->get('project'));
        $this->calculateEntityTotal($entity->get('milestone'));

        parent::afterRemove($entity, $options);
    }
}
