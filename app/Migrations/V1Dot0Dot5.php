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
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
    }
}
