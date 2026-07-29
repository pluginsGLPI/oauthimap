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
use League\OAuth2\Client\Token\AccessToken;

interface ProviderInterface
{
    /**
     * Return provider name.
     */
    public static function getName(): string;

    /**
     * Return provider icon (Font-Awesome identifier).
     */
    public static function getIcon(): string;

    /**
     * Return token owner details.
     *
     *
     */
    public function getOwnerDetails(AccessToken $token): ?OwnerDetails;

    /**
     * Returns default host for IMAP connection.
     */
    public function getDefaultHost(): string;

    /**
     * Returns default port for IMAP connection.
     */
    public function getDefaultPort(): ?int;

    /**
     * Returns default SSL flag ('SSL', 'TLS' or null) for IMAP connection.
     */
    public function getDefaultSslFlag(): ?string;
}
