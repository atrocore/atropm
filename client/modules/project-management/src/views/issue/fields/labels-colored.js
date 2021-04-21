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

        setup: function () {
            this.params.options = [];
            this.params.optionColors = [];
            this.translatedOptions = {};

            (this.getMetadata().get('entityDefs.Issue.fields.labels.allLabels') || []).forEach(label => {
                if ((this.model.get('projectId') && label.projectId === this.model.get('projectId')) || (this.model.get('projectGroupId') && label.groupId === this.model.get('projectGroupId')) || (!this.model.get('projectId') && !this.model.get('projectGroupId'))) {
                    this.params.options.push(label.id);
                    this.params.optionColors.push(label.backgroundColor);
                    this.translatedOptions[label.id] = label.name;
                }
            });

            Dep.prototype.setup.call(this);
        },

    });

});
