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

namespace ProjectManagement\Hooks\Label;

use Espo\Orm\Entity;
use Espo\Core\Exceptions\Error;

class PMLabelHook extends \Espo\Core\Hooks\Base
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
        $parentType = $entity->get('parentType');
        $parentId = $entity->get('parentId');
        if ($parentType == 'Project') {
            if ($this->isNameExist($entity->get('id'), $entity->get('name'), $parentType, $parentId)) {
                throw new Error('Name has already been taken for this project');
            }

            // get project group
            $project = $this->getEntityManager()->getEntity('Project', $parentId);
            if ($project->get('groupId')) {
                $parentType = 'Group';
                $parentId = $project->get('groupId');
            }
        }

        if ($parentType == 'Group') {
            $this->checkGroupLabel($entity->get('id'), $entity->get('name'), $parentType, $parentId);
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

            // set all found teams to label
            if (!empty($teamsIds)) {
                $entity->set([
                    'teamsIds' => $teamsIds
                ]);
                $options['skipPMAutoAssignTeam'] = true;
                $options['noStream'] = true;
                $this->getEntityManager()->saveEntity($entity, $options);
            }
        }
    }

    /**
     * Check label name
     *
     * @param string $id
     * @param string $name
     * @param string $parentType
     * @param string $parentId
     * @return bool
     */
    protected function isNameExist($id, $name, $parentType, $parentId)
    {
        $labels = $this->getEntityManager()->getRepository('Label')->where([
            'id!=' => $id,
            'name' => $name,
            'parentType' => $parentType,
            'parentId' => $parentId
        ])->findOne();

        return (!empty($labels));
    }

    /**
     * Check group label
     *
     * @param string $id
     * @param string $name
     * @param string $parentType
     * @param string $parentId
     * @throws Error
     */
    protected function checkGroupLabel($id, $name, $parentType, $parentId)
    {
        $group = $this->getEntityManager()->getEntity('Group', $parentId);
        if ($this->isNameExist($id, $name, $parentType, $parentId)) {
            throw new Error('Name has already been taken for group ' . $group->get('name'));
        } elseif (!empty($group->get('parentGroup'))) {
            $this->checkGroupLabel($id, $name, $parentType, $group->get('parentGroupId'));
        }
    }
}
