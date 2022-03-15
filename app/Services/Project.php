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
*
* This software is not allowed to be used in Russia and Belarus.
*/

declare(strict_types=1);

namespace ProjectManagement\Services;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;

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

    /**
     * @inheritDoc
     */
    public function isPermittedAssignedUser(Entity $entity)
    {
        return true;
    }
}
