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

use Espo\ORM\Entity;

/**
 * Class Label
 */
class Label extends AbstractService
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
    }

    /**
     * @inheritDoc
     */
    protected function afterCreateEntity(Entity $entity, $data)
    {
        parent::afterCreateEntity($entity, $data);

        $this->clearCache();
    }

    /**
     * @inheritDoc
     */
    protected function afterCreateProcessDuplicating(Entity $entity, $data)
    {
        parent::afterCreateProcessDuplicating($entity, $data);

        $this->clearCache();
    }

    /**
     * @inheritDoc
     */
    protected function afterUpdateEntity(Entity $entity, $data)
    {
        parent::afterUpdateEntity($entity, $data);

        $this->clearCache();
    }

    /**
     * @inheritDoc
     */
    protected function afterMassUpdate(array $idList, $data)
    {
        parent::afterMassUpdate($idList, $data);

        $this->clearCache();
    }

    /**
     * @inheritDoc
     */
    protected function afterDeleteEntity(Entity $entity)
    {
        parent::afterDeleteEntity($entity);

        $this->clearCache();
    }

    /**
     * @inheritDoc
     */
    protected function afterMassRemove(array $idList)
    {
        parent::afterMassRemove($idList);

        $this->clearCache();
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('dataManager');
    }

    protected function clearCache(): void
    {
        $this->getInjection('dataManager')->clearCache();
    }
}
