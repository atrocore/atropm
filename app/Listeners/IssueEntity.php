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
        if ($issue->isAttributeChanged('state') && $issue->get('state') == 'closed') {
            $expensesEntity = $this->getEntityManager()->getRepository('Expense')->where([
                'parentId' => $issue->get('id'),
                'parentType' => $issue->getEntityType()
            ])->find();

            foreach ($expensesEntity as $expenseEntity) {
                $expenseEntity->set([
                    'dateCompleted' => date('Y-m-d')
                ]);
                $this->getEntityManager()->saveEntity($expenseEntity, $options);
            }
        }

        // auto assign teams
        if (empty($options['skipPMAutoAssignTeam'])) {
            $teamsIds = [];

            // get teams of project
            if (!empty($issue->get('projectId'))) {
                $projectEntity = $this->getEntityManager()->getEntity('Project', $issue->get('projectId'));
                foreach ($projectEntity->get('teams') as $team) {
                    $teamsIds[] = $team->get('id');
                }
            }

            // set all found teams to issue
            if (!empty($teamsIds)) {
                $issue->set([
                    'teamsIds' => $teamsIds
                ]);
                $this->getEntityManager()->saveEntity(
                    $issue,
                    array_merge($options, ['skipPMAutoAssignTeam' => true])
                );
            }

            // get expenses of current issue
            $expensesEntity = $this->getEntityManager()->getRepository('Expense')->where([
                'parentId' => $issue->get('id'),
                'parentType' => $issue->getEntityType()
            ])->find();
            foreach ($expensesEntity as $expenseEntity) {
                $expenseEntity->set([
                    'teamsIds' => $teamsIds
                ]);
                $this->getEntityManager()->saveEntity(
                    $expenseEntity,
                    array_merge($options, ['skipPMAutoAssignTeam' => true])
                );
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
            $issuesProjectEntityTotal = $this->getEntityManager()->getRepository('Issue')->where([
                'projectId' => $entity->get('projectId')
            ])->find();
            $issuesProjectEntityClosed = $this->getEntityManager()->getRepository('Issue')->where([
                'projectId' => $entity->get('projectId'),
                'state' => 'closed'
            ])->find();
            $projectEntity->set([
                'totalIssues' => count($issuesProjectEntityTotal),
                'closedIssues' => count($issuesProjectEntityClosed)
            ]);
            $this->getEntityManager()->saveEntity($projectEntity, ['skipAll' => true]);

            // count Issues for Milestone
            if (!empty($entity->get('milestoneId'))) {
                $milestoneEntity = $this->getEntityManager()->getEntity('Milestone', $entity->get('milestoneId'));
                if (!empty($milestoneEntity)) {
                    $issuesMilestoneEntityTotal = $this->getEntityManager()->getRepository('Issue')->where([
                        'milestoneId' => $milestoneEntity->get('id')
                    ])->find();
                    $issuesMilestoneEntityClosed = $this->getEntityManager()->getRepository('Issue')->where([
                        'milestoneId' => $milestoneEntity->get('id'),
                        'state' => 'closed'
                    ])->find();
                    $milestoneEntity->set([
                        'totalIssues' => count($issuesMilestoneEntityTotal),
                        'closedIssues' => count($issuesMilestoneEntityClosed)
                    ]);
                    $this->getEntityManager()->saveEntity($milestoneEntity, ['skipAll' => true]);
                }
            }
        }
    }
}
