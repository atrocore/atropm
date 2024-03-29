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

use Espo\ORM\Entity;
use ProjectManagement\Acl\Milestone as Acl;

/**
 * Class Milestone
 */
class Milestone extends AbstractService
{
    protected $mandatorySelectAttributeList = ['parentId', 'parentType', 'parentName', 'groupId', 'groupName', 'projectId', 'projectName'];

    /**
     * @inheritDoc
     */
    public function createEntity($attachment)
    {
        $this->prepareAttachmentParentForCreate($attachment, 'group', 'Group');
        $this->prepareAttachmentParentForCreate($attachment, 'project', 'Project');

        return parent::createEntity($attachment);
    }

    /**
     * @inheritDoc
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        $this->prepareEntityParentForOutput($entity, 'group', 'Group');
        $this->prepareEntityParentForOutput($entity, 'project', 'Project');

        $entity->set('inProjectTeams', in_array($entity->get('id'), Acl::getMilestoneIdsByIssues($this->getEntityManager(), $this->getUser()->getLinkMultipleIdList('teams'))));
    }

    /**
     * @inheritDoc
     */
    public function isPermittedAssignedUser(Entity $entity)
    {
        return true;
    }
}
