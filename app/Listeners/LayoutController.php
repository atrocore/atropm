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
