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
use Treo\Core\Utils\Metadata;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

/**
 * Class Issue
 *
 * @author o.trelin <o.trelin@treolabs.com>
 * @author d.talko <d.talko@treolabs.com>
 *
 * @package ProjectManagement\Workflow
 */
class Issue
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
     * Get Metadata
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    /**
     * Change related Customer Order Items' status
     * @param string $issueId
     * @param string $status
     * @param array $notAffectedStatus
     */
    private function changeCustomerOrderItemsStatus($issueId, $status, $notAffectedStatus = [])
    {
        // if module "Sales" is installed
        if ($this->getMetadata()->isModuleInstalled('Sales')) {
            // get Customer Order Items
            $customerOrderItems = $this->getEntityManager()->getRepository('CustomerOrderItem')->where([
                'parentType' => 'Issue',
                'parentId' => $issueId,
                'status !=' => array_merge(['Canceled'], $notAffectedStatus)
            ])->find();
            foreach ($customerOrderItems as $customerOrderItem) {
                // set status Accepted
                $customerOrderItem->set(['status' => $status]);
                $this->getEntityManager()->saveEntity($customerOrderItem);
            }
        }
    }

    /**
     * Field "Status", transition New->ToDo
     * @param Event $event
     */
    public function statusTransitionFromNewToToDo(Event $event)
    {
        // get Issue entity
        $issue = $event->getSubject();

        // change Customer Order Items' status
        $this->changeCustomerOrderItemsStatus($issue->id, 'Accepted');
    }

    /**
     * Field "Status", entered to Done
     * @param Event $event
     */
    public function statusEnteredToDone(Event $event)
    {
        // get Issue entity
        $issue = $event->getSubject();

        // close Issue
        $this->closeIssue($issue->id);

        // get related Expenses which is not Realized
        $expenses = $this->getEntityManager()->getRepository('Expense')->where([
            'parentType' => 'Issue',
            'parentId' => $issue->id,
            'status!=' => 'Realized'
        ])->find();
        foreach ($expenses as $expense) {
            // set Status to "Realized"
            $expense->set(['status' => 'Realized']);
            $this->getEntityManager()->saveEntity($expense);
        }

        // change Customer Order Items' status
        $this->changeCustomerOrderItemsStatus($issue->id, 'Delivered');
    }

    /**
     * Field "Status", entered to Frozen
     * @param Event $event
     */
    public function statusEnteredToFrozen(Event $event)
    {
        // close Issue
        $this->closeIssue($event->getSubject()->id);
    }

    /**
     * Field "Status", entered to Rejected
     * @param Event $event
     */
    public function statusEnteredToRejected(Event $event)
    {
        // close Issue
        $this->closeIssue($event->getSubject()->id);
    }

    /**
     * Close Issue
     * @param string $issueId
     */
    public function closeIssue($issueId)
    {
        // get Issue entity
        $issue = $this->getEntityManager()->getEntity('Issue', $issueId);

        // set State to "closed"
        $issue->set(['state' => 'closed']);
        $this->getEntityManager()->saveEntity($issue);
    }

    /**
     * Guard event for field "Status"
     * @param GuardEvent $event
     */
    public function guardStatus(GuardEvent $event)
    {
        // get Issue entity
        $issue = $event->getSubject();
        $transitionTos = $event->getTransition()->getTos();
        foreach ($transitionTos as $to) {
            switch ($to) {
                case 'To Do':
                    // can't set Status to "To Do" if Approval Status is not "Approved"
                    if ($issue->get('approvalStatus') != 'Approved') {
                        $event->addTransitionBlocker(
                            new TransitionBlocker(
                                'The value of field "Approval Status" for Issue is not "Approved".',
                                '404'
                            )
                        );
                    }
                    break;

                default:
                    break;
            }
        }
    }

    /**
     * Field "Approval Status", transition ToApprove->Approved
     * @param Event $event
     */
    public function approvalStatusTransitionFromToApproveToApproved(Event $event)
    {
        // get Issue entity
        $issue = $event->getSubject();

        // change Customer Order Items' status
        $this->changeCustomerOrderItemsStatus($issue->id, 'Approved');
    }

    /**
     * Field "Approval Status", transition Approved->ToApprove
     * @param Event $event
     */
    public function approvalStatusTransitionFromApprovedToToApprove(Event $event)
    {
        // get Issue entity
        $issue = $event->getSubject();

        // change Customer Order Items' status
        $this->changeCustomerOrderItemsStatus($issue->id, 'To Approve');
    }

    /**
     * Guard event for field "Approval Status"
     * @param GuardEvent $event
     */
    public function guardApprovalStatus(GuardEvent $event)
    {
        // if module "Sales" is installed
        if ($this->getMetadata()->isModuleInstalled('Sales')) {
            // get Issue entity
            $issue = $event->getSubject();
            $transitionTos = $event->getTransition()->getTos();
            foreach ($transitionTos as $to) {
                switch ($to) {
                    case 'To Approve':
                        if ($issue->getFetched('approvalStatus') == 'New') {
                            // get Customer Order Item
                            $customerOrderItems = $this->getEntityManager()->getRepository('CustomerOrderItem')->where([
                                'parentType' => 'Issue',
                                'parentId' => $issue->id
                            ])->find();
                            foreach ($customerOrderItems as $customerOrderItem) {
                                // can't set Approval Status to "To Approve" if Customer Order Item's Status is not "To Approve"
                                if ($customerOrderItem->get('status') != 'To Approve') {
                                    $event->addTransitionBlocker(
                                        new TransitionBlocker(
                                            'The value of field "Status" for related Customer Order Item is not "To Approve".',
                                            '404'
                                        )
                                    );
                                }
                            }
                        }
                        break;

                    default:
                        break;
                }
            }
        }
    }
}
