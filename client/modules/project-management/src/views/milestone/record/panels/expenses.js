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

Espo.define('project-management:views/milestone/record/panels/expenses', 'project-management:views/record/panels/bottom-hierarchical', function (Dep) {

    return Dep.extend({

        totalDefs: {
            type: 'currency'
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
                {name: 'expenses', type: 'currency'}
            ],
            Expense: [
                {name: 'name', link: true, view: 'project-management:views/fields/varchar-with-caret'},
                {name: 'expenseType'},
                {name: 'status'},
                {name: 'description'},
                {name: 'total', type: 'currency'}
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

        getTotalLabel: function (value) {
            return '<div class="total-container pull-right">' + this.translate('sum', 'labels', this.model.name) + ':' + value + '</div>';
        }

    });

});
