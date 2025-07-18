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

include('../../../inc/includes.php');

Session::checkLoginUser();

/** @var array $_UPOST */
global $_UPOST;

$dropdown = new PluginOauthimapApplication();

if (isset($_POST['id']) && isset($_POST['request_authorization'])) {
    $dropdown->check($_POST['id'], UPDATE);
    $dropdown->redirectToAuthorizationUrl();
} else {
    Html::requireJs('clipboard');

    if (array_key_exists('client_secret', $_POST) && array_key_exists('client_secret', $_UPOST)) {
        // Client secret must not be altered.
        $_POST['client_secret'] = $_UPOST['client_secret'];
    }

    include(GLPI_ROOT . '/front/dropdown.common.form.php');
}
