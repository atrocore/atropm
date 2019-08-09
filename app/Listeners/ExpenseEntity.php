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

/**
 * Class ExpenseEntity
 *
 * @author o.trelin <o.trelin@treolabs.com>
 * @author d.talko <d.talko@treolabs.com>
 *
 * @package ProjectManagement\Listeners
 */
class ExpenseEntity extends AbstractListener
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
     * After save entity listener
     *
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // get expense entity
        $expense = $this->getEntity($event);
        // get options
        $options = $this->getOptions($event);

        // auto assign teams
        if (empty($options['skipPMAutoAssignTeam'])) {
            $teamsIds = [];

            // get teams of parent entity
            if (!empty($expense->get('parentId'))) {
                $parentEntity = $this->getEntityManager()->getEntity(
                    $expense->get('parentType'),
                    $expense->get('parentId')
                );
                foreach ($parentEntity->get('teams') as $teamId) {
                    $teamsIds[] = $teamId->get('id');
                }
            }

            // set all found teams to expense
            if (!empty($teamsIds)) {
                $expense->set([
                    'teamsIds' => $teamsIds
                ]);
                $options['skipPMAutoAssignTeam'] = true;
                $this->getEntityManager()->saveEntity($expense, $options);
            }
        }
    }
}
