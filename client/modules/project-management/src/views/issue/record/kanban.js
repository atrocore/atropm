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

Espo.define('project-management:views/issue/record/kanban', 'views/record/kanban', function (Dep) {

    return Dep.extend({

        ignoreRefresh: false,

        rendered: false,

        setup() {
            this.rendered = false;

            Dep.prototype.setup.call(this);

            this.initRealTimeMode();
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.rendered = true;
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
                    }.bind(this)).fail(function () {
                        $list.sortable('cancel');
                        $list.sortable('enable');
                    }.bind(this));
                }.bind(this)
            });
        },

    });
});
