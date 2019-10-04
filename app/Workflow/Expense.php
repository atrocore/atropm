<?php
/**
 * Project Management
 * TreoLabs Premium Module
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see <https://treolabs.com/eula>.
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

namespace ProjectManagement\Workflow;

use \Treo\Core\Container;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

/**
 * Class Expense
 *
 * @author o.trelin <o.trelin@treolabs.com>
 * @author d.talko <d.talko@treolabs.com>
 *
 * @package ProjectManagement\Workflow
 */
class Expense
{
    private $container;

    /**
     * Expense constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get Entity Manager
     * @return mixed
     */
    protected function getEntityManager()
    {
        return $this->container->get('entityManager');
    }

    /**
     * Check if all Expenses has the specified status
     * @param string $issueId
     * @param string $status
     * @return bool
     */
    private function isAllExpensesStatusAs($issueId, $status)
    {
        // get Expenses entity
        $expenses = $this->getEntityManager()->getRepository('Expense')->where([
            'parentId' => $issueId,
            'parentType' => 'Issue'
        ])->find();
        foreach ($expenses as $expense) {
            if ($expense->get('status') != $status) {
                return false;
            }
        }

        return true;
    }

    /**
     * Field "Status", transition New->Estimated
     * @param Event $event
     */
    public function statusTransitionFromNewToEstimated(Event $event)
    {
        // get Expense entity
        $expense = $event->getSubject();
        if ($expense->get('parentType') == 'Issue') {
            // get issueId
            $issueId = $expense->get('parentId');
            $status = 'Estimated';

            // if all Expenses has the specified status
            if ($this->isAllExpensesStatusAs($issueId, $status)) {
                // get issue entity
                $issue = $this->getEntityManager()->getEntity('Issue', $issueId);
                // set estimationStatus
                $issue->set([
                    'estimationStatus' => $status
                ]);
                $this->getEntityManager()->saveEntity($issue);
            }
        }
    }
}
