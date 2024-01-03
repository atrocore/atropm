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

Espo.define('project-management:views/fields/link', 'views/fields/link', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.modifySelectLinkActionHandler();

            if (this.name === 'project') {
                this.on('linkLoaded', function (e) {
                    const idValue = this.model.get(this.idName);
                    if (idValue === null) {
                        this.ajaxGetRequest(this.foreignScope, {silent: true})
                            .done(function (response) {
                                if (response.list && response.list.length === 1) {
                                    const item = response.list.pop();
                                    this.model.set(this.idName, item.id);
                                    this.model.set(this.nameName, item.name);
                                }
                            }.bind(this));
                    }
                });
            }
        },

        modifySelectLinkActionHandler: function () {
            if (this.mode !== 'list') {
                this.addActionHandler('selectLink', function () {
                    this.notify('Loading...');

                    var viewName = this.getMetadata().get('clientDefs.' + this.foreignScope + '.modalViews.select') || this.selectRecordsView;

                    this.createView('dialog', viewName, {
                        scope: this.foreignScope,
                        createButton: !this.createDisabled && this.mode !== 'search',
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        primaryFilterName: this.getSelectPrimaryFilterName(),
                        createAttributes: (this.mode === 'edit') ? this.getCreateAttributes() : null,
                        mandatorySelectAttributeList: this.mandatorySelectAttributeList,
                        forceSelectAllAttributes: this.forceSelectAllAttributes,
                        whereAdditional: this.getWhereAdditional()
                    }, function (view) {
                        view.render();
                        this.notify(false);
                        this.listenToOnce(view, 'select', function (model) {
                            this.clearView('dialog');
                            this.select(model);
                        }, this);
                    }, this);
                });
            }
        },

        getAutocompleteUrl: function () {
            var url = Dep.prototype.getAutocompleteUrl.call(this);

            var whereAdditional = this.getWhereAdditional();
            if (whereAdditional) {
                url += '&' + $.param({
                    where: whereAdditional
                });
            }
            return url;
        },

        getWhereAdditional: function () {
            return null;
        }

    });

});
