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

Espo.define('project-management:views/issue/fields/labels', 'views/fields/link-multiple', function (Dep) {

    return Dep.extend({

        editTemplate: 'project-management:issue/fields/labels/edit',

        detailTemplate: 'project-management:issue/fields/labels/detail',

        createDisabled: true,

        data() {
            const data = Dep.prototype.data.call(this);
            data['map'] = this.ids.map(id => {
                return {id, name: this.nameHash[id]};
            });
            return data;
        },

        afterRender() {
            if (this.mode === 'edit') {
                this.$element = this.$el.find('[name="' + this.name + '"]');

                this.$element.val(this.ids.join(':,:'));

                const options = {
                    options: this.ids.map(id => {
                        return {value: id, label: this.nameHash[id] || id};
                    }),
                    valueField: 'value',
                    labelField: 'label',
                    delimiter: ':,:',
                    searchField: ['label'],
                    highlight: false,
                    openOnFocus: true,
                    preload: true,
                    plugins: ['remove_button'],
                    maxOptions: 10,
                    load: (query, callback) => {
                        this.ajaxGetRequest('Label', {
                            select: 'id,name',
                            q: query,
                            maxSize: 10,
                            offset: 0,
                            where: [
                                {
                                    attribute: 'projectId',
                                    type: 'inProjectAndParentGroups',
                                    value: this.model.get('projectId')
                                }
                            ]
                        }).then(response => {
                            callback(response.list.map(item => {
                                return {value: item.id, label: item.name};
                            }));
                        });
                    },
                    score(search) {
                        const score = this.getScoreFunction(search);
                        return function (item) {
                            if (item.label.toLowerCase().indexOf(search.toLowerCase()) === 0) {
                                return score(item);
                            }
                            return 0;
                        };
                    }
                };

                this.$element.selectize(options);

                this.$element.on('change', function () {
                    this.trigger('change');
                    this.fetchFromDom();
                }.bind(this));
            }
        },

        fetchFromDom: function () {
            let ids = this.$element.val().split(':,:');
            if (ids.length == 1 && ids[0] == '') {
                ids = [];
            }
            this.ids = ids;
            this.nameHash = {};
            this.ids.forEach(id => {
                const options = this.$element[0].selectize.options || [];
                if (options[id]) {
                    this.nameHash[id] = options[id].label;
                }
            });
        }

    });

});
