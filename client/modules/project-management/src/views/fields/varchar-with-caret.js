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

Espo.define('project-management:views/fields/varchar-with-caret', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        listLinkTemplate: 'project-management:fields/varchar-with-caret/list-link',

        marginLeftStep: 10,

        hidden: false,

        events: _.extend({
            'click .action[data-action="toggleCaret"]': function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.actionToggleCaret();
            }
        }, Dep.prototype.events),

        data: function () {
            return _.extend({
                marginLeft: (this.model.level || 0) * this.marginLeftStep,
                showCaret: !!this.model.children,
                hidden: this.hidden
            }, Dep.prototype.data.call(this));
        },

        actionToggleCaret: function () {
            this.hidden = !this.hidden;
            this.model.trigger('list-hierarchical-hide-children', this.hidden);
            this.reRender();
        }

    });

});
