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
