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
*
* This software is not allowed to be used in Russia and Belarus.
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
