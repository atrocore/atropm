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

namespace ProjectManagement\Hooks\Expense;

use Espo\Orm\Entity;

class PMExpenseHook extends \Espo\Core\Hooks\Base
{
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
                foreach ($parentEntity->get('teams') as $teamId) {
                    $teamsIds[] = $teamId->get('id');
                }
            }

            // set all found teams to expense
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
}
