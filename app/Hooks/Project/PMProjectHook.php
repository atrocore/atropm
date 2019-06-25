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

namespace ProjectManagement\Hooks\Project;

use Espo\Core\Exceptions\Error;
use Espo\Orm\Entity;

class PMProjectHook extends \Espo\Core\Hooks\Base
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
        if (ctype_digit($entity->get('name'))) {
            throw new Error('Name must not consist of numbers only');
        }

        $projectsEntity = $this->getEntityManager()->getRepository('Project')->where([
            'name' => $entity->get('name'),
            'id!=' => $entity->get('id')
        ])->findOne();

        if (!empty($projectsEntity)) {
            throw new Error('Project with the same name already exists');
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
            $teamsNames = [];
            $teamProjectNameAssigned = false;
            foreach ($entity->get('teams') as $team) {
                if ($team->get('name') == $entity->get('name')) {
                    $teamProjectNameAssigned = true;
                }
                $teamsIds[] = $team->get('id');
                $teamsNames[$team->get('id')] = $team->get('name');
            }

            $getFetchedTeamsIds = $entity->getFetched('teamsIds');
            $getTeamsIds = $entity->get('teamsIds');
            // get removed teams
            $removedTeams = [];
            if (isset($getFetchedTeamsIds) && isset($getTeamsIds)) {
                $removedTeams = array_diff($getFetchedTeamsIds, $getTeamsIds);
            }

            if (!$teamProjectNameAssigned) {
                // if team with project name was not found in assigned teams to Project, try to find it in existing teams
                if (empty($teamEntity = $this->getEntityManager()->getRepository('Team')->where([
                    'name' => $entity->get('name')
                ])->findOne()))
                {
                    // if team does not exist then create new team
                    $teamEntity = $this->getEntityManager()->getEntity('Team');
                    $teamEntity->set([
                        'name' => $entity->get('name')
                    ]);
                    $this->getEntityManager()->saveEntity($teamEntity, $options);

                    // add user creator to Team
                    $userEntity = $this->getEntityManager()->getEntity('User', $entity->get('createdById'));
                    $this->getEntityManager()->getRepository('Team')->relate($teamEntity, 'users', $userEntity);
                }
                $teamsIds[] = $teamEntity->get('id');
                $teamsNames[$teamEntity->get('id')] = $teamEntity->get('name');
            }

            // get teams of group
            if (!empty($entity->get('groupId'))) {
                $groupEntity = $this->getEntityManager()->getEntity('Group', $entity->get('groupId'));
                foreach ($groupEntity->get('teams') as $team) {
                    $teamsIds[] = $team->get('id');
                    $teamsNames[$team->get('id')] = $team->get('name');
                }
            }

            // set all found teams to project
            if (!empty($teamsIds)) {
                $entity->set([
                    'teamsIds' => array_unique($teamsIds),
                    'teamsNames' => $teamsNames
                ]);
                $this->getEntityManager()->saveEntity(
                    $entity,
                    array_merge($options, ['skipPMAutoAssignTeam' => true, 'noStream' => true])
                );
            }

            // get labels of current project
            $labelsEntity = $this->getEntityManager()->getRepository('Label')->where([
                'parentId' => $entity->get('id'),
                'parentType' => 'Project'
            ])->find();
            $this->setTeamsToRelatedEntities(
                $labelsEntity,
                $teamsIds,
                $removedTeams,
                array_merge($options, ['skipPMAutoAssignTeam' => true])
            );

            // get milestones of current project
            $milestonesEntity = $this->getEntityManager()->getRepository('Milestone')->where([
                'parentId' => $entity->get('id'),
                'parentType' => 'Project'
            ])->find();
            $this->setTeamsToRelatedEntities($milestonesEntity, $teamsIds, $removedTeams, $options);

            // get issues of current project
            $issuesEntity = $this->getEntityManager()->getRepository('Issue')->where([
                'projectId' => $entity->get('id')
            ])->find();
            $this->setTeamsToRelatedEntities($issuesEntity, $teamsIds, $removedTeams, $options);

            // get expenses of current project
            $expensesEntity = $this->getEntityManager()->getRepository('Expense')->where([
                'parentId' => $entity->get('id'),
                'parentType' => 'Project'
            ])->find();
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
     * Before remove entity hook
     * Before deleting a project need to delete everything related with it
     *
     * @param Entity $entity
     * @param array $options
     */
    public function beforeRemove(Entity $entity, array $options = [])
    {
        // remove related Team with project name
        $relatedProjectTeams = $this->getEntityManager()->getRepository('Project')->findRelated(
            $entity,
            'teams',
            [
                'whereClause' => [
                    'name' => $entity->get('name')
                ]
            ]
        );
        foreach ($relatedProjectTeams as $relatedProjectTeam) {
            $this->getEntityManager()->removeEntity($relatedProjectTeam, $options);
        }

        $labelsEntity = $this->getEntityManager()->getRepository('Label')->where([
            'parentType' => 'Project',
            'parentId' => $entity->get('id')
        ])->find();
        foreach ($labelsEntity as $labelEntity) {
            $this->getEntityManager()->removeEntity($labelEntity, $options);
        }

        $milestonesEntity = $this->getEntityManager()->getRepository('Milestone')->where([
            'parentType' => 'Project',
            'parentId' => $entity->get('id')
        ])->find();
        foreach ($milestonesEntity as $milestoneEntity) {
            $this->getEntityManager()->removeEntity($milestoneEntity, $options);
        }

        $issuesEntity = $this->getEntityManager()->getRepository('Issue')->where([
            'projectId' => $entity->get('id')
        ])->find();
        foreach ($issuesEntity as $issueEntity) {
            $this->getEntityManager()->removeEntity($issueEntity, $options);
        }
    }
}
