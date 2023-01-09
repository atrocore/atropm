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
