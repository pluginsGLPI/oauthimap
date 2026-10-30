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

namespace GlpiPlugin\Oauthimap\Tests\Units;

use Glpi\Exception\RedirectException;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Oauthimap\MailCollectorFeature;
use GlpiPlugin\Oauthimap\Provider\Google;
use MailCollector;
use PluginOauthimapApplication;

class MailCollectorFeatureTest extends DbTestCase
{
    public function testNewAuthorizationCallbackIsSerializable(): void
    {
        $application = $this->createItem(
            PluginOauthimapApplication::class,
            [
                'name'          => $this->getUniqueString(),
                'provider'      => Google::class,
                'client_id'     => 'fake_client_id',
                'client_secret' => 'fake_client_secret',
                'is_active'     => 1,
            ],
            ['client_secret'],
        );
        $collector = $this->createItem(
            MailCollector::class,
            [
                'name' => $this->getUniqueString(),
                'host' => '{outlook.office365.com:993/imap/ssl}INBOX',
            ],
            ['host'],
        );

        $collector->input = [
            'plugin_oauthimap_applications_id'   => $application->getID(),
            'plugin_oauthimap_authorizations_id' => -1,
        ];

        try {
            MailCollectorFeature::handleMailCollectorSaving($collector);
            $this->fail('A redirection to the provider authorization URL was expected.');
        } catch (RedirectException) {
            // Expected redirection to the provider.
        }

        // Session is serialized at the end of the request.
        $callable = unserialize(serialize($_SESSION['plugin_oauthimap_callback_callable']));
        $this->assertIsCallable($callable);
        $this->assertSame(
            [MailCollector::getForeignKeyField() => $collector->getID()],
            $_SESSION['plugin_oauthimap_callback_params'],
        );
    }
}
