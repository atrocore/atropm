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

namespace ProjectManagement\Listeners;

use Atro\Listeners\AbstractListener;
use Atro\Core\EventManager\Event;
use Espo\Orm\Entity;
use Espo\Core\Exceptions\Error;

/**
 * Class MilestoneEntity
 */
class MilestoneEntity extends AbstractListener
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
     * Before save entity listener
     *
     * @param Event $event
     *
     * @throws Error
     */
    public function beforeSave(Event $event)
    {
        // get milestone entity
        $milestone = $this->getEntity($event);

        if (!empty($milestone->get('startDate'))
            && !empty($milestone->get('dueDate'))
            && $milestone->get('startDate') >= $milestone->get('dueDate')) {
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

            foreach (['project', 'group'] as $parentEntityType) {
                if (!empty($parentEntity = $milestone->get($parentEntityType))) {
                    foreach ($parentEntity->get('teams') as $teamId) {
                        $teamsIds[] = $teamId->get('id');
                    }
                }
            }

            // set all found teams to milestone
            if (!empty($teamsIds)) {
                foreach ($teamsIds as $teamId) {
                    $this->getEntityManager()->getRepository($milestone->getEntityType())->relate($milestone, 'teams', $teamId);
                }
            }

            // get expenses of current milestone
            $expensesEntity = $milestone->get('expenses');
            foreach ($expensesEntity as $expenseEntity) {
                foreach ($teamsIds as $teamId) {
                    $this->getEntityManager()->getRepository($expenseEntity->getEntityType())->relate($expenseEntity, 'teams', $teamId);
                }
            }
        }
    }
}
