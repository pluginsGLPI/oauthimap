<?php

/**
 * -------------------------------------------------------------------------
 * oauthimap plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of oauthimap plugin.
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this plugin. If not, see <https://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2020-2025 by Teclib'
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.fr.html
 * @link      https://services.glpi-network.com
 * -------------------------------------------------------------------------
 */

header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

/** @var DBmysql $DB */
global $DB;

$iterator = $DB->request(
    [
        'FROM'  => PluginOauthimapAuthorization::getTable(),
        'WHERE' => [
            PluginOauthimapApplication::getForeignKeyField() => $_POST['application_id'] ?? null,
        ],
    ],
);
$authorizations = [
    '-1' => __s('Create authorization for another user', 'oauthimap'),
];
$value = -1;
foreach ($iterator as $row) {
    $authorizations[$row['id']] = $row['email'];
    if (array_key_exists('selected', $_POST) && $row['email'] == $_POST['selected']) {
        $value = $row['id'];
    }
}

Dropdown::showFromArray(
    PluginOauthimapAuthorization::getForeignKeyField(),
    $authorizations,
    [
        'display_emptychoice' => false,
        'value'               => $value,
    ],
);
