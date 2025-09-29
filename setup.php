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

use Glpi\Http\SessionManager;

use function Safe\define;

define('PLUGIN_OAUTHIMAP_VERSION', '1.5.0');

// Minimal GLPI version, inclusive
define('PLUGIN_OAUTHIMAP_MIN_GLPI', '11.0.0');
// Maximum GLPI version, exclusive
define('PLUGIN_OAUTHIMAP_MAX_GLPI', '11.0.99');

define('PLUGIN_OAUTHIMAP_ROOT', Plugin::getPhpDir('oauthimap'));

use Glpi\Http\Firewall;
use GlpiPlugin\Oauthimap\MailCollectorFeature;

function plugin_init_oauthimap()
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['oauthimap'] = true;

    Firewall::addPluginStrategyForLegacyScripts(
        'oauthimap',
        '#^/front/authorization.callback.php$#',
        Firewall::STRATEGY_NO_CHECK,
    );

    if (Plugin::isPluginActive('oauthimap')) {
        // Config page: redirect to dropdown page
        $PLUGIN_HOOKS['config_page']['oauthimap'] = 'front/application.php';

        // Menu link
        $PLUGIN_HOOKS['menu_toadd']['oauthimap'] = [
            'config' => 'PluginOauthimapApplication',
        ];

        // Secured fields that are encrypted
        $PLUGIN_HOOKS['secured_fields']['oauthimap'] = [
            PluginOauthimapApplication::getTableField('client_secret'),
            PluginOauthimapAuthorization::getTableField('code'),
            PluginOauthimapAuthorization::getTableField('token'),
            PluginOauthimapAuthorization::getTableField('refresh_token'),
        ];

        // Plugin hooks
        $PLUGIN_HOOKS['post_item_form']['oauthimap'] = [PluginOauthimapHook::class, 'postItemForm'];

        // MailCollector hooks
        $PLUGIN_HOOKS['mail_server_protocols']['oauthimap'] = (fn(array $additionnal_protocols) => array_merge($additionnal_protocols, MailCollectorFeature::getMailProtocols()));
        $PLUGIN_HOOKS['pre_item_update']['oauthimap'] = [
            'MailCollector' => [MailCollectorFeature::class, 'forceMailCollectorUpdate'],
        ];
        $PLUGIN_HOOKS['item_add']['oauthimap'] = [
            'MailCollector' => [MailCollectorFeature::class, 'handleMailCollectorSaving'],
        ];
        $PLUGIN_HOOKS['item_update']['oauthimap'] = [
            'MailCollector' => [MailCollectorFeature::class, 'handleMailCollectorSaving'],
        ];
    }
}

function plugin_oauthimap_boot()
{
    SessionManager::registerPluginStatelessPath('oauthimap', '#/front/authorization\.callback\.php#');
}

function plugin_version_oauthimap()
{
    return [
        'name'         => __s('OAuth IMAP', 'oauthimap'),
        'version'      => PLUGIN_OAUTHIMAP_VERSION,
        'author'       => 'Teclib\'',
        'license'      => 'GPL v3+',
        'homepage'     => 'https://www.teclib-edition.com',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_OAUTHIMAP_MIN_GLPI,
                'max' => PLUGIN_OAUTHIMAP_MAX_GLPI,
            ],
            'php'    => [
                'exts' => [
                    'openssl'    => [
                        'required'  => true,
                        'function'  => 'openssl_x509_read',
                    ],
                ],
            ],
        ],
    ];
}
