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

include('../../../inc/includes.php');

$dropdown = new PluginOauthimapApplication();

if (isset($_POST['id']) && isset($_POST['request_authorization'])) {
    $dropdown->check($_POST['id'], UPDATE);
    $dropdown->redirectToAuthorizationUrl();
} else {
    Html::requireJs('clipboard');

    if (array_key_exists('client_secret', $_POST)) {
        // Client secret must not be altered.
        $_POST['client_secret'] = $_UPOST['client_secret'];
    }

    include(GLPI_ROOT . '/front/dropdown.common.form.php');
}
