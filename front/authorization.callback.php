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

// Session cookie will not be accessible when user will be redirected from provider website
// if `session.cookie_samesite` configuration value is `strict`.
// Redirecting on self using `http-equiv="refresh"` will get around this limitation.
$url = htmlspecialchars(
    str_replace('authorization.callback.php', 'authorization.process.php', $_SERVER['REQUEST_URI']),
);

echo <<<HTML
<html>
<head>
    <meta http-equiv="refresh" content="0;URL='{$url}'"/>
</head>
    <body></body>
</html>
HTML;
