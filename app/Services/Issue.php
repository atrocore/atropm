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

namespace ProjectManagement\Services;

use Espo\Core\DataManager;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;

/**
 * Class Issue
 */
class Issue extends Base
{
    protected $mandatorySelectAttributeList = ['assignedUserId', 'assignedUserName'];

    /**
     * @inheritDoc
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        $projectTeamsIds = ['no-such-id'];
        if (!empty($project = $entity->get('project'))) {
            $entity->set('projectAccountId', $project->get('accountId'));
            $projectTeamsIds = array_merge($projectTeamsIds, $project->getLinkMultipleIdList('teams'));
        }
        $entity->set('projectTeamsIds', $projectTeamsIds);

        if (!empty($entity->get('labels'))) {
            $labels = [];
            foreach ($entity->get('labels') as $label) {
                if (in_array($label, $this->getMetadata()->get(['entityDefs', 'Issue', 'fields', 'labels', 'options'], []))) {
                    $labels[] = $label;
                }
            }
            $entity->set('labels', $labels);
        }

        parent::prepareEntityForOutput($entity);
    }

    /**
     * @inheritDoc
     */
    public function isPermittedAssignedUser(Entity $entity)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isPermittedOwnerUser(Entity $entity): bool
    {
        return true;
    }

    protected function isEntityUpdated(Entity $entity, \stdClass $data): bool
    {
        if (property_exists($data, 'beforeIssueId')) {
            return true;
        }

        return parent::isEntityUpdated($entity, $data);
    }

    /**
     * @inheritDoc
     */
    protected function afterCreateEntity(Entity $entity, $data)
    {
        parent::afterCreateEntity($entity, $data);

        $this->refreshIssueTimestamp();
    }

    /**
     * @inheritDoc
     */
    protected function afterCreateProcessDuplicating(Entity $entity, $data)
    {
        parent::afterCreateProcessDuplicating($entity, $data);

        $this->refreshIssueTimestamp();
    }

    /**
     * @inheritDoc
     */
    protected function afterUpdateEntity(Entity $entity, $data)
    {
        if (property_exists($data, 'beforeIssueId')) {
            $this->getEntityManager()->getRepository('Issue')->updatePosition($entity, (string)$data->beforeIssueId);
        }

        parent::afterUpdateEntity($entity, $data);

        $this->refreshIssueTimestamp();
    }

    /**
     * @inheritDoc
     */
    protected function afterMassUpdate(array $idList, $data)
    {
        parent::afterMassUpdate($idList, $data);

        $this->refreshIssueTimestamp();
    }

    /**
     * @inheritDoc
     */
    protected function afterDeleteEntity(Entity $entity)
    {
        parent::afterDeleteEntity($entity);

        $this->refreshIssueTimestamp();
    }

    /**
     * @inheritDoc
     */
    protected function afterMassRemove(array $idList)
    {
        parent::afterMassRemove($idList);

        $this->refreshIssueTimestamp();
    }

    protected function refreshIssueTimestamp(): void
    {
        DataManager::pushPublicData('issuesUpdateTimestamp', (new \DateTime())->getTimestamp());
    }
}
