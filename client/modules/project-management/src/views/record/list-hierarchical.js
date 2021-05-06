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
