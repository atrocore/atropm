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

Espo.define('project-management:views/expense/fields/total', 'views/fields/currency', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:unitPrice change:units', function () {
                this.setValue();
            }, this);
            this.setValue();

            this.listenTo(this.model, 'change:unitPriceCurrency', function () {
                this.setCurrency();
            }, this);
            this.setCurrency();
        },

        setValue: function () {
            if (this.model.has('units') && this.model.has('unitPrice')) {
                var data = {};
                var unitPrice = this.model.get('unitPrice');
                var units = this.model.get('units');
                if (unitPrice && units) {
                    data[this.name] = unitPrice * units;
                } else {
                    data[this.name] = null;
                }
                this.model.set(data);
            }
        },

        setCurrency: function () {
            if (this.model.has('unitPriceCurrency')) {
                var data = {};
                var unitPriceCurrency = this.model.get('unitPriceCurrency');
                if (unitPriceCurrency) {
                    data[this.currencyFieldName] = unitPriceCurrency;
                } else {
                    data[this.currencyFieldName] = null;
                }
                this.model.set(data);
            }
        }

    });

});
