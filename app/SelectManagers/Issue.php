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

namespace ProjectManagement\SelectManagers;

use Treo\Core\SelectManagers\Base;

/**
 * Class Issue
 */
class Issue extends Base
{
    /**
     * @inheritDoc
     */
    public function applyAdditional(array &$result, array $params)
    {
        parent::applyAdditional($result, $params);

        foreach ($result['whereClause'] as $v) {
            if (isset($v['archived'])) {
                return;
            }
        }

        $result['whereClause'][] = [
            'archived!=' => true
        ];
    }

    /**
     * @param mixed $result
     */
    protected function boolFilterOnlyOpen(&$result)
    {
        $result['whereClause'][] = [
            'closed!=' => true
        ];
    }

    /**
     * @param mixed $result
     */
    protected function boolFilterOnlyClosed(&$result)
    {
        $result['whereClause'][] = [
            'closed' => true
        ];
    }

    /**
     * @param mixed $result
     */
    protected function boolFilterOnlyArchived(&$result)
    {
        $result['whereClause'][] = [
            'archived' => true
        ];
    }

    /**
     * @inheritDoc
     */
    protected function accessOnlyTeam(&$result)
    {
        $this->setDistinct(true, $result);
        $this->addLeftJoin(['teams', 'teamsAccess'], $result);

        $d = ['teamsAccess.id' => $this->getUser()->getLinkMultipleIdList('teams')];

        $sqlTeamsIds = implode("','", $d['teamsAccess.id']);
        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare(
            "SELECT i.id FROM `issue` AS i LEFT JOIN `project` AS p ON p.id=i.project_id LEFT JOIN `entity_team` AS et ON et.entity_id=p.id WHERE i.deleted=0 AND p.deleted=0 AND et.deleted=0 AND et.entity_type='Project' AND et.team_id IN ('$sqlTeamsIds')"
        );
        $sth->execute();
        $d['id'] = $sth->fetchAll(\PDO::FETCH_COLUMN);

        if ($this->hasOwnerUserField()) {
            $d['ownerUserId'] = $this->getUser()->id;
        }

        if ($this->hasAssignedUserField()) {
            $d['assignedUserId'] = $this->getUser()->id;
        }

        if ($this->hasCreatedByField() && !$this->hasAssignedUserField() && !$this->hasOwnerUserField()) {
            $d['createdById'] = $this->getUser()->id;
        }

        $result['whereClause'][] = ['OR' => $d];
    }

    /**
     * @inheritDoc
     */
    protected function accessPortalOnlyAccount(&$result)
    {
        $d = [];
        $accountIdList = $this->getUser()->getLinkMultipleIdList('accounts');
        if (count($accountIdList)) {
            $d['project.accountId'] = $accountIdList;
        }

        $contactId = $this->getUser()->get('contactId');
        if ($contactId) {
            if ($this->getSeed()->hasAttribute('contactId')) {
                $d['contactId'] = $contactId;
            }
            if ($this->getSeed()->hasRelation('contacts')) {
                $this->addLeftJoin(['contacts', 'contactsAccess'], $result);
                $this->setDistinct(true, $result);
                $d['contactsAccess.id'] = $contactId;
            }
        }

        if ($this->getSeed()->hasAttribute('createdById')) {
            $d['createdById'] = $this->getUser()->id;
        }

        if (!empty($d)) {
            $result['whereClause'][] = [
                'OR' => $d
            ];
        } else {
            $result['whereClause'][] = [
                'id' => null
            ];
        }
    }
}
