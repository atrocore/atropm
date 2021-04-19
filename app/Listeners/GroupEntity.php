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

        if ($group->get('parentGroup')) {
            if ($group->get('id') == $group->get('parentGroupId')) {
                throw new Error('Unable to set self parent group');
            } else {
                // check parentGroup
                $this->checkParentGroup($group->get('id'), $group->get('parentGroupId'));
            }
        }
    }

    private function checkParentGroup($entityId, $parentGroupId)
    {
        $parentGroupEntity = $this->getEntityManager()->getEntity('Group', $parentGroupId);
        if ($parentGroupEntity->get('parentGroup')) {
            if ($parentGroupEntity->get('parentGroupId') == $entityId) {
                throw new Error('This parent group is not allowed for this group');
            } else {
                $this->checkParentGroup($entityId, $parentGroupEntity->get('parentGroupId'));
            }
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

            // get teams of parent group
            if (!empty($group->get('parentGroupId'))) {
                $parentGroupEntity = $this->getEntityManager()->getEntity('Group', $group->get('parentGroupId'));
                foreach ($parentGroupEntity->get('teams') as $team) {
                    $teamsIds[] = $team->get('id');
                    $teamsNames[$team->get('id')] = $team->get('name');
                }
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

            // get subgroups of current group
            $subGroupsEntity = $this->getEntityManager()->getRepository('Group')->where([
                'parentGroupId' => $group->get('id')
            ])->find();
            $this->setTeamsToRelatedEntities($subGroupsEntity, $teamsIds, $removedTeams, $options);

            // get labels of current group
            $labelsEntity = $this->getEntityManager()->getRepository('Label')->where([
                'parentId' => $group->get('id'),
                'parentType' => 'Group'
            ])->find();
            $this->setTeamsToRelatedEntities(
                $labelsEntity,
                $teamsIds,
                $removedTeams,
                array_merge($options, ['skipPMAutoAssignTeam' => true])
            );

            // get milestones of current group
            $milestonesEntity = $this->getEntityManager()->getRepository('Milestone')->where([
                'parentId' => $group->get('id'),
                'parentType' => 'Group'
            ])->find();
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
     * Before relate entity listener
     *
     * @param Event $event
     * @throws Error
     */
    public function beforeRelate(Event $event)
    {
        // get group entity
        $group = $this->getEntity($event);

        if ($event->getArgument('relationName') == 'subgroups' && $group->get('subgroups')) {
            if ($group->get('id') == $event->getArgument('foreign')->get('id')) {
                throw new Error('Unable to set self subgroup');
            } else {
                // check subgroups
                $this->checkSubgroups($group->get('id'), $event->getArgument('foreign')->get('subgroups'));
            }
        }
    }

    private function checkSubgroups($entityId, $subgroups)
    {
        foreach ($subgroups as $subgroup) {
            if ($subgroup->get('id') == $entityId) {
                throw new Error('This subgroup is not allowed for this group');
            } else {
                if ($subgroup->get('subgroups')) {
                    $this->checkSubgroups($entityId, $subgroup->get('subgroups'));
                }
            }
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

        $groupsEntity = $this->getEntityManager()->getRepository('Group')->where([
            'parentGroupId' => $group->get('id')
        ])->find();
        foreach ($groupsEntity as $groupEntity) {
            $this->getEntityManager()->removeEntity($groupEntity, $options);
        }

        $labelsEntity = $this->getEntityManager()->getRepository('Label')->where([
            'parentType' => 'Group',
            'parentId' => $group->get('id')
        ])->find();
        foreach ($labelsEntity as $labelEntity) {
            $this->getEntityManager()->removeEntity($labelEntity, $options);
        }

        $milestonesEntity = $this->getEntityManager()->getRepository('Milestone')->where([
            'parentType' => 'Group',
            'parentId' => $group->get('id')
        ])->find();
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
