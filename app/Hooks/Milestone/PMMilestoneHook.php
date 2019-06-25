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

namespace ProjectManagement\Hooks\Milestone;

use Espo\Orm\Entity;
use Espo\Core\Exceptions\Error;

class PMMilestoneHook extends \Espo\Core\Hooks\Base
{
    /**
     * Before save entity hook
     *
     * @param Entity $entity
     * @param array $options
     * @throws Error
     */
    public function beforeSave(Entity $entity, array $options = [])
    {
        if (!empty($entity->get('startDate'))
            && !empty($entity->get('dueDate'))
            && $entity->get('startDate') >= $entity->get('dueDate'))
        {
            throw new Error('Due Date must be greater than Start Date');
        }
    }

    /**
     * After save entity hook
     *
     * @param Entity $entity
     * @param array $options
     */
    public function afterSave(Entity $entity, array $options = [])
    {
        // auto assign teams
        if (empty($options['skipPMAutoAssignTeam'])) {
            $teamsIds = [];

            // get teams of parent entity
            if (!empty($entity->get('parentId'))) {
                $parentEntity = $this->getEntityManager()->getEntity(
                    $entity->get('parentType'),
                    $entity->get('parentId')
                );
                foreach ($parentEntity->get('teams') as $team) {
                    $teamsIds[] = $team->get('id');
                }
            }

            // set all found teams to milestone
            if (!empty($teamsIds)) {
                $entity->set([
                    'teamsIds' => $teamsIds
                ]);
                $this->getEntityManager()->saveEntity(
                    $entity,
                    array_merge($options, ['skipPMAutoAssignTeam' => true, 'noStream' => true])
                );
            }

            // get expenses of current milestone
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
     * Before remove entity hook
     * Before deleting a milestone need to delete everything related with it
     *
     * @param Entity $entity
     * @param array $options
     */
    public function beforeRemove(Entity $entity, array $options = [])
    {
        $issuesEntity = $this->getEntityManager()->getRepository('Issue')->where([
            'milestoneId' => $entity->get('id')
        ])->find();
        foreach ($issuesEntity as $issueEntity) {
            $issueEntity->set([
                'milestoneId' => null
            ]);
            $this->getEntityManager()->saveEntity($issueEntity, $options);
        }
    }
}
