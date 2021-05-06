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

namespace ProjectManagement\Migrations;

/**
 * Migration for version 1.0.5
 */
class V1Dot0Dot5 extends V1Dot0Dot1
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->execute("DROP INDEX IDX_PARENT ON `milestone`");
        $this->execute("ALTER TABLE `milestone` ADD project_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, DROP parent_type, ADD group_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("CREATE INDEX IDX_PROJECT_ID ON `milestone` (project_id)");
        $this->execute("CREATE INDEX IDX_GROUP_ID ON `milestone` (group_id)");
        $this->execute("ALTER TABLE `milestone` DROP parent_id");

        $this->execute("DROP INDEX IDX_PARENT ON `label`");
        $this->execute("ALTER TABLE `label` ADD project_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, DROP parent_type, ADD group_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("CREATE INDEX IDX_PROJECT_ID ON `label` (project_id)");
        $this->execute("CREATE INDEX IDX_GROUP_ID ON `label` (group_id)");
        $this->execute("ALTER TABLE `label` DROP parent_id");

        $this->execute("DROP INDEX IDX_PARENT ON `expense`");
        $this->execute("ALTER TABLE `expense` ADD project_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, DROP parent_type, ADD group_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ADD issue_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("CREATE INDEX IDX_PROJECT_ID ON `expense` (project_id)");
        $this->execute("CREATE INDEX IDX_GROUP_ID ON `expense` (group_id)");
        $this->execute("CREATE INDEX IDX_ISSUE_ID ON `expense` (issue_id)");
        $this->execute("ALTER TABLE `expense` DROP parent_id");

        $this->execute("DROP INDEX IDX_GROUP_ID ON `expense`");
        $this->execute("ALTER TABLE `expense` ADD milestone_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->execute("CREATE INDEX IDX_MILESTONE_ID ON `expense` (milestone_id)");
        $this->execute("ALTER TABLE `expense` DROP group_id");
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
    }
}
