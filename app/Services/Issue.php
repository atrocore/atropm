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

use Espo\Core\DataManager;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;

/**
 * Class Issue
 */
class Issue extends Base
{
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

    protected function isEntityUpdated(Entity $entity, \stdClass $data): bool
    {
        if (property_exists($data, 'beforeIssueId')) {
            return true;
        }

        return parent::isEntityUpdated($entity, $data);
    }

    /**
     * @param Entity $entity
     * @param string $beforeIssueId
     *
     * @throws NotFound
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function updatePosition(Entity $entity, string $beforeIssueId): void
    {
        /** @var \ProjectManagement\Repositories\Issue $repository */
        $repository = $this->getEntityManager()->getRepository('Issue');

        if (empty($beforeIssueId)) {
            $position = 1;
        } else {
            $beforeIssue = $repository->get($beforeIssueId);
            if (empty($beforeIssue)) {
                throw new NotFound();
            }
            $position = (int)$beforeIssue->get('position') + 1;
        }

        $this->updatePositionQuery($position, (string)$entity->get('id'));

        $issues = $repository
            ->select(['id'])
            ->where(['status' => $entity->get('status'), 'position>' => $position - 1, 'id!=' => $entity->get('id')])
            ->order('position')
            ->find();

        foreach ($issues as $issue) {
            $position++;
            $this->updatePositionQuery($position, (string)$issue->get('id'));
        }
    }

    protected function updatePositionQuery(int $position, string $id): void
    {
        $this->getEntityManager()->getPDO()->exec("UPDATE `issue` SET position=$position WHERE id='$id'");
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
            $this->updatePosition($entity, (string)$data->beforeIssueId);
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
