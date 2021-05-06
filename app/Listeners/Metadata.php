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
use Treo\Core\EventManager\Event;
use Espo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;

/**
 * Class Metadata
 */
class Metadata extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function modify(Event $event)
    {
        // get data
        $data = $event->getArgument('data');

        $labels = $this->getAllLabels();

        $data['entityDefs']['Issue']['fields']['labels']['allLabels'] = $labels;
        foreach ($labels as $label) {
            $data['entityDefs']['Issue']['fields']['labels']['options'][] = $label['id'];
            $data['entityDefs']['Issue']['fields']['labels']['optionColors'][] = $label['backgroundColor'];
        }

        if (isset($data['entityDefs']['ImportFeed']['fields']['type'])) {
            $data['entityDefs']['ImportFeed']['fields']['type']['options'][] = 'Trello';
        }

        // set data
        $event->setArgument('data', $data);
    }

    /**
     * @return array
     */
    protected function getAllLabels(): array
    {
        $cacheFile = 'data/cache/all-labels.json';

        if (file_exists($cacheFile)) {
            return Json::decode(file_get_contents($cacheFile), true);
        }

        try {
            $sth = $this
                ->getContainer()
                ->get('pdo')
                ->prepare(
                    "SELECT id, name, background_color as backgroundColor, project_id as projectId, group_id as groupId FROM label WHERE deleted=0 AND (project_id IS NOT NULL OR group_id IS NOT NULL)"
                );
            $sth->execute();
            $labels = $sth->fetchAll(\PDO::FETCH_ASSOC);
            file_put_contents($cacheFile, Json::encode($labels));
        } catch (\Throwable $e) {
            $labels = [];
        }

        return $labels;
    }
}
