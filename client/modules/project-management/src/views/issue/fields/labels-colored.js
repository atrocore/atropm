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

Espo.define('project-management:views/issue/fields/labels-colored', 'views/fields/colored-multi-enum', function (Dep) {

    return Dep.extend({

        setup() {
            this.prepareLabelsOptions();

            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:projectId', () => {
                this.model.set('labels', null);
                this.prepareLabelsOptions();
                this.reRender();
            });
        },

        prepareLabelsOptions() {
            this.params.options = [];
            this.params.optionColors = [];
            this.translatedOptions = {};

            const allLabels = this.getMetadata().get('entityDefs.Issue.fields.labels.allLabels') || [];

            if ((this.mode === 'detail' || this.mode === 'edit') && this.model.get('projectId')) {
                this.ajaxGetRequest(`Project/${this.model.get('projectId')}`, {}, {async: false}).then(response => {
                    if (response.groupId) {
                        allLabels.forEach(label => {
                            if (label.groupId === response.groupId) {
                                this.params.options.push(label.id);
                                this.params.optionColors.push(label.backgroundColor);
                                this.translatedOptions[label.id] = label.name;
                            }
                        });
                    }

                    allLabels.forEach(label => {
                        if (label.projectId === this.model.get('projectId')) {
                            this.params.options.push(label.id);
                            this.params.optionColors.push(label.backgroundColor);
                            this.translatedOptions[label.id] = label.name;
                        }
                    });
                });
            } else {
                allLabels.forEach(label => {
                    this.params.options.push(label.id);
                    this.params.optionColors.push(label.backgroundColor);
                    this.translatedOptions[label.id] = label.name;
                });
            }
        },

    });

});
