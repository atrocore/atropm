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
 * Migration for version 1.0.3
 */
class V1Dot0Dot3 extends V1Dot0Dot1
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->execute("DROP INDEX IDX_PARENT_GROUP_ID ON `group`");
        $this->execute("ALTER TABLE `group` DROP parent_group_id");
        $this->execute("DROP INDEX IDX_ASSIGNED_USER_ID ON `expense_type`");
        $this->execute("DROP INDEX IDX_ASSIGNED_USER ON `expense_type`");
        $this->execute("ALTER TABLE `expense_type` DROP assigned_user_id");
        $this->execute("DROP INDEX IDX_ASSIGNED_USER_ID ON `label`");
        $this->execute("DROP INDEX IDX_ASSIGNED_USER ON `label`");
        $this->execute("ALTER TABLE `label` DROP assigned_user_id");
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
    }
}
