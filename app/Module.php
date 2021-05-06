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

namespace ProjectManagement;

use Espo\Core\Utils\Json;
use Treo\Core\Loaders\Layout;
use Treo\Core\ModuleManager\AbstractModule;

/**
 * Class Module
 */
class Module extends AbstractModule
{
    /**
     * @inheritdoc
     */
    public static function getLoadOrder(): int
    {
        return 5000;
    }

    /**
     * @inheritdoc
     */
    public function loadLayouts(string $scope, string $name, array &$data)
    {
        // load layout class
        $layout = (new Layout($this->container))->load();

        // prepare file path
        $filePath = $layout->concatPath($this->path . 'app/Resources/layouts', $scope);
        $fileFullPath = $layout->concatPath($filePath, $name . '.json');

        if (file_exists($fileFullPath)) {
            // get file data
            $fileDataJson = $this->container->get('fileManager')->getContents($fileFullPath);
            $fileDataArr = Json::decode($fileDataJson, true);

            // prepare data
            switch ($name) {
                case 'filters':
                case 'massUpdate':
                case 'relationships':
                    // add only new fields
                    foreach ($fileDataArr as $field) {
                        if (!in_array($field, $data)) {
                            $data[] = $field;
                        }
                    }
                    break;

                case 'list':
                case 'listSmall':
                    // merge fields (by "name" parameter)
                    $dataKeys = array_column($data, 'name');
                    $dataAssoc = array_combine($dataKeys, $data);
                    $fileDataKeys = array_column($fileDataArr, 'name');
                    $fileDataAssoc = array_combine($fileDataKeys, $fileDataArr);
                    $data = array_values(array_merge($dataAssoc, $fileDataAssoc));
                    break;

                case 'detail':
                case 'detailSmall':
                    // add only new panels (by "label" parameter)
                    $dataKeys = array_column($data, 'label');
                    foreach ($fileDataArr as $panel) {
                        if (!in_array($panel['label'], $dataKeys)) {
                            $data[] = $panel;
                        }
                    }
                    break;

                default:
                    $data = array_merge_recursive($data, $fileDataArr);
            }
        }
    }
}
