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

class Milestone extends \Espo\Core\Templates\Controllers\Base
{
    public function getActionGetIssuesAndExpenses($params, $data, $request)
    {
        $milestoneId = isset($params['id']) ? $params['id'] : null;

        $list = [];
        if ($milestoneId) {
            // get all milestone Expenses
            $expenses = $this->getEntityManager()->getRepository('Expense')->where([
                'parentId' => $milestoneId,
                'parentType' => 'Milestone'
            ])->find();
            foreach ($expenses as $expense) {
                $expenseItem = [
                    'type' => $expense->getEntityType(),
                    'entity' => (array)$expense->getValueMap(),
                    'children' => []
                ];
                $expenseItem['entity']['expenses'] = $expense->get('total');
                $expenseItem['entity']['expensesCurrency'] = $expense->get('totalCurrency');

                $list[] = $expenseItem;
            }

            // get all milestone Issues
            $issues = $this->getEntityManager()->getRepository('Issue')->where([
                'milestoneId' => $milestoneId
            ])->find();
            foreach ($issues as $issue) {
                $issueItem = [
                    'type' => $issue->getEntityType(),
                    'entity' => (array) $issue->getValueMap(),
                    'children' => []
                ];

                // get all issue Labels
                $labels = $this->getEntityManager()->getRepository('Issue')->findRelated($issue, 'labels');
                foreach ($labels as $label) {
                    $issueItem['entity']['labelsIds'][] = $label->get('id');
                    $issueItem['entity']['labelsNames'][$label->get('id')] = $label->get('name');
                }

                // get all issue Expenses
                $expenses = $this->getEntityManager()->getRepository('Expense')->where([
                    'parentId' => $issue->get('id'),
                    'parentType' => $issue->getEntityType()
                ])->find();
                $expenseTotal = 0;
                $expenseCurrency = '';
                foreach ($expenses as $expense) {
                    $issueItem['children'][] = [
                        'type' => $expense->getEntityType(),
                        'entity' => (array) $expense->getValueMap()
                    ];
                    if (!is_null($expenseTotal)) {
                        if (empty($expenseCurrency) || $expenseCurrency == $expense->get('totalCurrency')) {
                            $expenseTotal += $expense->get('total');
                            $expenseCurrency = $expense->get('totalCurrency');
                        } else {
                            $expenseTotal = null;
                            $expenseCurrency = null;
                        }
                    }
                }
                $issueItem['entity']['expenses'] = $expenseTotal;
                $issueItem['entity']['expensesCurrency'] = $expenseCurrency;

                $list[] = $issueItem;
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

        return json_encode($result);
    }
}
