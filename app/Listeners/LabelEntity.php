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
use Espo\Orm\Entity;
use Espo\Core\Exceptions\Error;

/**
 * Class LabelEntity
 */
class LabelEntity extends AbstractListener
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
        // get label entity
        $label = $this->getEntity($event);

        if (!empty($label->get('projectId'))) {
            if ($this->isNameExist($label->get('id'), $label->get('name'), 'projectId', $label->get('projectId'))) {
                throw new Error('Name has already been taken for this project');
            }
        }

        if (!empty($label->get('groupId'))) {
            if ($this->isNameExist($label->get('id'), $label->get('name'), 'groupId', $label->get('groupId'))) {
                throw new Error('Name has already been taken for this group');
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
        // get label entity
        $label = $this->getEntity($event);
        // get options
        $options = $this->getOptions($event);

        // auto assign teams
        if (empty($options['skipPMAutoAssignTeam'])) {
            $teamsIds = [];

            foreach (['project', 'group'] as $parentEntityType) {
                if (!empty($parentEntity = $label->get($parentEntityType))) {
                    foreach ($parentEntity->get('teams') as $teamId) {
                        $teamsIds[] = $teamId->get('id');
                    }
                }
            }

            // set all found teams to label
            if (!empty($teamsIds)) {
                $label->set(
                    [
                        'teamsIds' => $teamsIds
                    ]
                );
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
     *
     * @return bool
     */
    protected function isNameExist($id, $name, $parentType, $parentId)
    {
        $labels = $this->getEntityManager()->getRepository('Label')->where(
            [
                'id!='      => $id,
                'name'      => $name,
                $parentType => $parentId
            ]
        )->findOne();

        return (!empty($labels));
    }
}
