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

namespace ProjectManagement\Services;

use Espo\Core\Utils\Json;
use Espo\Repositories\Attachment;
use Espo\Services\QueueManagerBase;
use Import\Entities\ImportFeed;

/**
 * Class ImportTypeTrello
 */
class ImportTypeTrello extends QueueManagerBase
{
    /**
     * @param ImportFeed $feed
     *
     * @return string
     */
    public function getEntityType(ImportFeed $feed): string
    {
        return 'Issue';
    }

    /**
     * @inheritdoc
     */
    public function run(array $data = []): bool
    {
        /** @var Attachment $attachmentRepository */
        $attachmentRepository = $this->getEntityManager()->getRepository('Attachment');

        $content = $attachmentRepository->getContents($attachmentRepository->get($data['attachmentId']));

        if (empty($content)) {
            return false;
        }

        $content = Json::decode($content, true);

        // create project
        $project = $this->getEntityManager()->getEntity('Project');
        $project->id = 'atrocore';
        $project->set('name', 'AtroCore');
        $project->set('projectType', 'Internal');

        try {
            $this->getEntityManager()->saveEntity($project);
        } catch (\Throwable $e) {
        }

        // create users
        foreach ($content['members'] as $member) {
            $user = $this->getEntityManager()->getEntity('User');
            $user->id = $member['id'];
            $user->set('userName', $member['username']);
            $user->set('salutationName', 'Mr.');
            $user->set('firstName', $member['username']);
            $user->set('lastName', $member['username']);
            $user->set('isActive', true);
            $user->set('password', (new \Espo\Core\Utils\PasswordHash($this->getConfig()))->hash($member['username']));

            try {
                $this->getEntityManager()->saveEntity($user);
            } catch (\Throwable $e) {
            }
        }

        $statusesMapping = [
            'Software Backlog' => 'New',
            'Running'          => 'In Progress',
            'Feedback'         => 'Feedback',
            'To Release'       => 'To Release',
            'Released'         => 'Released',
            'Done'             => 'Done',
        ];

        $statuses = [];

        $lists = [];
        foreach ($content['lists'] as $list) {
            if (empty($list['closed'])) {
                $lists[$list['id']] = $list['name'];
                if (isset($statusesMapping[$list['name']])) {
                    $statuses[$list['id']] = $statusesMapping[$list['name']];
                } else {
                    $label = $this->getEntityManager()->getEntity('Label');
                    $label->id = $list['id'];
                    $label->set('name', $list['name']);
                    $label->set('projectId', 'atrocore');
                    try {
                        $this->getEntityManager()->saveEntity($label);
                    } catch (\Throwable $e) {
                    }
                }
            }
        }

        $trelloLabels = array_column($content['labels'], 'name', 'id');

        foreach ($content['cards'] as $k => $card) {
            if (!empty($card['closed'])) {
                continue 1;
            }

            if (empty($lists[$card['idList']])) {
                continue 1;
            }

            $issue = $this->getEntityManager()->getEntity('Issue');
            $issue->set('name', $card['name']);

            $description = $card['desc'];
            $description .= "\n\nTRELLO URL: " . $card['url'];
            if (!empty($card['attachments'])) {
                foreach ($card['attachments'] as $attachment) {
                    $description .= "\nTRELLO ATTACHMENT: " . $attachment['url'];
                }
            }
            $issue->set('description', $description);

            $issue->set('projectId', 'atrocore');

            if (isset($statuses[$card['idList']])) {
                $issue->set('status', $statuses[$card['idList']]);
                $issue->set('issueType', 'Feature');
            } else {
                $issue->set('labels', [$card['idList']]);
                $issue->set('status', 'New');
                $issue->set('issueType', 'Request');
            }

            if (!empty($card['idLabels'])) {
                $repositories = [];
                foreach ($card['idLabels'] as $idLabel) {
                    $repositories[] = $trelloLabels[$idLabel];
                }
                $issue->set('repositories', $repositories);
            }

            if (!empty($card['pluginData'])) {
                $hours = 0;
                foreach ($card['pluginData'] as $v) {
                    $hours = Json::decode($v['value'], true)['points'];
                }
                $issue->set('estimate', $hours);
            }

            if (!empty($card['idMembers'])) {
                switch ($issue->get('status')) {
                    case 'New':
                    case 'In Progress':
                    case 'Feedback':
                        $issue->set('ownerUserId', $card['idMembers'][0]);
                        $issue->set('assignedUserId', $card['idMembers'][0]);
                        break;
                    case 'To Release':
                        $issue->set('ownerUserId', '5fae90b294cbd18c13f395c4');
                        $issue->set('assignedUserId', '564c9d8fff9ed9c1cd0d8502');
                        break;
                    case 'Released':
                    case 'Done':
                        foreach ($card['idMembers'] as $idMember) {
                            if ($idMember !== '55083dae7040d54e03f21a98') {
                                $issue->set('ownerUserId', $idMember);
                                break;
                            }
                        }
                        $issue->set('assignedUserId', '55083dae7040d54e03f21a98');
                        break;
                }
            }

            try {
                $this->getEntityManager()->saveEntity($issue);

                // create log
                $log = $this->getEntityManager()->getEntity('ImportResultLog');
                $log->set('name', $k);
                $log->set('rowNumber', $k);
                $log->set('entityName', 'Issue');
                $log->set('importResultId', (string)$data['data']['importResultId']);
                $log->set('type', 'create');
                $log->set('entityId', $issue->get('id'));
                $this->getEntityManager()->saveEntity($log);
            } catch (\Throwable $e) {
            }
        }

        return true;
    }
}
