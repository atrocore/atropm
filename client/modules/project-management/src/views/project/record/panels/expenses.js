/**
 * Project Management
 * TreoLabs Premium Module
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see <https://treolabs.com/eula>.
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

Espo.define('project-management:views/project/record/panels/expenses', 'project-management:views/record/panels/bottom-hierarchical', function (Dep) {

    return Dep.extend({

        totalDefs: {
            type: 'currency'
        },

        rowActionsViews: {
            Expense: 'views/record/row-actions/relationship-no-unlink'
        },

        layouts: {
            Expense: [
                {name: 'name', link: true},
                {name: 'parent'},
                {name: 'expenseType'},
                {name: 'total'},
                {name: 'assignedUser'},
                {name: 'dateCompleted'}
            ]
        },

        headLayout: [
            {name: 'name'},
            {name: 'parent'},
            {name: 'expenseType'},
            {name: 'total'},
            {name: 'assignedUser'},
            {name: 'dateCompleted'}
        ],

        scope: 'Expense',

        getCollectionUrl: function () {
            return 'Project/getProjectExpenses/' + this.model.id;
        },

        getTotalLabel: function (value) {
            return '<div class="total-container pull-right">' + this.translate('sum', 'labels', this.model.name) + ':' + value + '</div>';
        }

    });

});
