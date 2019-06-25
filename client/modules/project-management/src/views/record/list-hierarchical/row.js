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

Espo.define('project-management:views/record/list-hierarchical/row', 'view', function (Dep) {

    return Dep.extend({

        template: 'project-management:record/list-hierarchical/row',

        rowLayout: [],

        setup: function () {
            Dep.prototype.setup.call(this);

            this.rowLayout = this.options.rowLayout || this.rowLayout;
        },

        data: function () {
            return {
                rowLayout: this.rowLayout
            };
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.rowLayout.forEach(function (field) {
                var type = field.type || this.model.getFieldType(field.name) || 'base';
                var view = field.view || this.model.getFieldParam(field.name, 'view') || this.getFieldManager().getViewName(type);
                this.createView(field.name, view, {
                    el: this.options.el + ' .cell[data-name="' + field.name + '"]',
                    model: this.model,
                    mode: field.link ? 'listLink' : 'list',
                    defs: {
                        name: field.name
                    },
                    acl: {
                        edit: this.getAcl().checkModel(this.model, 'edit'),
                        delete: this.getAcl().checkModel(this.model, 'delete')
                    }
                }, function (view) {
                    view.render();
                });
            }, this);
        }

    });

});
