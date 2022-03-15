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
