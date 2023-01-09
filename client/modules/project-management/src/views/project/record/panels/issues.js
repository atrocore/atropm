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

Espo.define('project-management:views/project/record/panels/issues', 'project-management:views/record/panels/relationship-hierarchical', function (Dep) {

    return Dep.extend({

        totalDefs: false,

        rowActionsViews: {
            Milestone: false,
            Issue: 'views/record/row-actions/relationship-no-unlink'
        },

        layouts: null,

        headLayout: null,

        setup: function () {
            this.wait(true);

            this.layouts = {
                Milestone: [
                    {
                        name: 'name',
                        link: true,
                        "width": '40%',
                        view: 'project-management:views/fields/varchar-with-caret'
                    }
                ],
                Issue: [
                    {
                        name: 'name',
                        link: true,
                        "width": '40%',
                        view: 'project-management:views/fields/varchar-with-caret'
                    }
                ]
            };
            this.headLayout = [{name: 'name', "width": '40%'}];

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
