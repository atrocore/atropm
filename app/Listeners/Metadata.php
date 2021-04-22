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
        foreach ($labels as $label){
            $data['entityDefs']['Issue']['fields']['labels']['options'][] = $label['id'];
            $data['entityDefs']['Issue']['fields']['labels']['optionColors'][] = $label['backgroundColor'];
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

        if (file_exists($cacheFile)){
            return Json::decode(file_get_contents($cacheFile), true);
        }

        try {
            $sth = $this
                ->getContainer()
                ->get('pdo')
                ->prepare("SELECT id, name, background_color as backgroundColor, project_id as projectId, group_id as groupId FROM label WHERE deleted=0 AND (project_id IS NOT NULL OR group_id IS NOT NULL)");
            $sth->execute();
            $labels = $sth->fetchAll(\PDO::FETCH_ASSOC);
            file_put_contents($cacheFile, Json::encode($labels));
        } catch (\Throwable $e) {
            $labels = [];
        }

        return $labels;
    }
}
