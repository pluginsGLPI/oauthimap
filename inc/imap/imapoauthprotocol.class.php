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

use Glpi\Mail\Protocol\ProtocolInterface;
use Laminas\Mail\Protocol\Exception\RuntimeException;
use Laminas\Mail\Protocol\Imap;
use PluginOauthimapAuthorization;

use function Safe\fwrite;
use function Safe\preg_match;

class ImapOauthProtocol extends Imap implements ProtocolInterface
{
    /**
     * Prefix to use when writing a sent line in diagnostic log.
     *
     * @var string
     */
    private const DIAGNOSTIC_PREFIX_SENT = '>>> ';

    /**
     * Prefix to use when writing a received line in diagnostic log.
     *
     * @var string
     */
    private const DIAGNOSTIC_PREFIX_RECEIVED = '<<< ';

    /**
     * ID of PluginOauthimapApplication to use.
     *
     * @var int
     */
    private $application_id;

    /**
     * Indicates whether diagnostic is enabled.
     *
     * @var boolean
     */
    private $diagnostic_enabled = false;

    /**
     * Diagnostic log.
     *
     * @var string[]
     */
    private $diagnostic_log = [];

    /**
     * Connection timeout.
     *
     * @var int
     */
    private $timeout = self::TIMEOUT_CONNECTION;

    /**
     * @param  int   $application_id   ID of PluginOauthimapApplication to use
     */
    public function __construct($application_id)
    {
        $this->application_id = $application_id;
        parent::__construct();
    }

    /**
     * Almost identical to parent class method, just to be able to redefine timeout in case of diagnostic.
     *
     * {@inheritDoc}
     */
    public function connect($host, $port = null, $ssl = false)
    {
        $transport = 'tcp';
        $isTls     = false;

        if ($ssl) {
            $ssl = strtolower($ssl);
        }

        switch ($ssl) {
            case 'ssl':
                $transport = 'ssl';
                if (!$port) {
                    $port = 993;
                }
                break;
            case 'tls':
                $isTls = true;
                // break intentionally omitted
                // no break
            default:
                if (!$port) {
                    $port = 143;
                }
        }

        $this->socket = $this->setupSocket($transport, $host, $port, $this->timeout);

        if (!$this->assumedNextLine('* OK')) {
            throw new RuntimeException('host doesn\'t allow connection');
        }

        if ($isTls) {
            $result = $this->requestAndResponse('STARTTLS');
            $result = $result && stream_socket_enable_crypto($this->socket, true, $this->getCryptoMethod());
            if (!$result) {
                throw new RuntimeException('cannot enable TLS');
            }
        }
    }

    public function login($user, $password)
    {
        $token = PluginOauthimapAuthorization::getAccessTokenForApplicationAndEmail($this->application_id, $user);

        if ($token === null) {
            trigger_error('Unable to get access token', E_USER_WARNING);

            return false;
        }

        $this->sendRequest(
            'AUTHENTICATE',
            [
                'XOAUTH2',
                base64_encode("user={$user}\001auth=Bearer {$token}\001\001"),
            ],
        );

        while (true) {
            $response = '';
            $isPlus   = $this->readLine($response, '+', true);
            if ($isPlus) {
                // Send empty client response.
                $this->sendRequest('');
            } else {
                if (
                    preg_match('/^NO /i', $response) || preg_match('/^BAD /i', $response)
                ) {
                    return false;
                }
                if (preg_match('/^OK /i', $response) !== 0) {
                    return true;
                }
            }
        }
    }

    /**
     * Almost identical to parent class method, some `$this->addToDiagnosticLog()` calls were added.
     *
     * {@inheritDoc}
     */
    public function sendRequest($command, $tokens = [], &$tag = '')
    {
        if (!$tag) {
            ++$this->tagCount;
            $tag = 'TAG' . $this->tagCount;
        }

        $line = $tag . ' ' . $command;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $tosend = $line . ' ' . $token[0] . "\r\n";
                fwrite($this->socket, $tosend);
                $this->addToDiagnosticLog($tosend, self::DIAGNOSTIC_PREFIX_SENT);
                if (!$this->assumedNextLine('+ ')) {
                    throw new RuntimeException('cannot send literal string');
                }
                $line = $token[1];
            } else {
                $line .= ' ' . $token;
            }
        }

        $tosend = $line . "\r\n";
        fwrite($this->socket, $line . "\r\n");
        $this->addToDiagnosticLog($tosend, self::DIAGNOSTIC_PREFIX_SENT);
    }

    /**
     * Almost identical to parent class method, `$this->addToDiagnosticLog()` call added.
     *
     * {@inheritDoc}
     */
    protected function nextLine()
    {
        $line = fgets($this->socket);
        if ($line === false) {
            throw new RuntimeException('cannot read - connection closed?');
        }
        $this->addToDiagnosticLog($line, self::DIAGNOSTIC_PREFIX_RECEIVED);

        return $line;
    }

    /**
     * Enable diagnostic.
     *
     * @return void
     */
    public function enableDiagnostic(): void
    {
        $this->diagnostic_enabled = true;
    }

    /**
     * Get the diagnostic log.
     *
     * @return string
     */
    public function getDiagnosticLog(): string
    {
        return implode('', $this->diagnostic_log);
    }

    /**
     * Add line to diagnostic log.
     *
     * @param string $line
     * @param string $prefix
     *
     * @return void
     */
    private function addToDiagnosticLog(string $line, string $prefix = '')
    {
        if (!$this->diagnostic_enabled) {
            return;
        }
        $this->diagnostic_log[] = $prefix . $line;
    }

    /**
     * Defines socket timeout.
     *
     * @param int $timeout
     *
     * @return void
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }
}
