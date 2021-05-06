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

use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;

/**
 * Class AbstractService
 */
abstract class AbstractService extends Base
{
    protected function prepareAttachmentParentForCreate(\stdClass $attachment, string $field, string $type): void
    {
        if (!empty($attachment->parentType) && $attachment->parentType === $type) {
            $id = $field . 'Id';
            $name = $field . 'Name';

            $attachment->$id = $attachment->parentId;
            $attachment->$name = $attachment->parentName;
            unset($attachment->parentId);
            unset($attachment->parentType);
            unset($attachment->parentName);
        }
    }

    protected function prepareEntityParentForOutput(Entity $entity, string $field, string $type): void
    {
        if (!empty($entity->get($field . 'Id'))) {
            $entity->set('parentId', $entity->get($field . 'Id'));
            $entity->set('parentType', $type);
            $entity->set('parentName', $entity->get($field . 'Name'));
        }
    }
}
