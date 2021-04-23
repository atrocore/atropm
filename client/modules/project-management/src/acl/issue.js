/*
 * This file is part of EspoCRM and/or AtroCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * AtroCore is EspoCRM-based Open Source application.
 * Copyright (C) 2020 AtroCore UG (haftungsbeschränkt).
 *
 * AtroCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AtroCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "AtroCore" word.
 */

Espo.define('project-management:acl/issue', 'acl', function (Dep) {

    return Dep.extend({

        checkInTeam: function (model) {
            let userTeamIdList = this.getUser().getTeamIdList();
            if (model.name == 'Team') {
                return (userTeamIdList.indexOf(model.id) != -1);
            } else {
                if (!model.has('teamsIds')) {
                    return null;
                }
                let teamIdList = model.getTeamIdList();
                (model.get('projectTeamsIds') || []).forEach(id => {
                    teamIdList.push(id);
                });

                let inTeam = false;
                userTeamIdList.forEach(function (id) {
                    if (~teamIdList.indexOf(id)) {
                        inTeam = true;
                    }
                });

                return inTeam;
            }

            return false;
        }

    });

});

