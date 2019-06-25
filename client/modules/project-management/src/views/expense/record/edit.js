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

Espo.define('project-management:views/expense/record/edit', 'views/record/edit', function (Dep) {

    return Dep.extend({

        expenseType: null,

        assignedUser: null,

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.isNew()) {
                this.getModelFactory().create('User', function (model) {
                    this.assignedUser = model;
                    this.listenTo(this.assignedUser, 'change:unitPrice change:unitPriceCurrency', function () {
                        this.updateDependentAttributes();
                    }, this);
                    this.listenTo(this.model, 'change:assignedUserId', function () {
                        this.updateRelatedModel(this.assignedUser, 'assignedUserId');
                    }, this);
                    this.updateRelatedModel(this.assignedUser, 'assignedUserId');
                }, this);

                this.getModelFactory().create('ExpenseType', function (model) {
                    this.expenseType = model;
                    this.listenTo(this.expenseType, 'change:unitPrice change:unitPriceCurrency change:defaultUnitsAmount', function () {
                        this.updateDependentAttributes();
                    }, this);
                    this.listenTo(this.model, 'change:expenseTypeId', function () {
                        this.updateRelatedModel(this.expenseType, 'expenseTypeId');
                    }, this);
                    this.updateRelatedModel(this.expenseType, 'expenseTypeId');
                }, this);
            }
        },

        updateDependentAttributes: function () {
            var data = {};

            if (this.assignedUser.has('unitPrice')) {
                data['unitPrice'] = this.assignedUser.get('unitPrice');
            } else if (this.expenseType.has('unitPrice')) {
                data['unitPrice'] = this.expenseType.get('unitPrice')
            } else {
                data['unitPrice'] = null;
            }

            if (this.assignedUser.has('unitPriceCurrency')) {
                data['unitPriceCurrency'] = this.assignedUser.get('unitPriceCurrency');
            } else if (this.expenseType.has('unitPriceCurrency')) {
                data['unitPriceCurrency'] = this.expenseType.get('unitPriceCurrency')
            } else {
                data['unitPriceCurrency'] = null;
            }

            if (this.expenseType.has('defaultUnitsAmount')) {
                data['units'] = this.expenseType.get('defaultUnitsAmount')
            } else {
                data['units'] = null;
            }

            this.model.set(data);
        },

        updateRelatedModel: function (model, key) {
            if (model) {
                var id = this.model.get(key);
                if (id) {
                    model.id = id;
                    model.fetch();
                } else {
                    model.id = null;
                    model.clear();
                }
            }
        },

        populateDefaults: function () {
            this.model.set({assignedUserId: null, assignedUserName: null}, {silent: true});

            Dep.prototype.populateDefaults.call(this);
        },

    });

});
