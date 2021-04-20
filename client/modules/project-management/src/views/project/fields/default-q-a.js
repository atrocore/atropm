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

Espo.define('project-management:views/project/fields/default-q-a', 'project-management:views/fields/link', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:teamsIds', function (model, changes, params) {
                if ((params || {}).ui) {
                    var data = {};
                    data[this.idName] = null;
                    data[this.nameName] = null;
                    this.model.set(data);
                }
            }, this);
        },

        getWhereAdditional: function () {
            var teamsIds = Espo.Utils.clone(this.model.get('teamsIds'));
            var value = null;
            if (Array.isArray(teamsIds) && teamsIds.length) {
                value = Espo.Utils.clone(this.model.get('teamsIds'));
            } else {
                value = [''];
            }
            return [
                {
                    type: 'linkedWith',
                    attribute: 'teams',
                    value: value
                }
            ];
        }

    });

});
