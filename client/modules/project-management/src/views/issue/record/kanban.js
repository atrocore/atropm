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

Espo.define('project-management:views/issue/record/kanban', 'views/record/kanban', function (Dep) {

    return Dep.extend({

        ignoreRefresh: false,

        rendered: false,

        rowActionsView: 'project-management:views/issue/record/row-actions/kanban',

        setup() {
            this.rendered = false;

            Dep.prototype.setup.call(this);

            this.initRealTimeMode();
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.rendered = true;
        },

        actionCloseAndArchive(data) {
            let self = this;
            let model = this.collection.get(data.id);

            this.notify('Saving...');
            model.save({"closed": true, "archived": true}, {
                success: function () {
                    self.notify('Saved', 'success');
                },
                patch: true
            });
        },

        initRealTimeMode() {
            let issuesUpdateTimestamp = localStorage.getItem('pd_issuesUpdateTimestamp');

            let interval = setInterval(() => {
                if ($('.list-kanban').length === 0 && this.rendered) {
                    clearInterval(interval);
                    return false;
                }

                if (issuesUpdateTimestamp !== localStorage.getItem('pd_issuesUpdateTimestamp')) {
                    if (!this.ignoreRefresh) {
                        $('button[data-action="search"]').click();
                    }
                    issuesUpdateTimestamp = localStorage.getItem('pd_issuesUpdateTimestamp');
                    this.ignoreRefresh = false;
                }
            }, 500);
        },

        initSortable() {
            const $list = this.$listKanban.find('.group-column-list');

            $list.find('> .item').on('touchstart', function (e) {
                e.originalEvent.stopPropagation();
            }.bind(this));

            $list.sortable({
                connectWith: '.group-column-list',
                cancel: '.dropdown-menu *',
                stop: function (e, ui) {
                    const $item = $(ui.item);
                    const group = $item.closest('.group-column-list').data('name');
                    const id = $item.data('id');
                    let position = 0;

                    $item.parent().find('.item').each((k, el) => {
                        if ($(el).data('id') === id) {
                            position = k;
                        }
                    });

                    let beforeId = null;
                    if (position > 0) {
                        beforeId = $item.parent().find('.item:eq(' + (position - 1) + ')').data('id');
                    }

                    const model = this.collection.get(id);
                    if (!model) {
                        $list.sortable('cancel');
                        return;
                    }
                    let attributes = {
                        status: group,
                        beforeIssueId: beforeId,
                    };

                    this.handleAttributesOnGroupChange(model, attributes, group);

                    model.save(attributes, {patch: true, isDrop: true}).then(function (issue) {
                        this.ignoreRefresh = true;
                        Espo.Ui.success(this.translate('Saved'));
                        this.trigger('after:save');
                    }.bind(this)).fail(function () {
                        $list.sortable('cancel');
                        $list.sortable('enable');
                    }.bind(this));
                }.bind(this)
            });
        },

    });
});
