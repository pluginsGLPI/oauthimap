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

namespace GlpiPlugin\Oauthimap\Imap;

use Laminas\Mail\Storage\Exception\ExceptionInterface;
use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Laminas\Mail\Storage\Exception\RuntimeException;
use Laminas\Mail\Storage\Imap;

class ImapOauthStorage extends Imap
{
    public function __construct($params)
    {
        if (is_array($params)) {
            $params = (object) $params;
        }

        $this->has['flags'] = true;

        if ($params instanceof ImapOauthProtocol) {
            $this->protocol = $params;
            try {
                $this->selectFolder('INBOX');
            } catch (ExceptionInterface $e) {
                throw new RuntimeException('cannot select INBOX, is this a valid transport?', 0, $e);
            }

            return;
        }

        if (!isset($params->application_id)) {
            throw new InvalidArgumentException('Oauth credentials must be defined');
        }

        if (!isset($params->user)) {
            throw new InvalidArgumentException('need at least user in params');
        }

        $host     = $params->host ?? 'localhost';
        $password = ''; // No password used in Oauth process
        $port     = $params->port ?? null;
        $ssl      = $params->ssl ?? false;

        $this->protocol = new ImapOauthProtocol($params->application_id);

        if (isset($params->novalidatecert)) {
            $this->protocol->setNoValidateCert((bool) $params->novalidatecert);
        }

        $this->protocol->connect($host, $port, $ssl);
        if (!$this->protocol->login($params->user, $password)) {
            throw new RuntimeException('cannot login, user or password wrong');
        }
        $this->selectFolder($params->folder ?? 'INBOX');
    }
}
