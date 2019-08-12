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

Espo.define('project-management:views/record/list-hierarchical', ['view', 'views/record/list'], function (Dep, List) {

    return Dep.extend({

        template: 'project-management:record/list-hierarchical',

        rowActionsViews: {},

        layouts: {},

        headLayout: [],

        scope: null,

        rowsDefs: [],

        rowView: 'project-management:views/record/list-hierarchical/row',

        events: {
            'click .action': function (e) {
                var $el = $(e.currentTarget);
                var action = $el.data('action');
                var method = 'action' + Espo.Utils.upperCaseFirst(action);
                if (typeof this[method] == 'function') {
                    var data = $el.data();
                    this[method](data, e);
                    e.preventDefault();
                }
            },
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.scope = this.options.scope || this.scope;
            this.rowActionsViews = this.options.rowActionsViews || this.rowActionsViews;
            this.layouts = this.options.layouts || this.layouts;
            this.headLayout = this.options.headLayout || this.headLayout;
            this.rowView = this.options.rowView || this.rowView;

            this.prepareLayouts();
            this.prepareRowsDefs();
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.buildRows();
        },

        data: function () {
            return {
                scope: this.scope,
                headLayout: this.headLayout,
                rowsDefs: this.rowsDefs,
                noData: !this.collection.length
            };
        },

        prepareLayouts: function () {
            this.headLayout.push({width: 25});

            for (var key in this.layouts) {
                if (this.layouts.hasOwnProperty(key)) {
                    this.layouts[key].push({
                        name: 'buttons',
                        width: 25,
                        view: this.rowActionsViews[key] || false
                    });
                }
            }
        },

        prepareRowsDefs: function () {
            this.rowsDefs = this.getRowsDefsFromCollection(this.collection);
        },

        getRowsDefsFromCollection: function (collection) {
            var defs = [];
            collection.forEach(function (model) {
                defs.push({id: model.id, type: model.name});
            });
            return defs;
        },

        buildRows: function () {
            this.buildRowsFromCollection(this.collection);
        },

        buildRowsFromCollection: function (collection) {
            collection.forEach(function (model) {
                this.buildRow(model);
                this.bindHideEvents(model);
            }, this);
        },

        buildRow: function (model) {
            this.createView(model.name + '-' + model.id, this.rowView, {
                el: this.options.el + ' .list-row[data-type="' + model.name + '"][data-id="' + model.id + '"]',
                model: model,
                rowLayout: this.layouts[model.name]
            }, function (view) {
                view.render();
            }.bind(this));
        },

        bindHideEvents: function (model) {
            this.listenTo(model, 'list-hierarchical-hide-children', function (hide) {
                if (model.children) {
                    model.children.forEach(function (child) {
                        var childModel = this.collection.get(child);
                        if (childModel) {
                            hide ? this.hideRow(childModel.name, childModel.id) : this.showRow(childModel.name, childModel.id);
                            childModel.trigger('list-hierarchical-hide-children', hide);
                        }
                    }, this);
                }
            }, this);
        },

        hideRow: function (type, id) {
            this.$el.find('.list-row[data-type="' + type + '"][data-id="' + id + '"]').addClass('hidden');
        },

        showRow: function (type, id) {
            this.$el.find('.list-row[data-type="' + type + '"][data-id="' + id + '"]').removeClass('hidden');
        },

        actionQuickEdit: function (data) {
            List.prototype.actionQuickEdit.call(this, data);
        },

        actionQuickView: function (data) {
            List.prototype.actionQuickView.call(this, data);
        }

    });

});
