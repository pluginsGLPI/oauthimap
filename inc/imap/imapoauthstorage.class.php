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

namespace GlpiPlugin\Oauthimap\Imap;

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
            } catch (\Laminas\Mail\Storage\Exception\ExceptionInterface $e) {
                throw new \Laminas\Mail\Storage\Exception\RuntimeException('cannot select INBOX, is this a valid transport?', 0, $e);
            }
            return;
        }

        if (!isset($params->application_id)) {
            throw new \Laminas\Mail\Storage\Exception\InvalidArgumentException('Oauth credentials must be defined');
        }

        if (!isset($params->user)) {
            throw new \Laminas\Mail\Storage\Exception\InvalidArgumentException('need at least user in params');
        }

        $host     = isset($params->host) ? $params->host : 'localhost';
        $password = ''; // No password used in Oauth process
        $port     = isset($params->port) ? $params->port : null;
        $ssl      = isset($params->ssl) ? $params->ssl : false;

        $this->protocol = new ImapOauthProtocol($params->application_id);

        if (isset($params->novalidatecert)) {
            $this->protocol->setNoValidateCert((bool)$params->novalidatecert);
        }

        $this->protocol->connect($host, $port, $ssl);
        if (!$this->protocol->login($params->user, $password)) {
            throw new \Laminas\Mail\Storage\Exception\RuntimeException('cannot login, user or password wrong');
        }
        $this->selectFolder(isset($params->folder) ? $params->folder : 'INBOX');
    }
}
