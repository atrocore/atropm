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

namespace ProjectManagement\Listeners;

use Espo\Core\Utils\Json;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class LayoutController
 */
class LayoutController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterActionRead(Event $event)
    {
        /** @var string $scope */
        $scope = $event->getArgument('params')['scope'];

        /** @var string $name */
        $name = $event->getArgument('params')['name'];

        /** @var bool $isAdminPage */
        $isAdminPage = $event->getArgument('request')->get('isAdminPage') === 'true';

        $method = 'modify' . $scope . ucfirst($name);
        $methodAdmin = $method . 'Admin';

        if (!$isAdminPage && method_exists($this, $method)) {
            $this->{$method}($event);
        } else {
            if ($isAdminPage && method_exists($this, $methodAdmin)) {
                $this->{$methodAdmin}($event);
            }
        }
    }

    protected function modifyLabelDetail(Event $event): void
    {
        if (!$this->isPortalUser()) {
            return;
        }

        $result = Json::decode($event->getArgument('result'), true);

        foreach ($result as $kp => $panel) {
            if (!isset($panel['rows'])) {
                continue 1;
            }
            foreach ($panel['rows'] as $kr => $row) {
                if (empty($row)) {
                    continue 1;
                }
                foreach ($row as $k => $cell) {
                    if (isset($cell['name']) && $cell['name'] == 'parent') {
                        $result[$kp]['rows'][$kr][$k]['name'] = 'project';
                    }
                }
            }
        }

        $event->setArgument('result', Json::encode($result));
    }

    protected function modifyLabelDetailSmall(Event $event): void
    {
        $this->modifyLabelDetail($event);
    }

    protected function modifyLabelList(Event $event): void
    {
        if (!$this->isPortalUser()) {
            return;
        }

        $data = Json::decode($event->getArgument('result'), true);
        foreach ($data as $k => $row) {
            if (isset($row['name']) && $row['name'] == 'parent') {
                $data[$k]['name'] = 'project';
            }
        }

        $event->setArgument('result', Json::encode($data));
    }

    protected function modifyLabelListSmall(Event $event): void
    {
        $this->modifyLabelList($event);
    }

    protected function isPortalUser(): bool
    {
        return $this->getContainer()->get('user')->isPortalUser();
    }
}
