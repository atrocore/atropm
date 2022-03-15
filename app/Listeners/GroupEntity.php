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
*
* This software is not allowed to be used in Russia and Belarus.
*/

declare(strict_types=1);

namespace ProjectManagement\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;
use Espo\Orm\Entity;
use Espo\Core\Exceptions\Error;

/**
 * Class GroupEntity
 */
class GroupEntity extends AbstractListener
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
     * @throws Error
     */
    public function beforeSave(Event $event)
    {
        // get group entity
        $group = $this->getEntity($event);

        if (ctype_digit($group->get('name'))) {
            throw new Error('Name must not consist of numbers only');
        }

        $groupsEntity = $this->getEntityManager()->getRepository('Group')->where([
            'name' => $group->get('name'),
            'id!=' => $group->get('id')
        ])->findOne();

        if (!empty($groupsEntity)) {
            throw new Error('Group with the same name already exists');
        }
    }

    /**
     * After save entity listener
     *
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // get group entity
        $group = $this->getEntity($event);
        // get options
        $options = $this->getOptions($event);

        // auto assign teams
        if (empty($options['skipPMAutoAssignTeam'])) {
            // try to find team with group name
            $teamsIds = [];
            $teamsNames = [];
            $teamGroupNameAssigned = false;
            foreach ($group->get('teams') as $team) {
                if ($team->get('name') == $group->get('name')) {
                    $teamGroupNameAssigned = true;
                }
                $teamsIds[] = $team->get('id');
                $teamsNames[$team->get('id')] = $team->get('name');
            }

            $getFetchedTeamsIds = $group->getFetched('teamsIds');
            $getTeamsIds = $group->get('teamsIds');
            // get removed teams
            $removedTeams = [];
            if (isset($getFetchedTeamsIds) && isset($getTeamsIds)) {
                $removedTeams = array_diff($getFetchedTeamsIds, $getTeamsIds);
            }

            if (!$teamGroupNameAssigned) {
                // if team with group name was not found in assigned teams to Group, try to find it in existing teams
                if (empty($teamEntity = $this->getEntityManager()->getRepository('Team')->where([
                    'name' => $group->get('name')
                ])->findOne()))
                {
                    // if team does not exist then create new team
                    $teamEntity = $this->getEntityManager()->getEntity('Team');
                    $teamEntity->set([
                        'name' => $group->get('name')
                    ]);
                    $this->getEntityManager()->saveEntity($teamEntity, $options);

                    // add user creator to Team
                    $userEntity = $this->getEntityManager()->getEntity('User', $group->get('createdById'));
                    $this->getEntityManager()->getRepository('Team')->relate($teamEntity, 'users', $userEntity);
                }
                $teamsIds[] = $teamEntity->get('id');
                $teamsNames[$teamEntity->get('id')] = $teamEntity->get('name');
            }

            // set all found teams to group
            if (!empty($teamsIds)) {
                $group->set([
                    'teamsIds' => array_unique($teamsIds),
                    'teamsNames' => $teamsNames
                ]);
                $this->getEntityManager()->saveEntity(
                    $group,
                    array_merge($options, ['skipPMAutoAssignTeam' => true])
                );
            }

            // get labels of current group
            $labelsEntity = $this->getEntityManager()->getRepository('Label')->where(['groupId' => $group->get('id')])->find();
            $this->setTeamsToRelatedEntities(
                $labelsEntity,
                $teamsIds,
                $removedTeams,
                array_merge($options, ['skipPMAutoAssignTeam' => true])
            );

            // get milestones of current group
            $milestonesEntity = $this->getEntityManager()->getRepository('Milestone')->where(['groupId' => $group->get('id')])->find();
            $this->setTeamsToRelatedEntities($milestonesEntity, $teamsIds, $removedTeams, $options);

            // get projects of current group
            $projectsEntity = $this->getEntityManager()->getRepository('Project')->where([
                'groupId' => $group->get('id')
            ])->find();
            $this->setTeamsToRelatedEntities($projectsEntity, $teamsIds, $removedTeams, $options);
        }
    }

    /**
     * Set teams to related entities of current entity
     *
     * @param array $entities
     * @param array $teamsIds
     * @param array $removedTeams
     * @param array $options
     */
    private function setTeamsToRelatedEntities($entities, $teamsIds, $removedTeams, $options = [])
    {
        foreach ($entities as $entity) {
            $tmp = $teamsIds;
            foreach ($entity->get('teams') as $team) {
                // if team not in removed teams
                if (!in_array($team->get('id'), $removedTeams)) {
                    $tmp = array_merge($tmp, [$team->get('id')]);
                }
            }
            $entity->set([
                'teamsIds' => array_unique($tmp)
            ]);
            $this->getEntityManager()->saveEntity($entity, $options);
        }
    }

    /**
     * Before remove entity listener
     * Before deleting a group need to delete everything related with it
     *
     * @param Event $event
     */
    public function beforeRemove(Event $event)
    {
        // get group entity
        $group = $this->getEntity($event);
        // get options
        $options = $this->getOptions($event);

        // remove related Team with group name
        $relatedGroupTeams = $this->getEntityManager()->getRepository('Group')->findRelated(
            $group,
            'teams',
            [
                'whereClause' => [
                    'name' => $group->get('name')
                ]
            ]
        );
        foreach ($relatedGroupTeams as $relatedGroupTeam) {
            $this->getEntityManager()->removeEntity($relatedGroupTeam, $options);
        }

        $labelsEntity = $this->getEntityManager()->getRepository('Label')->where(['groupId' => $group->get('id')])->find();
        foreach ($labelsEntity as $labelEntity) {
            $this->getEntityManager()->removeEntity($labelEntity, $options);
        }

        $milestonesEntity = $this->getEntityManager()->getRepository('Milestone')->where(['groupId' => $group->get('id')])->find();
        foreach ($milestonesEntity as $milestoneEntity) {
            $this->getEntityManager()->removeEntity($milestoneEntity, $options);
        }

        $projectsEntity = $this->getEntityManager()->getRepository('Project')->where([
            'groupId' => $group->get('id')
        ])->find();
        foreach ($projectsEntity as $projectEntity) {
            $this->getEntityManager()->removeEntity($projectEntity, $options);
        }
    }
}
