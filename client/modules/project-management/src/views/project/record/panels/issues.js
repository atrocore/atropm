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

Espo.define('project-management:views/project/record/panels/issues', 'project-management:views/record/panels/relationship-hierarchical', function (Dep) {

    return Dep.extend({

        totalDefs: false,

        rowActionsViews: {
            Milestone: false,
            Issue: 'views/record/row-actions/relationship-no-unlink'
        },

        layouts: {
            Milestone: [
                {name: 'name', link: true, view: 'project-management:views/fields/varchar-with-caret'},
                {name: 'status'},
                {name: 'labels'},
                {name: 'expenses', type: 'currency'},
                {name: 'assignedUser'}
            ],
            Issue: [
                {name: 'name', link: true, view: 'project-management:views/fields/varchar-with-caret'},
                {name: 'status'},
                {name: 'labels'},
                {name: 'expenses', type: 'currency'},
                {name: 'assignedUser'}
            ]
        },

        headLayout: [
            {name: 'name'},
            {name: 'status'},
            {name: 'labels'},
            {name: 'expenses'},
            {name: 'assignedUser'}
        ],

        getCollectionUrl: function () {
            return 'Project/getMilestonesAndIssues/' + this.model.id;
        }

    });

});
