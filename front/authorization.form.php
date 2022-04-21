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

$authorization = new PluginOauthimapAuthorization();
$application   = new PluginOauthimapApplication();

if (isset($_POST['id']) && isset($_POST['delete'])) {
    $authorization->check($_POST['id'], DELETE);
    $authorization->delete($_POST);

    Html::back();
} elseif (isset($_POST['id']) && isset($_POST['update'])) {
    $authorization->check($_POST['id'], UPDATE);
    if (
        $authorization->update($_POST)
        && $application->getFromDB($authorization->fields[$application->getForeignKeyField()])
    ) {
        Html::redirect($application->getLinkURL());
    }

    Html::back();
} elseif (isset($_REQUEST['id']) && isset($_REQUEST['diagnose'])) {
    $authorization->check($_REQUEST['id'], READ);

    $authorization = new PluginOauthimapAuthorization();
    $application   = new PluginOauthimapApplication();

    Html::popHeader($application::getTypeName(Session::getPluralNumber()));
    $authorization->check($_REQUEST['id'], READ);

    $authorization->showDiagnosticForm($_POST);

    Html::popFooter();
} elseif (isset($_GET['id'])) {
    $application = new PluginOauthimapApplication();
    $application->displayCentralHeader();
    $authorization->display(
        [
            'id' => $_GET['id'],
        ]
    );
    Html::footer();
} else {
    Html::displayErrorAndDie('lost');
}
