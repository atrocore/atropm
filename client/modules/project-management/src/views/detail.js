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

Espo.define('project-management:views/detail', 'views/detail', function (Dep) {

    return Dep.extend({

        whereAdditional: {},

        actionSelectRelated: function (data) {
            var link = data.link;

            if (!this.model.defs['links'][link]) {
                throw new Error('Link ' + link + ' does not exist.');
            }
            var scope = this.model.defs['links'][link].entity;
            var foreign = this.model.defs['links'][link].foreign;

            var massRelateEnabled = data.massSelect;

            var self = this;
            var attributes = {};

            var filters = Espo.Utils.cloneDeep(this.selectRelatedFilters[link]) || {};
            for (var filterName in filters) {
                if (typeof filters[filterName] == 'function') {
                    var filtersData = filters[filterName].call(this);
                    if (filtersData) {
                        filters[filterName] = filtersData;
                    } else {
                        delete filters[filterName];
                    }
                }
            }

            var primaryFilterName = data.primaryFilterName || this.selectPrimaryFilterNames[link] || null;
            if (typeof primaryFilterName == 'function') {
                primaryFilterName = primaryFilterName.call(this);
            }


            var dataBoolFilterList = data.boolFilterList;
            if (typeof data.boolFilterList == 'string') {
                dataBoolFilterList = data.boolFilterList.split(',');
            }

            var boolFilterList = dataBoolFilterList || Espo.Utils.cloneDeep(this.selectBoolFilterLists[link] || []);

            if (typeof boolFilterList == 'function') {
                boolFilterList = boolFilterList.call(this);
            }

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.select') || 'views/modals/select-records';

            this.notify('Loading...');
            this.createView('dialog', viewName, {
                scope: scope,
                multiple: true,
                createButton: false,
                filters: filters,
                massRelateEnabled: massRelateEnabled,
                primaryFilterName: primaryFilterName,
                boolFilterList: boolFilterList,
                whereAdditional: this.getWhereAdditional(link)
            }, function (dialog) {
                dialog.render();
                this.notify(false);
                dialog.once('select', function (selectObj) {
                    var data = {};
                    if (Object.prototype.toString.call(selectObj) === '[object Array]') {
                        var ids = [];
                        selectObj.forEach(function (model) {
                            ids.push(model.id);
                        });
                        data.ids = ids;
                    } else {
                        if (selectObj.massRelate) {
                            data.massRelate = true;
                            data.where = selectObj.where;
                        } else {
                            data.id = selectObj.id;
                        }
                    }
                    $.ajax({
                        url: self.scope + '/' + self.model.id + '/' + link,
                        type: 'POST',
                        data: JSON.stringify(data),
                        success: function () {
                            this.notify('Linked', 'success');
                            this.updateRelationshipPanel(link);
                            this.model.trigger('after:relate');
                        }.bind(this),
                        error: function () {
                            this.notify('Error occurred', 'error');
                        }.bind(this)
                    });
                }.bind(this));
            }.bind(this));
        },

        getWhereAdditional: function (link) {
            var whereAdditional = this.whereAdditional[link];
            if (typeof whereAdditional === 'function') {
                return whereAdditional.call(this);
            } else {
                return null;
            }
        }

    });

});
