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
     * Field "Status", entered to Done
     * @param Event $event
     */
    public function statusEnteredToDone(Event $event)
    {
        $issue = $event->getSubject();
        $this->closeIssue($issue->id);

        // get expenses with parentType = Issue and parentId
        $expenses = $this->getEntityManager()->getRepository('Expense')->where([
            'parentType' => 'Issue',
            'parentId' => $issue->id
        ])->find();

        foreach ($expenses as $expense) {
            // set status Realized
            $expense->set([
                'status' => 'Realized'
            ]);
            $this->getEntityManager()->saveEntity($expense);
        }

        // checking if install module Sales
        if ($this->checkInstallModuleSales()) {
            // get Customer Order Items entity
            $customerOrderItems = $this->getEntityManager()->getRepository('CustomerOrderItem')->where([
                'parentType' => 'Issue',
                'parentId' => $issue->id
            ])->find();
            foreach ($customerOrderItems as $customerOrderItem) {
                // set status Delivered
                $customerOrderItem->set([
                    'status' => 'Delivered'
                ]);
                $this->getEntityManager()->saveEntity($customerOrderItem);
            }
        }
    }

    /**
     * Field "Status", entered to Frozen
     * @param Event $event
     */
    public function statusEnteredToFrozen(Event $event)
    {
        $this->closeIssue($event->getSubject()->id);
    }

    /**
     * Field "Status", entered to Rejected
     * @param Event $event
     */
    public function statusEnteredToRejected(Event $event)
    {
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
        // set state closed
        $issue->set([
            'state' => 'closed'
        ]);
        $this->getEntityManager()->saveEntity($issue);
    }

    /**
     * Guard event for field "Status"
     * @param Event $event
     */
    public function guardStatus(Event $event)
    {
        // get Issue entity
        $issue = $event->getSubject();
        $transitionTos = $event->getTransition()->getTos();
        foreach ($transitionTos as $to) {
            switch ($to) {
                case 'ToDo':
                    if ($issue->getFetched('status') == 'New') {
                        // can't set Status to "ToDo" if Approval Status is not "Approved"
                        if ($issue->get('approvalStatus') != 'Approved') {
                            $event->addTransitionBlocker(
                                new TransitionBlocker(
                                    'The value of field "Approval Status" for Issue is not "Approved".',
                                    '404'
                                )
                            );
                        }
                    }
                    break;

                default:
                    break;
            }
        }
    }

    /**
     * Guard event for field "Approval Status"
     * @param Event $event
     */
    public function guardApprovalStatus(Event $event)
    {
        // checking if install module Sales
        if ($this->checkInstallModuleSales()) {
            // get Issue entity
            $issue = $event->getSubject();
            $transitionTos = $event->getTransition()->getTos();
            foreach ($transitionTos as $to) {
                switch ($to) {
                    case 'To Approve':
                        if ($issue->getFetched('approvalStatus') == 'New') {
                            // get Customer Order Items entity
                            $customerOrderItems = $this->getEntityManager()->getRepository('CustomerOrderItem')->where([
                                'parentType' => 'Issue',
                                'parentId' => $issue->id
                            ])->find();
                            foreach ($customerOrderItems as $customerOrderItem) {
                                // can't set Approval Status to "To Approve" if Customer Order Items Status is not "To Approve"
                                if ($customerOrderItem->get('status') != 'To Approve') {
                                    $event->addTransitionBlocker(
                                        new TransitionBlocker(
                                            'Cannot set Approval Status `To Approve`.'
                                            . ' Status in Customer Order Items must by `To Approve`',
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

    /**
     * Field "Status", transition New->ToDo
     * @param Event $event
     */
    public function approvalStatusTransitionFromNewToToDo(Event $event)
    {
        // checking if install module Sales
        if ($this->checkInstallModuleSales()) {
            // get Issue entity
            $issue = $event->getSubject();
            // get Customer Order Items entity
            $customerOrderItems = $this->getEntityManager()->getRepository('CustomerOrderItem')->where([
                'parentType' => 'Issue',
                'parentId' => $issue->id
            ])->find();
            foreach ($customerOrderItems as $customerOrderItem) {
                // set status Accepted
                $customerOrderItem->set([
                    'status' => 'Accepted'
                ]);
                $this->getEntityManager()->saveEntity($customerOrderItem);
            }
        }
    }

    /**
     * Field "Approval Status", transition ToApprove->Approved
     * @param Event $event
     */
    public function approvalStatusTransitionFromToApproveToApproved(Event $event)
    {
        // checking if install module Sales
        if ($this->checkInstallModuleSales()) {
            // get Issue entity
            $issue = $event->getSubject();
            // get Customer Order Items entity
            $customerOrderItems = $this->getEntityManager()->getRepository('CustomerOrderItem')->where([
                'parentType' => 'Issue',
                'parentId' => $issue->id
            ])->find();
            foreach ($customerOrderItems as $customerOrderItem) {
                // set status Approved
                $customerOrderItem->set([
                    'status' => 'Approved'
                ]);
                $this->getEntityManager()->saveEntity($customerOrderItem);
            }
        }
    }

    /**
     * Check if install module Sales
     * @return bool
     */
    public function checkInstallModuleSales()
    {
        $metadata = new \ProjectManagement\Utils\Metadata(
            $this->container->get('fileManager'),
            $this->container->get('moduleManager'),
            $this->container->get('eventManager')
        );

        return $metadata->isModuleInstalled('Sales');
    }
}
