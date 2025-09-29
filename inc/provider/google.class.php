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

namespace GlpiPlugin\Oauthimap\Provider;

use GlpiPlugin\Oauthimap\Oauth\OwnerDetails;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;

class Google extends \League\OAuth2\Client\Provider\Google implements ProviderInterface
{
    public static function getName(): string
    {
        return 'Google';
    }

    public static function getIcon(): string
    {
        return 'fa-google';
    }

    public function getOwnerDetails(AccessToken $token): ?OwnerDetails
    {
        /** @var GoogleUser $owner */
        $owner = $this->getResourceOwner($token);

        $owner_details            = new OwnerDetails();
        $owner_details->email     = $owner->getEmail();
        $owner_details->firstname = $owner->getFirstName();
        $owner_details->lastname  = $owner->getLastName();

        return $owner_details;
    }

    public function getDefaultHost(): string
    {
        return 'imap.gmail.com';
    }

    public function getDefaultPort(): ?int
    {
        return 993;
    }

    public function getDefaultSslFlag(): ?string
    {
        return 'SSL';
    }
}
