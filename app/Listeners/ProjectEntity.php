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

use Espo\Listeners\AbstractListener;
use Espo\Core\EventManager\Event;
use Espo\Core\Exceptions\Error;
use Espo\Orm\Entity;

/**
 * Class ProjectEntity
 */
class ProjectEntity extends AbstractListener
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
     * @return array
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
        // get project entity
        $project = $this->getEntity($event);

        if (ctype_digit($project->get('name'))) {
            throw new Error('Name must not consist of numbers only');
        }

        $projectsEntity = $this->getEntityManager()->getRepository('Project')->where([
            'name' => $project->get('name'),
            'id!=' => $project->get('id')
        ])->findOne();

        if (!empty($projectsEntity)) {
            throw new Error('Project with the same name already exists');
        }
    }

    public function afterSave(Event $event): void
    {
        // get options
        $options = $this->getOptions($event);

        if (!empty($options['skipPMAutoAssignTeam'])) {
            return;
        }

        // get project entity
        $project = $this->getEntity($event);

        $teamsIds = [];
        $teamProjectNameAssigned = false;
        foreach ($project->get('teams') as $team) {
            if ($team->get('name') == $project->get('name')) {
                $teamProjectNameAssigned = true;
            }
            $teamsIds[] = $team->get('id');
        }

        $getFetchedTeamsIds = $project->getFetched('teamsIds');
        $getTeamsIds = $project->get('teamsIds');

        // get removed teams
        $removedTeams = [];
        if (isset($getFetchedTeamsIds) && isset($getTeamsIds)) {
            $removedTeams = array_diff($getFetchedTeamsIds, $getTeamsIds);
        }

        if (!$teamProjectNameAssigned) {
            // if team with project name was not found in assigned teams to Project, try to find it in existing teams
            if (empty($teamEntity = $this->getEntityManager()->getRepository('Team')->where(['name' => $project->get('name')])->findOne())) {
                // if team does not exist then create new team
                $teamEntity = $this->getEntityManager()->getEntity('Team');
                $teamEntity->set([
                    'name' => $project->get('name')
                ]);
                $this->getEntityManager()->saveEntity($teamEntity, ['skipAll' => true]);

                // add user creator to Team
                $this->getEntityManager()->getRepository('Team')->relate($teamEntity, 'users', $project->get('createdById'));
            }
            $teamsIds[] = $teamEntity->get('id');
        }

        // get teams of group
        if (!empty($project->get('groupId'))) {
            $groupEntity = $this->getEntityManager()->getEntity('Group', $project->get('groupId'));
            foreach ($groupEntity->get('teams') as $team) {
                $teamsIds[] = $team->get('id');
            }
        }

        // set all found teams to project
        foreach ($teamsIds as $teamId) {
            $this->getEntityManager()->getRepository($project->getEntityType())->relate($project, 'teams', $teamId);
        }

        // get labels of current project
        $labelsEntity = $this->getEntityManager()->getRepository('Label')->where(['projectId' => $project->get('id')])->find();
        $this->setTeamsToRelatedEntities(
            $labelsEntity,
            $teamsIds,
            $removedTeams,
            array_merge($options, ['skipPMAutoAssignTeam' => true])
        );

        // get milestones of current project
        $milestonesEntity = $this->getEntityManager()->getRepository('Milestone')->where(['projectId' => $project->get('id')])->find();
        $this->setTeamsToRelatedEntities($milestonesEntity, $teamsIds, $removedTeams, $options);

        // get issues of current project
        $issuesEntity = $this->getEntityManager()->getRepository('Issue')->where(['projectId' => $project->get('id')])->find();
        $this->setTeamsToRelatedEntities($issuesEntity, $teamsIds, $removedTeams, $options);

        // get expenses of current project
        $expensesEntity = $this->getEntityManager()->getRepository('Expense')->where(['projectId' => $project->get('id')])->find();
        $this->setTeamsToRelatedEntities(
            $expensesEntity,
            $teamsIds,
            $removedTeams,
            array_merge($options, ['skipPMAutoAssignTeam' => true])
        );
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
     * Before deleting a project need to delete everything related with it
     *
     * @param Event $event
     */
    public function beforeRemove(Event $event)
    {
        // get project entity
        $project = $this->getEntity($event);
        // get options
        $options = $this->getOptions($event);

        // remove related Team with project name
        $relatedProjectTeams = $this->getEntityManager()->getRepository('Project')->findRelated(
            $project,
            'teams',
            [
                'whereClause' => [
                    'name' => $project->get('name')
                ]
            ]
        );
        foreach ($relatedProjectTeams as $relatedProjectTeam) {
            $this->getEntityManager()->removeEntity($relatedProjectTeam, $options);
        }

        $labelsEntity = $this->getEntityManager()->getRepository('Label')->where(['projectId' => $project->get('id')])->find();
        foreach ($labelsEntity as $labelEntity) {
            $this->getEntityManager()->removeEntity($labelEntity, $options);
        }

        $milestonesEntity = $this->getEntityManager()->getRepository('Milestone')->where(['projectId' => $project->get('id')])->find();
        foreach ($milestonesEntity as $milestoneEntity) {
            $this->getEntityManager()->removeEntity($milestoneEntity, $options);
        }

        $issuesEntity = $this->getEntityManager()->getRepository('Issue')->where([
            'projectId' => $project->get('id')
        ])->find();
        foreach ($issuesEntity as $issueEntity) {
            $this->getEntityManager()->removeEntity($issueEntity, $options);
        }
    }
}
