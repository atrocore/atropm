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

Espo.define('project-management:views/milestone/record/panels/expenses', 'project-management:views/record/panels/bottom-hierarchical', function (Dep) {

    return Dep.extend({

        totalDefs: {
            type: 'float',
            measureId: 'currency'
        },

        rowActionsViews: {
            Issue: 'views/record/row-actions/relationship-no-unlink',
            Expense: 'views/record/row-actions/relationship-no-unlink'
        },

        layouts: {
            Issue: [
                {name: 'name', link: true, view: 'project-management:views/fields/varchar-with-caret'},
                {name: 'expenseType'},
                {name: 'status'},
                {name: 'labels'},
                {name: 'expenses', type: 'float', measureId: 'currency'}
            ],
            Expense: [
                {name: 'name', link: true, view: 'project-management:views/fields/varchar-with-caret'},
                {name: 'expenseType'},
                {name: 'status'},
                {name: 'description'},
                {name: 'total', type: 'float', measureId: 'currency'}
            ]
        },

        headLayout: [
            {name: 'name'},
            {name: 'expenseType'},
            {name: 'status'},
            {name: 'labelsOrDescription'},
            {name: 'total'}
        ],

        scope: 'Expense',

        prepareHeadLayout: function () {
            this.headLayout.forEach(function (col) {
                col.customLabel = this.translate(col.name, 'expensesPanel', this.model.name);
            }, this);
        },

        getCollectionUrl: function () {
            return 'Milestone/getIssuesAndExpenses/' + this.model.id;
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.buttonList.push(
                {
                    "title": "Create",
                    "action": "createRelatedExpense",
                    "acl": "create",
                    "aclScope": "Expense",
                    "html": "<span class=\"fas fa-plus\"></span>"
                }
            );
        },

        getTotalLabel: function (value) {
            return '<div style="margin-right: 55px" class="total-container pull-right">' + this.translate('sum', 'labels', this.model.name) + ':' + value + '</div>';
        }

    });

});
