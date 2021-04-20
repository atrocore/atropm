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

Espo.define('project-management:views/record/panels/bottom-list', 'views/record/panels/bottom', function (Dep) {

    return Dep.extend({

        scope: null,

        template: 'record/panels/relationship',

        rowActionsView: 'views/record/row-actions/relationship',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupTitle();

            var layoutName = 'listSmall';
            var listLayout = null;

            this.wait(true);
            this.getCollectionFactory().create(this.scope, function (collection) {
                this.collection = collection;
                collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;
                collection.url = collection.urlRoot = this.scope;
                collection.parentModel = this.model;
                collection.whereAdditional = this.getWhereAdditional();

                this.listenTo(this.model, 'update-all', function () {
                    collection.fetch();
                }, this);

                var viewName =
                    this.defs.recordListView ||
                    this.getMetadata().get(['clientDefs', this.scope, 'recordViews', 'listRelated']) ||
                    this.getMetadata().get(['clientDefs', this.scope, 'recordViews', 'list']) ||
                    'views/record/list';
                this.rowActionsView = this.defs.readOnly ? false : (this.defs.rowActionsView || this.rowActionsView);

                this.listenToOnce(this, 'after:render', function () {
                    this.createView('list', viewName, {
                        collection: collection,
                        layoutName: layoutName,
                        listLayout: listLayout,
                        checkboxes: false,
                        rowActionsView: this.rowActionsView,
                        buttonsDisabled: true,
                        el: this.options.el + ' .list-container',
                        skipBuildRows: true
                    }, function (view) {
                        view.getSelectAttributeList(function (selectAttributeList) {
                            if (selectAttributeList) {
                                collection.data.select = selectAttributeList.join(',');
                            }
                            collection.fetch();
                        }.bind(this));
                    });
                }, this);

                this.wait(false);
            }, this);
        },

        getWhereAdditional: function () {
            return null;
        },

        actionRefresh: function () {
            this.collection.fetch();
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
                var model = this.collection.get(id);
                this.notify('Removing...');
                model.destroy({
                    success: function () {
                        this.notify('Removed', 'success');
                        this.collection.fetch();
                        this.model.trigger('after:unrelate');
                    }.bind(this),
                });
            }, this);
        }

    });

});
