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

Espo.define('project-management:views/issue/detail', 'project-management:views/detail', function (Dep) {

    return Dep.extend({

        setup() {
             Dep.prototype.setup.call(this);

             this.listenTo(this.model, 'after:save', model => {
                 this.model.fetch();
             });
        },

        whereAdditional: {
            'labels': function () {
                return [
                    {
                        type: 'inProjectAndParentGroups',
                        attribute: 'projectId',
                        value: this.model.get('projectId')
                    }
                ];
            }
        }

    });

});
