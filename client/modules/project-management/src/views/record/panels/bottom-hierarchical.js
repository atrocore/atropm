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

Espo.define('project-management:views/record/panels/bottom-hierarchical', ['views/record/panels/bottom', 'collection', 'model'], function (Dep, Collection, Model) {

    return Dep.extend({

        template: 'record/panels/relationship',

        customUrl: null,

        totalDefs: null,

        listHierarchicalView: 'project-management:views/record/list-hierarchical',

        rowActionsViews: {},

        layouts: {},

        headLayout: [],

        mixedCollection: null,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupTitle();

            this.prepareHeadLayout();
        },

        prepareHeadLayout: function () {

        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.createCollection().then(function (collection) {
                this.createView('list', this.listHierarchicalView, {
                    el: this.options.el + ' > .list-container',
                    collection: collection,
                    rowActionsViews: Espo.Utils.cloneDeep(this.rowActionsViews),
                    layouts: Espo.utils.cloneDeep(this.layouts),
                    headLayout: Espo.utils.cloneDeep(this.headLayout),
                    scope: this.scope
                }, function (view) {
                    view.render();
                })
            });
        },

        actionCreateRelatedExpense: function () {
            const link = 'expenses';
            const scope = 'Expense';
            const foreignLink = this.model.defs['links'][link].foreign;

            this.notify('Loading...');

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') || 'views/modals/edit';
            this.createView('quickCreate', viewName, {
                scope: scope,
                relate: {
                    model: this.model,
                    link: foreignLink,
                }
            }, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.getParentView().getView('expenses').actionRefresh()
                }, this);
            }.bind(this));
        },

        createCollection: function () {
            return this.ajaxGetRequest(this.getCollectionUrl()).then(function (data) {
                this.parseTotal(data);
                var collection = new Collection();
                this.mixedCollection = collection;
                this.parseCollection(collection, data.list, 0);
                return collection;
            }.bind(this));
        },

        parseCollection: function (collection, data, level) {
            var children = [];
            data.forEach(function (row) {
                this.getModelFactory().create(row.type, function (model) {
                    model.set(row.entity);
                    model.level = level;
                    collection.add(model);
                    if (Array.isArray(row.children) && row.children.length) {
                        model.children = this.parseCollection(collection, row.children, level + 1);
                    }
                    children.push(model.id);
                }, this)
            }, this);
            return children;
        },

        actionRefresh: function () {
            this.reRender();
        },

        parseTotal: function (data) {
            if (this.totalDefs) {
                var type = this.totalDefs.type || 'base';
                var view = this.totalDefs.view || this.getFieldManager().getViewName(type);
                var attributeList = this.getFieldManager().getAttributeList(type, 'total');
                var model = new Model();
                var newData = {};
                (attributeList || []).forEach(function (attribute) {
                   newData[attribute] = Espo.Utils.cloneDeep(data[attribute]);
                });
                model.set(newData);

                this.createView('total', view, {
                    model: model,
                    mode: 'list',
                    defs: {
                        name: 'total',
                    }
                }, function (view) {
                    view.render();
                    view._getHtml(function (html) {
                        this.setTotalValue(html);
                    }.bind(this));
                    this.clearView('total');
                }.bind(this));
            }
        },

        setTotalValue: function (value) {
            var parentView = this.getParentView();
            if (parentView) {
                var panelTitle = parentView.$el.find('.panel[data-name="' + this.panelName + '"] .panel-title');
                if (panelTitle.length) {
                    panelTitle.find('.total-container').remove();
                    panelTitle.prepend(this.getTotalLabel(value));
                }
            }
        },

        getTotalLabel: function (value) {
            return '<div class="total-container pull-right">' + value + '</div>';
        },

        setupTitle: function () {
            var iconHtml = '';
            if (!this.getConfig().get('scopeColorsDisabled')) {
                iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);
            }

            if (this.defs.label) {
                this.titleHtml = iconHtml + this.translate(this.defs.label, 'labels', this.model.name);
            } else {
                this.titleHtml = iconHtml + this.title;
            }
        },

        actionRemoveRelated: function (data) {
            var id = data.id;

            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages'),
                confirmText: this.translate('Remove')
            }, function () {
                var model = this.mixedCollection.get(id);
                this.notify('Removing...');
                model.destroy({
                    success: function () {
                        this.notify('Removed', 'success');
                        this.actionRefresh();
                        this.model.trigger('after:unrelate');
                    }.bind(this),
                });
            }, this);
        },

    });

});
