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

Espo.define('project-management:views/record/panels/relationship-hierarchical', ['project-management:views/record/panels/bottom-hierarchical', 'project-management:collections/stub'], function (Dep, StubCollection) {

    return Dep.extend({

        noCreateScopeList: ['User', 'Team', 'Role'],

        setup: function () {
            Dep.prototype.setup.call(this);

            this.link = this.link || this.defs.link || this.panelName;

            if (!this.scope && !(this.link in this.model.defs.links)) {
                throw new Error('Link \'' + this.link + '\' is not defined in model \'' + this.model.name + '\'');
            }

            this.scope = this.scope || this.model.defs.links[this.link].entity;

            if (!('create' in this.defs)) {
                this.defs.create = true;
            }
            if (!('select' in this.defs)) {
                this.defs.select = true;
            }

            if (!('view' in this.defs)) {
                this.defs.view = true;
            }

            this.setupTitle();

            if (this.defs.createDisabled) {
                this.defs.create = false;
            }
            if (this.defs.selectDisabled) {
                this.defs.select = false;
            }
            if (this.defs.viewDisabled) {
                this.defs.view = false;
            }

            if (this.defs.create) {
                if (this.getAcl().check(this.scope, 'create') && !~this.noCreateScopeList.indexOf(this.scope)) {
                    this.buttonList.push({
                        title: 'Create',
                        action: this.defs.createAction || 'createRelated',
                        link: this.link,
                        acl: 'edit',
                        html: '<span class="fas fa-plus"></span>',
                        data: {
                            link: this.link,
                        }
                    });
                }
            }

            if (this.defs.select) {
                var data = {link: this.link};
                if (this.defs.selectPrimaryFilterName) {
                    data.primaryFilterName = this.defs.selectPrimaryFilterName;
                }
                if (this.defs.selectBoolFilterList) {
                    data.boolFilterList = this.defs.selectBoolFilterList;
                }
                data.massSelect = this.defs.massSelect;

                this.actionList.unshift({
                    label: 'Select',
                    action: this.defs.selectAction || 'selectRelated',
                    data: data,
                    acl: 'edit'
                });
            }

            if (this.defs.view) {
                this.actionList.unshift({
                    label: 'View List',
                    action: this.defs.viewAction || 'viewRelatedList'
                });
            }

            //pseudo collection for compatibility
            this.collection = new StubCollection();
            this.listenTo(this.collection, 'fetch', function () {
                this.actionRefresh();
            }, this);
        },

        setupTitle: function () {
            this.title = this.title || this.translate(this.link, 'links', this.model.name);

            var iconHtml = '';
            if (!this.getConfig().get('scopeColorsDisabled')) {
                iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);
            }

            this.titleHtml = this.title;

            if (this.defs.label) {
                this.titleHtml = iconHtml + this.translate(this.defs.label, 'labels', this.scope);
            } else {
                this.titleHtml = iconHtml + this.title;
            }
        }
    });

});
