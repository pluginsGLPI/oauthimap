<?php

/**
 * -------------------------------------------------------------------------
 * OauthIMAP plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of OauthIMAP.
 *
 * OauthIMAP is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * OauthIMAP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OauthIMAP. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2020-2022 by OauthIMAP plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/oauthimap
 * -------------------------------------------------------------------------
 */

include("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

global $DB;

$iterator = $DB->request(
    [
        'FROM'   => PluginOauthimapAuthorization::getTable(),
        'WHERE'  => [
            PluginOauthimapApplication::getForeignKeyField() => $_POST['application_id'] ?? null,
        ],
    ]
);
$authorizations = [
    '-1' => __('Create authorization for another user', 'oauthimap')
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
    ]
);
