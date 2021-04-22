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

namespace ProjectManagement\Controllers;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Templates\Controllers\Base;

/**
 * Class Project
 */
class Project extends Base
{
    /**
     * @param mixed $params
     * @param mixed $data
     * @param mixed $request
     *
     * @return array
     * @throws BadRequest
     * @throws Forbidden
     */
    public function getActionGetMilestonesAndIssues($params, $data, $request): array
    {
        if (!$request->isGet() || empty($params['id'])) {
            throw new BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'create')) {
            throw new Forbidden();
        }

        return $this
            ->getRecordService()
            ->getActionGetMilestonesAndIssues((string)$params['id']);
    }

    /**
     * @param mixed $params
     * @param mixed $data
     * @param mixed $request
     *
     * @return array
     */
    public function getActionGetProjectExpenses($params, $data, $request): array
    {
        $projectId = isset($params['id']) ? $params['id'] : null;

        $list = [];
        if ($projectId && !empty($project = $this->getEntityManager()->getEntity('Project', $projectId))) {
            // get all project Expenses
            $expenses = $project->get('expenses');
            $service = $this->getRecordService();
            foreach ($expenses as $expense) {
                $service->loadAdditionalFieldsForList($expense);
                $expenseItem = [
                    'type' => $expense->getEntityType(),
                    'entity' => (array)$expense->getValueMap(),
                    'children' => []
                ];
                $expenseItem['entity']['expenses'] = $expense->get('total');
                $expenseItem['entity']['expensesCurrency'] = $expense->get('totalCurrency');

                $list[] = $expenseItem;
            }
        }

        $result = [
            'total' => 0,
            'totalCurrency' => '',
            'list' => []
        ];
        foreach ($list as $e) {
            if (!is_null($result['total'])) {
                if ((empty($result['totalCurrency']) || ($result['totalCurrency'] == $e['entity']['expensesCurrency']))
                    && !is_null($e['entity']['expenses']))
                {
                    $result['total'] += $e['entity']['expenses'];
                    $result['totalCurrency'] = $e['entity']['expensesCurrency'];
                } else {
                    $result['total'] = null;
                    $result['totalCurrency'] = null;
                }
            }
            $result['list'][] = $e;
        }

        return $result;
    }
}
