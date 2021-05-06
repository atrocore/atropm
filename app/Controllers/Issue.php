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

namespace ProjectManagement\Controllers;

class Issue extends \Espo\Core\Templates\Controllers\Base
{
    public function getActionGetIssueExpenses($params, $data, $request)
    {
        $issueId = isset($params['id']) ? $params['id'] : null;

        $list = [];
        if ($issueId) {
            // get all issue Expenses
            $expenses = $this->getEntityManager()->getRepository('Expense')->where(['issueId' => $issueId])->find();
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
