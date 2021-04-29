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
        }

        if ($entity->isAttributeChanged('status')) {
            $this->updateOwnership($entity);
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

    protected function updateOwnership(Entity $entity): void
    {
        if ($entity->get('status') == 'In Progress') {
            if (!empty($user = $entity->get('ownerUser'))) {
                $entity->set('assignedUserId', $user->get('id'));
                $entity->set('assignedUserName', $user->get('name'));
            }
        }
        if ($entity->get('status') == 'To Release') {
            if (!empty($user = $this->getUserByUserName('r.ratsun'))) {
                $entity->set('assignedUserId', $user->get('id'));
                $entity->set('assignedUserName', $user->get('name'));
            }
        }
        if ($entity->get('status') == 'Released') {
            if (!empty($user = $this->getUserByUserName('o.zinchenko'))) {
                $entity->set('assignedUserId', $user->get('id'));
                $entity->set('assignedUserName', $user->get('name'));
            }
        }
    }

    protected function getUserByUserName(string $userName): ?\ProjectManagement\Entities\User
    {
        return $this->getEntityManager()->getRepository('User')->where(['userName' => $userName])->findOne();
    }
}
