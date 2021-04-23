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

namespace ProjectManagement\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;
use Espo\Orm\Entity;

/**
 * Class IssueEntity
 */
class IssueEntity extends AbstractListener
{
    /**
     * @param Event $event
     *
     * @return Entity
     */
    protected function getEntity(Event $event): Entity
    {
        return $event->getArgument('entity');
    }

    /**
     * @param Event $event
     *
     * @return Entity
     */
    protected function getOptions(Event $event)
    {
        return $event->getArgument('options');
    }

    /**
     * After save entity listener
     *
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // get issue entity
        $issue = $this->getEntity($event);
        // get options
        $options = $this->getOptions($event);

        $this->countIssues($issue);

        // set "Date Completed" in Expenses of current Issue if state has changed to "closed"
        if ($issue->isAttributeChanged('closed') && $issue->get('closed')) {
            $expensesEntity = $issue->get('expenses');

            foreach ($expensesEntity as $expenseEntity) {
                $expenseEntity->set([
                    'dateCompleted' => date('Y-m-d')
                ]);
                $this->getEntityManager()->saveEntity($expenseEntity, $options);
            }
        }
    }

    /**
     * After relate entity listener
     * @param Event $event
     */
    public function afterRelate(Event $event)
    {
        // get issue entity
        $issue = $this->getEntity($event);

        $this->countIssues($issue);
    }

    /**
     * After remove entity listener
     *
     * @param Event $event
     */
    public function afterRemove(Event $event)
    {
        // get issue entity
        $issue = $this->getEntity($event);

        $this->countIssues($issue);
    }

    /**
     * @param $entity
     */
    public function countIssues($entity)
    {
        // count Issues for Project
        $projectEntity = $this->getEntityManager()->getEntity('Project', $entity->get('projectId'));
        if (!empty($projectEntity)) {
            $repository = $this->getEntityManager()->getRepository('Issue');
            $projectEntity->set([
                'totalIssues' => $repository->where(['projectId' => $entity->get('projectId')])->count(),
                'openIssues' => $repository->where(['projectId' => $entity->get('projectId'), 'closed!=' => true])->count()
            ]);
            $this->getEntityManager()->saveEntity($projectEntity, ['skipAll' => true]);

            // count Issues for Milestone
            if (!empty($entity->get('milestoneId'))) {
                $milestoneEntity = $this->getEntityManager()->getEntity('Milestone', $entity->get('milestoneId'));
                if (!empty($milestoneEntity)) {
                    $milestoneEntity->set([
                        'totalIssues' => $repository->where(['milestoneId' => $milestoneEntity->get('id')])->count(),
                        'openIssues' => $repository->where(['milestoneId' => $milestoneEntity->get('id'), 'closed!=' => true])->count()
                    ]);
                    $this->getEntityManager()->saveEntity($milestoneEntity, ['skipAll' => true]);
                }
            }
        }
    }
}
