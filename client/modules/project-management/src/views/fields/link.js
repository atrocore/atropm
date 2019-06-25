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

Espo.define('project-management:views/fields/link', 'views/fields/link', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.modifySelectLinkActionHandler();
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
