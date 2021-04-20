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

Espo.define('project-management:views/record/panels/relationship-hierarchical', ['project-management:views/record/panels/bottom-hierarchical', 'project-management:collections/stub'], function (Dep, StubCollection) {

    return Dep.extend({

        noCreateScopeList: ['User', 'Team', 'Role', 'Portal'],

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
