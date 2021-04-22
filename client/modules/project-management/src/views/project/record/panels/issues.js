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

Espo.define('project-management:views/project/record/panels/issues', 'project-management:views/record/panels/relationship-hierarchical', function (Dep) {

    return Dep.extend({

        totalDefs: false,

        rowActionsViews: {
            Milestone: false,
            Issue: 'views/record/row-actions/relationship-no-unlink'
        },

        layouts: {
            Milestone: [
                {name: 'name', link: true, view: 'project-management:views/fields/varchar-with-caret'}
            ],
            Issue: [
                {name: 'name', link: true, view: 'project-management:views/fields/varchar-with-caret'}
            ]
        },

        headLayout: [
            {name: 'name'}
        ],

        setup: function () {
            this.wait(true);
            this.ajaxGetRequest(`Issue/layout/listSmall?isAdminPage=false`).then(issue => {
                issue.forEach(row => {
                    this.pushUnique(this.headLayout, row);
                    this.pushUnique(this.layouts.Milestone, row);
                    this.pushUnique(this.layouts.Issue, row);
                });
                this.wait(false);
            });

            Dep.prototype.setup.call(this);
        },

        pushUnique: function (data, row) {
            if (row.name === 'name') {
                return false;
            }

            if (row.name === 'milestone') {
                return false;
            }

            if (row.name === 'project') {
                return false;
            }

            data.forEach(v => {
                if (v.name === row.name) {
                    return false;
                }
            });

            data.push(row);

            return true;
        },

        getCollectionUrl: function () {
            return 'Project/getMilestonesAndIssues/' + this.model.id;
        }

    });

});
