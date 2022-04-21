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

namespace GlpiPlugin\Oauthimap\Provider;

use GlpiPlugin\Oauthimap\Oauth\OwnerDetails;
use League\OAuth2\Client\Token\AccessToken;

class Azure extends \TheNetworg\OAuth2\Client\Provider\Azure implements ProviderInterface
{
    public static function getName(): string
    {
        return 'Azure';
    }

    public static function getIcon(): string
    {
        return 'fa-windows';
    }

    public function getOwnerDetails(AccessToken $token): ?OwnerDetails
    {
        /* @var \TheNetworg\OAuth2\Client\Provider\AzureResourceOwner $owner */
        $owner = $this->getResourceOwner($token);

        $owner_details = new OwnerDetails();
        if (($email = $owner->claim('email')) !== null) {
            $owner_details->email = $email;
        } elseif (($upn = $owner->claim('upn')) !== null) {
            $owner_details->email = $upn;
        }
        $owner_details->firstname = $owner->getFirstName();
        $owner_details->lastname = $owner->getLastName();

        return $owner_details;
    }

    public function getDefaultHost(): string
    {
        return 'outlook.office365.com';
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
