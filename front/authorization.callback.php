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

if (!array_key_exists('cookie_refresh', $_GET)) {
    // Session cookie will not be accessible when user will be redirected from provider website
    // if `session.cookie_samesite` configuration value is `strict`.
    // Redirecting on self using `http-equiv="refresh"` will get around this limitation.
    $url = htmlspecialchars(
        $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'cookie_refresh'
    );

    echo <<<HTML
<html>
<head>
    <meta http-equiv="refresh" content="0;URL='{$url}'"/>
</head>
    <body></body>
</html>
HTML;
    exit;
}

include('../../../inc/includes.php');

$application   = new PluginOauthimapApplication();
$authorization = new PluginOauthimapAuthorization();

$application_id = $_SESSION[PluginOauthimapApplication::getForeignKeyField()] ?? null;

$success = false;
if (
    array_key_exists('error', $_GET) && !empty($_GET['error'])
    || array_key_exists('error_description', $_GET) && !empty($_GET['error_description'])
) {
    // Got an error, probably user denied access
    Session::addMessageAfterRedirect(
        sprintf(__('Authorization failed with error: %s', 'oauthimap'), $_GET['error_description'] ?? $_GET['error']),
        false,
        ERROR
    );
} elseif (
    $application_id === null
    || !array_key_exists('state', $_GET)
    || !array_key_exists('oauth2state', $_SESSION)
    || $_GET['state'] !== $_SESSION['oauth2state']
) {
    Session::addMessageAfterRedirect(__('Unable to verify authorization code', 'oauthimap'), false, ERROR);
} elseif (!array_key_exists('code', $_GET)) {
    Session::addMessageAfterRedirect(__('Unable to get authorization code', 'oauthimap'), false, ERROR);
} elseif (!$authorization->createFromCode($application_id, $_GET['code'])) {
    Session::addMessageAfterRedirect(__('Unable to save authorization code', 'oauthimap'), false, ERROR);
} else {
    $success = true;
}

$callback_callable = $_SESSION['plugin_oauthimap_callback_callable'] ?? null;

if (is_callable($callback_callable)) {
    $callback_params = $_SESSION['plugin_oauthimap_callback_params'] ?? [];
    call_user_func_array($callback_callable, [$success, $authorization, $callback_params]);
}

// Redirect to application form/list if callback action does not exit yet
if ($application->getFromDB($application_id)) {
    $url = $application->getLinkURL();
} else {
    $url = $application->getSearchURL(true);
}

Html::redirect($url);
