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

use GlpiPlugin\Oauthimap\MailCollectorFeature;

class PluginOauthimapHook
{
    /**
     * Handle post_item_form hook.
     *
     * @param array $params
     *
     * @return void
     */
    public static function postItemForm(array $params): void
    {
        $item = $params['item'];

        if (!is_object($item)) {
            return;
        }

        switch (get_class($item)) {
            case MailCollector::class:
                MailCollectorFeature::alterMailCollectorForm();
                break;
            case PluginOauthimapApplication::class:
                PluginOauthimapApplication::showFormExtra((int) $item->fields[PluginOauthimapApplication::getIndexName()]);
                break;
        }
    }
}
