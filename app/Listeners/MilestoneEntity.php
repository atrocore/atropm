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

namespace ProjectManagement\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;
use Espo\Orm\Entity;
use Espo\Core\Exceptions\Error;

/**
 * Class MilestoneEntity
 *
 * @author o.trelin <o.trelin@treolabs.com>
 * @author d.talko <d.talko@treolabs.com>
 *
 * @package ProjectManagement\Listeners
 */
class MilestoneEntity extends AbstractListener
{
    /**
     * @param Event $event
     *
     * @return Entity
     */
    private function getEntity(Event $event): Entity
    {
        return $event->getArgument('entity');
    }

    /**
     * @param Event $event
     *
     * @return Entity
     */
    private function getOptions(Event $event)
    {
        return $event->getArgument('options');
    }

    /**
     * Before save entity listener
     *
     * @param Event $event
     * @throws Error
     */
    public function beforeSave(Event $event)
    {
        // get milestone entity
        $milestone = $this->getEntity($event);

        if (!empty($milestone->get('startDate'))
            && !empty($milestone->get('dueDate'))
            && $milestone->get('startDate') >= $milestone->get('dueDate'))
        {
            throw new Error('Due Date must be greater than Start Date');
        }
    }

    /**
     * After save entity listener
     *
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // get milestone entity
        $milestone = $this->getEntity($event);
        // get options
        $options = $this->getOptions($event);

        // auto assign teams
        if (empty($options['skipPMAutoAssignTeam'])) {
            $teamsIds = [];

            // get teams of parent entity
            if (!empty($milestone->get('parentId'))) {
                $parentEntity = $this->getEntityManager()->getEntity(
                    $milestone->get('parentType'),
                    $milestone->get('parentId')
                );
                foreach ($parentEntity->get('teams') as $team) {
                    $teamsIds[] = $team->get('id');
                }
            }

            // set all found teams to milestone
            if (!empty($teamsIds)) {
                $milestone->set([
                    'teamsIds' => $teamsIds
                ]);
                $this->getEntityManager()->saveEntity(
                    $milestone,
                    array_merge($options, ['skipPMAutoAssignTeam' => true])
                );
            }

            // get expenses of current milestone
            $expensesEntity = $this->getEntityManager()->getRepository('Expense')->where([
                'parentId' => $milestone->get('id'),
                'parentType' => $milestone->getEntityType()
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
     * Before remove entity listener
     * Before deleting a milestone need to delete everything related with it
     *
     * @param Event $event
     */
    public function beforeRemove(Event $event)
    {
        // get milestone entity
        $milestone = $this->getEntity($event);
        // get options
        $options = $this->getOptions($event);

        $issuesEntity = $this->getEntityManager()->getRepository('Issue')->where([
            'milestoneId' => $milestone->get('id')
        ])->find();
        foreach ($issuesEntity as $issueEntity) {
            $issueEntity->set([
                'milestoneId' => null
            ]);
            $this->getEntityManager()->saveEntity($issueEntity, $options);
        }
    }
}
