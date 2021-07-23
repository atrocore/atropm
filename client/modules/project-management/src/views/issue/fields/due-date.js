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

Espo.define('project-management:views/issue/fields/due-date', 'views/fields/date', function (Dep) {

    return Dep.extend({

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.options.isKanban) {
                const value = this.model.get(this.name);

                if (value) {
                    const d = moment.tz(value + ' OO:OO:00', this.getDateTime().internalDateTimeFormat, this.getDateTime().getTimeZone());
                    const dt = moment().tz(this.getDateTime().getTimeZone()).startOf('day');

                    if (d.unix() <= dt.unix()) {
                        this.$el.find('span').addClass('date-alert');
                    } else {
                        if (d.unix() <= dt.add(3, 'days').unix()) {
                            this.$el.find('span').addClass('date-warning');
                        }
                    }
                }
            }
        },

    });

});