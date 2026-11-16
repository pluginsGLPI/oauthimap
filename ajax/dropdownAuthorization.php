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

use function Safe\json_encode;

Session::checkLoginUser();
Session::checkRight("config", UPDATE);

Session::writeClose();

/** @var DBmysql $DB */
/** @var array $CFG_GLPI */
global $DB, $CFG_GLPI;

$application_fkey   = PluginOauthimapApplication::getForeignKeyField();
$authorization_fkey = PluginOauthimapAuthorization::getForeignKeyField();
$application_id     = (int) ($_POST['application_id'] ?? 0);
$create_label       = __('Create authorization for another user', 'oauthimap');

Html::header_nocache();

if (($_POST['action'] ?? '') === 'get_values') {
    header('Content-Type: application/json; charset=UTF-8');

    $dropdown_max = max(1, (int) $CFG_GLPI['dropdown_max']);
    $page         = max(1, (int) ($_POST['page'] ?? 1));
    $page_limit   = min($dropdown_max, max(1, (int) ($_POST['page_limit'] ?? $dropdown_max)));
    $search       = trim((string) ($_POST['searchText'] ?? ''));

    $where = [$application_fkey => $application_id];
    // A search reduced to nothing (`$`, `^$`, `null`) does not filter, like an empty search.
    $search_value = $search !== '' ? Search::makeTextSearchValue($search) : null;
    if (is_string($search_value) && $search_value !== '') {
        $where['email'] = ['LIKE', $search_value];
    }

    $results = [];
    if ($page === 1 && str_contains(mb_strtolower($create_label), mb_strtolower(trim($search, '^$')))) {
        $results[] = ['id' => -1, 'text' => $create_label];
    }

    $iterator = $DB->request(
        [
            'SELECT' => ['id', 'email'],
            'FROM'   => PluginOauthimapAuthorization::getTable(),
            'WHERE'  => $where,
            'ORDER'  => 'email',
            'START'  => ($page - 1) * $page_limit,
            'LIMIT'  => $page_limit,
        ],
    );
    foreach ($iterator as $row) {
        $results[] = ['id' => (int) $row['id'], 'text' => $row['email']];
    }

    echo json_encode(['results' => $results, 'count' => count($iterator)]);

    return;
}

header('Content-Type: text/html; charset=UTF-8');

$value     = -1;
$valuename = $create_label;
if (($_POST['selected'] ?? '') !== '') {
    $selected = $DB->request(
        [
            'SELECT' => ['id', 'email'],
            'FROM'   => PluginOauthimapAuthorization::getTable(),
            'WHERE'  => [$application_fkey => $application_id, 'email' => $_POST['selected']],
            'LIMIT'  => 1,
        ],
    )->current();
    if ($selected !== null) {
        $value     = (int) $selected['id'];
        $valuename = $selected['email'];
    }
}

echo Html::jsAjaxDropdown(
    $authorization_fkey,
    'dropdown_' . $authorization_fkey . mt_rand(),
    $CFG_GLPI['root_doc'] . '/plugins/oauthimap/ajax/dropdownAuthorization.php',
    [
        'value'          => $value,
        'valuename'      => $valuename,
        'width'          => '100%',
        'action'         => 'get_values',
        'application_id' => $application_id,
    ],
);
