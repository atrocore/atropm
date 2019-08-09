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
 * Class LabelEntity
 *
 * @author o.trelin <o.trelin@treolabs.com>
 * @author d.talko <d.talko@treolabs.com>
 *
 * @package ProjectManagement\Listeners
 */
class LabelEntity extends AbstractListener
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
        // get label entity
        $label = $this->getEntity($event);

        $parentType = $label->get('parentType');
        $parentId = $label->get('parentId');
        if ($parentType == 'Project') {
            if ($this->isNameExist($label->get('id'), $label->get('name'), $parentType, $parentId)) {
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
            $this->checkGroupLabel($label->get('id'), $label->get('name'), $parentType, $parentId);
        }
    }

    /**
     * After save entity listener
     *
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // get label entity
        $label = $this->getEntity($event);
        // get options
        $options = $this->getOptions($event);

        // auto assign teams
        if (empty($options['skipPMAutoAssignTeam'])) {
            $teamsIds = [];

            // get teams of parent entity
            if (!empty($label->get('parentId'))) {
                $parentEntity = $this->getEntityManager()->getEntity(
                    $label->get('parentType'),
                    $label->get('parentId')
                );
                foreach ($parentEntity->get('teams') as $team) {
                    $teamsIds[] = $team->get('id');
                }
            }

            // set all found teams to label
            if (!empty($teamsIds)) {
                $label->set([
                    'teamsIds' => $teamsIds
                ]);
                $options['skipPMAutoAssignTeam'] = true;
                $this->getEntityManager()->saveEntity($label, $options);
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
