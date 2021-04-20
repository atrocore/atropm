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

class Project extends \Espo\Core\Templates\Controllers\Base
{
    public function getActionGetMilestonesAndIssues($params, $data, $request)
    {
        $projectId = isset($params['id']) ? $params['id'] : null;

        $list = ['milestones' => [], 'issues' => []];
        if ($projectId) {
            // get all project Issues
            $issues = $this->getEntityManager()->getRepository('Issue')->where([
                'projectId' => $projectId
            ])->find();
            foreach ($issues as $issue) {
                $issueValue = [
                    'type' => $issue->getEntityType(),
                    'entity' => (array) $issue->getValueMap()
                ];

                // get all issue Labels
                $labels = $this->getEntityManager()->getRepository('Issue')->findRelated($issue, 'labels');
                foreach ($labels as $label) {
                    $issueValue['entity']['labelsIds'][] = $label->get('id');
                    $issueValue['entity']['labelsNames'][$label->get('id')] = $label->get('name');
                }

                // get all issue Expenses
                $expenses = $this->getEntityManager()->getRepository('Expense')->where([
                    'parentId' => $issue->get('id'),
                    'parentType' => $issue->getEntityType()
                ])->find();
                $expenseTotal = 0;
                $expenseCurrency = '';
                foreach ($expenses as $expense) {
                    if (!is_null($expenseTotal)) {
                        if (empty($expenseCurrency) || $expenseCurrency == $expense->get('totalCurrency')) {
                            $expenseTotal += $expense->get('total');
                            $expenseCurrency = $expense->get('totalCurrency');
                        } else {
                            $expenseTotal = null;
                            $expenseCurrency = null;
                            break;
                        }
                    }
                }
                $issueValue['entity']['expenses'] = $expenseTotal;
                $issueValue['entity']['expensesCurrency'] = $expenseCurrency;

                // get issue Milestone
                if ($milestoneId = $issue->get('milestoneId') && $milestone =
                        $this->getEntityManager()->getEntity('Milestone', $issue->get('milestoneId')))
                {
                    $milestoneId = $milestone->get('id');
                    if (!isset($list['milestones'][$milestoneId])) {
                        $list['milestones'][$milestoneId] = [
                            'type' => $milestone->getEntityType(),
                            'entity' => (array)$milestone->getValueMap(),
                            'children' => []
                        ];
                        $list['milestones'][$milestoneId]['entity']['expenses'] = 0;
                        $list['milestones'][$milestoneId]['entity']['expensesCurrency'] = '';
                    }

                    if (!is_null($list['milestones'][$milestoneId]['entity']['expenses'])) {
                        if ((empty($list['milestones'][$milestoneId]['entity']['expensesCurrency'])
                                || ($list['milestones'][$milestoneId]['entity']['expensesCurrency'] ==
                                    $issueValue['entity']['expensesCurrency']))
                            && !is_null($issueValue['entity']['expenses']))
                        {
                            $list['milestones'][$milestoneId]['entity']['expenses'] +=
                                $issueValue['entity']['expenses'];
                            $list['milestones'][$milestoneId]['entity']['expensesCurrency'] =
                                $issueValue['entity']['expensesCurrency'];
                        } else {
                            $list['milestones'][$milestoneId]['entity']['expenses'] = null;
                            $list['milestones'][$milestoneId]['entity']['expensesCurrency'] = null;
                        }
                    }
                    $list['milestones'][$milestoneId]['children'][] = $issueValue;
                } else {
                    $list['issues'][$issue->get('id')] = $issueValue;
                }
            }
        }

        $result = [
            'list' => []
        ];
        foreach ($list as $item) {
            foreach ($item as $e) {
                $result['list'][] = $e;
            }
        }

        return json_encode($result);
    }

    public function getActionGetProjectExpenses($params, $data, $request)
    {
        $projectId = isset($params['id']) ? $params['id'] : null;

        $list = [];
        if ($projectId) {
            // get all project Expenses
            $expenses = $this->getEntityManager()->getRepository('Expense')->where([
                'parentId' => $projectId,
                'parentType' => 'Project'
            ])->find();
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

        return json_encode($result);
    }
}
