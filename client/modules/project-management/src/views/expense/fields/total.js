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
