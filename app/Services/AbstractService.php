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
