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

$application   = new PluginOauthimapApplication();
$authorization = new PluginOauthimapAuthorization();

$application_id = $_SESSION[PluginOauthimapApplication::getForeignKeyField()] ?? null;

$success = false;
if (
    array_key_exists('error', $_GET)                && !empty($_GET['error'])
    || array_key_exists('error_description', $_GET) && !empty($_GET['error_description'])
) {
    // Got an error, probably user denied access
    Session::addMessageAfterRedirect(
        sprintf(__s('Authorization failed with error: %s', 'oauthimap'), htmlspecialchars($_GET['error_description'] ?? $_GET['error'])),
        false,
        ERROR,
    );
} elseif (
    $application_id === null
    || !array_key_exists('state', $_GET)
    || !array_key_exists('oauth2state', $_SESSION)
    || $_GET['state'] !== $_SESSION['oauth2state']
) {
    Session::addMessageAfterRedirect(__s('Unable to verify authorization code', 'oauthimap'), false, ERROR);
} elseif (!array_key_exists('code', $_GET)) {
    Session::addMessageAfterRedirect(__s('Unable to get authorization code', 'oauthimap'), false, ERROR);
} elseif (!$authorization->createFromCode($application_id, $_GET['code'])) {
    Session::addMessageAfterRedirect(__s('Unable to save authorization code', 'oauthimap'), false, ERROR);
} else {
    $success = true;
}

$callback_callable = $_SESSION['plugin_oauthimap_callback_callable'] ?? null;

if (is_callable($callback_callable)) {
    $callback_params = $_SESSION['plugin_oauthimap_callback_params'] ?? [];
    call_user_func_array($callback_callable, [$success, $authorization, $callback_params]);
}

$url = $application->getFromDB($application_id) ? $application->getLinkURL() : $application->getSearchURL(true);

Html::redirect($url);
