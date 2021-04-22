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

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Services\Base;

/**
 * Class Project
 */
class Project extends Base
{
    public function getActionGetMilestonesAndIssues(string $projectId): array
    {
        $project = $this->getEntityManager()->getEntity('Project', $projectId);

        if (empty($project)) {
            throw new NotFound();
        }

        $result = [
            'list' => []
        ];

        $list = [
            'milestones' => [],
            'issues'     => []
        ];

        // get all project Issues
        $issues = $this->findLinkedEntities($projectId, 'issues', []);

        if ($issues['total'] == 0) {
            return $result;
        }

        foreach ($issues['collection'] as $issue) {
            $issueValue = [
                'type'   => $issue->getEntityType(),
                'entity' => (array)$issue->getValueMap()
            ];

            // get all issue Expenses
//            $expenses = $issue->get('expenses');
            $expenseTotal = 0;
            $expenseCurrency = '';
//            foreach ($expenses as $expense) {
//                if (!is_null($expenseTotal)) {
//                    if (empty($expenseCurrency) || $expenseCurrency == $expense->get('totalCurrency')) {
//                        $expenseTotal += $expense->get('total');
//                        $expenseCurrency = $expense->get('totalCurrency');
//                    } else {
//                        $expenseTotal = null;
//                        $expenseCurrency = null;
//                        break;
//                    }
//                }
//            }
            $issueValue['entity']['expenses'] = $expenseTotal;
            $issueValue['entity']['expensesCurrency'] = $expenseCurrency;

            // get issue Milestone
            if ($milestone = $issue->get('milestone')) {
                $milestoneId = $milestone->get('id');
                if (!isset($list['milestones'][$milestoneId])) {
                    $list['milestones'][$milestoneId] = [
                        'type'     => $milestone->getEntityType(),
                        'entity'   => (array)$milestone->getValueMap(),
                        'children' => []
                    ];
                    $list['milestones'][$milestoneId]['entity']['expenses'] = 0;
                    $list['milestones'][$milestoneId]['entity']['expensesCurrency'] = '';
                }

                if (!is_null($list['milestones'][$milestoneId]['entity']['expenses'])) {
                    if ((empty($list['milestones'][$milestoneId]['entity']['expensesCurrency'])
                            || ($list['milestones'][$milestoneId]['entity']['expensesCurrency'] ==
                                $issueValue['entity']['expensesCurrency']))
                        && !is_null($issueValue['entity']['expenses'])) {
                        $list['milestones'][$milestoneId]['entity']['expenses']
                            += $issueValue['entity']['expenses'];
                        $list['milestones'][$milestoneId]['entity']['expensesCurrency']
                            = $issueValue['entity']['expensesCurrency'];
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


        foreach ($list as $item) {
            foreach ($item as $e) {
                $result['list'][] = $e;
            }
        }

        return $result;
    }
}
