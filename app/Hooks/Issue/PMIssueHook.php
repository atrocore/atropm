<?php
/**
 * Project Management
 * TreoLabs Premium Module
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see <https://treolabs.com/eula>.
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

namespace ProjectManagement\Hooks\Issue;

use Espo\Orm\Entity;

class PMIssueHook extends \Espo\Core\Hooks\Base
{
    /**
     * After save entity hook
     *
     * @param Entity $entity
     * @param array $options
     */
    public function afterSave(Entity $entity, array $options = [])
    {
        $this->countIssues($entity);

        // set "Date Completed" in Expenses of current Issue if state has changed to "closed"
        if ($entity->isAttributeChanged('state') && $entity->get('state') == 'closed') {
            $expensesEntity = $this->getEntityManager()->getRepository('Expense')->where([
                'parentId' => $entity->get('id'),
                'parentType' => $entity->getEntityType()
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
            if (!empty($entity->get('projectId'))) {
                $projectEntity = $this->getEntityManager()->getEntity('Project', $entity->get('projectId'));
                foreach ($projectEntity->get('teams') as $team) {
                    $teamsIds[] = $team->get('id');
                }
            }

            // set all found teams to issue
            if (!empty($teamsIds)) {
                $entity->set([
                    'teamsIds' => $teamsIds
                ]);
                $this->getEntityManager()->saveEntity(
                    $entity,
                    array_merge($options, ['skipPMAutoAssignTeam' => true, 'noStream' => true])
                );
            }

            // get expenses of current issue
            $expensesEntity = $this->getEntityManager()->getRepository('Expense')->where([
                'parentId' => $entity->get('id'),
                'parentType' => $entity->getEntityType()
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
     * After relate entity hook
     * @param Entity $entity
     * @param array $options
     */
    public function afterRelate(Entity $entity, array $options = [])
    {
        $this->countIssues($entity);
    }

    /**
     * After remove entity hook
     *
     * @param Entity $entity
     * @param array $options
     */
    public function afterRemove(Entity $entity, array $options = [])
    {
        $this->countIssues($entity);
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
