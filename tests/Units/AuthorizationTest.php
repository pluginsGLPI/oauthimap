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

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Oauthimap\Oauth\OwnerDetails;
use GlpiPlugin\Oauthimap\Provider\Azure;
use League\OAuth2\Client\Token\AccessToken;
use PluginOauthimapApplication;
use PluginOauthimapAuthorization;
use RuntimeException;

class AuthorizationTest extends DbTestCase
{
    private function createApplication(): PluginOauthimapApplication
    {
        return $this->createItem(
            PluginOauthimapApplication::class,
            [
                'name'          => $this->getUniqueString(),
                'provider'      => Azure::class,
                'client_id'     => 'fake_client_id',
                'client_secret' => 'fake_client_secret',
            ],
            ['client_secret'],
        );
    }

    public function testCreateFromCodeFailsWhenTokenExchangeFails(): void
    {
        $application = $this->createApplication();

        $provider = $this->getMockBuilder(Azure::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAccessToken'])
            ->getMock();
        $provider->method('getAccessToken')->willThrowException(new RuntimeException('invalid_grant'));

        $authorization = new PluginOauthimapAuthorization();
        $this->assertFalse($authorization->createFromCode($application->getID(), 'a_code', $provider));
        $this->assertStringContainsString('invalid_grant', $authorization->getLastError());
        $this->hasPhpLogRecordThatContains('Error during authorization code fetching: invalid_grant', 'Warning');
    }

    public function testCreateFromCodeFailsWhenEmailIsMissing(): void
    {
        $application = $this->createApplication();

        $owner_details            = new OwnerDetails();
        $owner_details->email     = null;
        $owner_details->firstname = 'John';
        $owner_details->lastname  = 'Doe';

        $provider = $this->getMockBuilder(Azure::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAccessToken', 'getOwnerDetails'])
            ->getMock();
        $provider->method('getAccessToken')->willReturn(new AccessToken(['access_token' => 'a_token']));
        $provider->method('getOwnerDetails')->willReturn($owner_details);

        $authorization = new PluginOauthimapAuthorization();
        $this->assertFalse($authorization->createFromCode($application->getID(), 'a_code', $provider));
        $this->assertEquals(
            'The authenticated account does not expose an email address',
            $authorization->getLastError(),
        );
        $this->hasPhpLogRecordThatContains('Unable to get user email', 'Warning');
    }

    public function testCreateFromCodeSucceeds(): void
    {
        $application = $this->createApplication();

        $owner_details        = new OwnerDetails();
        $owner_details->email = 'user@example.com';

        $provider = $this->getMockBuilder(Azure::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAccessToken', 'getOwnerDetails'])
            ->getMock();
        $provider->method('getAccessToken')->willReturn(
            new AccessToken(['access_token' => 'a_token', 'refresh_token' => 'a_refresh_token']),
        );
        $provider->method('getOwnerDetails')->willReturn($owner_details);

        $authorization = new PluginOauthimapAuthorization();
        $this->assertTrue($authorization->createFromCode($application->getID(), 'a_code', $provider));
        $this->assertNull($authorization->getLastError());
        $this->assertEquals('user@example.com', $authorization->fields['email']);
    }
}
