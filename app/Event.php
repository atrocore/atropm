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

namespace ProjectManagement;

use Treo\Composer\AbstractEvent;

/**
 * Class Event
 *
 * @author o.trelin <o.trelin@treolabs.com>
 */
class Event extends AbstractEvent
{
    /**
     * @var array
     */
    protected $menuItems = [
        'Account',
        'Group',
        'Project',
        'Label',
        'Milestone',
        'Issue',
        'ExpenseType',
        'Expense'
    ];

    /**
     * @inheritdoc
     */
    public function afterInstall(): void
    {
        // add menu items
        $this->addMenuItems();
    }

    /**
     * @inheritdoc
     */
    public function afterDelete(): void
    {
        // delete menu items
        $this->deleteMenuItems();
    }

    /**
     * Add menu items
     */
    protected function addMenuItems()
    {
        // get config
        $config = $this->getContainer()->get('config');

        // get config data
        $tabList = $config->get("tabList", []);
        $quickCreateList = $config->get("quickCreateList", []);
        $twoLevelTabList = $config->get("twoLevelTabList", []);

        foreach ($this->menuItems as $item) {
            if (!in_array($item, $tabList)) {
                $tabList[] = $item;
            }
            if (!in_array($item, $quickCreateList)) {
                $quickCreateList[] = $item;
            }
            if (!in_array($item, $twoLevelTabList)) {
                $twoLevelTabList[] = $item;
            }
        }

        // set to config
        $config->set('tabList', $tabList);
        $config->set('quickCreateList', $quickCreateList);
        $config->set('twoLevelTabList', $twoLevelTabList);

        // save
        $config->save();
    }

    /**
     * Delete menu items
     */
    protected function deleteMenuItems()
    {
        // get config
        $config = $this->getContainer()->get('config');

        // for tabList
        $tabList = [];
        foreach ($config->get("tabList", []) as $entity) {
            if (!in_array($entity, $this->menuItems)) {
                $tabList[] = $entity;
            }
        }
        $config->set('tabList', $tabList);

        // for quickCreateList
        $quickCreateList = [];
        foreach ($config->get("quickCreateList", []) as $entity) {
            if (!in_array($entity, $this->menuItems)) {
                $quickCreateList[] = $entity;
            }
        }
        $config->set('quickCreateList', $quickCreateList);

        // for twoLevelTabList
        $twoLevelTabList = [];
        foreach ($config->get("twoLevelTabList", []) as $entity) {
            if (!in_array($entity, $this->menuItems)) {
                $twoLevelTabList[] = $entity;
            }
        }
        $config->set('twoLevelTabList', $twoLevelTabList);

        // save
        $config->save();
    }
}
