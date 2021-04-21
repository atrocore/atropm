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

    /**
     * After save entity listener
     *
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // get project entity
        $project = $this->getEntity($event);
        // get options
        $options = $this->getOptions($event);

        // auto assign teams
        if (empty($options['skipPMAutoAssignTeam'])) {
            $teamsIds = [];
            $teamsNames = [];
            $teamProjectNameAssigned = false;
            foreach ($project->get('teams') as $team) {
                if ($team->get('name') == $project->get('name')) {
                    $teamProjectNameAssigned = true;
                }
                $teamsIds[] = $team->get('id');
                $teamsNames[$team->get('id')] = $team->get('name');
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
                if (empty($teamEntity = $this->getEntityManager()->getRepository('Team')->where([
                    'name' => $project->get('name')
                ])->findOne()))
                {
                    // if team does not exist then create new team
                    $teamEntity = $this->getEntityManager()->getEntity('Team');
                    $teamEntity->set([
                        'name' => $project->get('name')
                    ]);
                    $this->getEntityManager()->saveEntity($teamEntity, $options);

                    // add user creator to Team
                    $userEntity = $this->getEntityManager()->getEntity('User', $project->get('createdById'));
                    $this->getEntityManager()->getRepository('Team')->relate($teamEntity, 'users', $userEntity);
                }
                $teamsIds[] = $teamEntity->get('id');
                $teamsNames[$teamEntity->get('id')] = $teamEntity->get('name');
            }

            // get teams of group
            if (!empty($project->get('groupId'))) {
                $groupEntity = $this->getEntityManager()->getEntity('Group', $project->get('groupId'));
                foreach ($groupEntity->get('teams') as $team) {
                    $teamsIds[] = $team->get('id');
                    $teamsNames[$team->get('id')] = $team->get('name');
                }
            }

            // set all found teams to project
            if (!empty($teamsIds)) {
                $project->set([
                    'teamsIds' => array_unique($teamsIds),
                    'teamsNames' => $teamsNames
                ]);
                $this->getEntityManager()->saveEntity(
                    $project,
                    array_merge($options, ['skipPMAutoAssignTeam' => true])
                );
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
            $issuesEntity = $this->getEntityManager()->getRepository('Issue')->where([
                'projectId' => $project->get('id')
            ])->find();
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
