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

Espo.define('project-management:views/fields/varchar-with-caret', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        listLinkTemplate: 'project-management:fields/varchar-with-caret/list-link',

        marginLeftStep: 10,

        hidden: false,

        events: _.extend({
            'click .action[data-action="toggleCaret"]': function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.actionToggleCaret();
            }
        }, Dep.prototype.events),

        data: function () {
            return _.extend({
                marginLeft: (this.model.level || 0) * this.marginLeftStep,
                showCaret: !!this.model.children,
                hidden: this.hidden
            }, Dep.prototype.data.call(this));
        },

        actionToggleCaret: function () {
            this.hidden = !this.hidden;
            this.model.trigger('list-hierarchical-hide-children', this.hidden);
            this.reRender();
        }

    });

});
