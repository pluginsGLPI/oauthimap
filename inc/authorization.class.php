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

use GlpiPlugin\Oauthimap\MailCollectorFeature;
use GlpiPlugin\Oauthimap\Oauth\OwnerDetails;
use League\OAuth2\Client\Token\AccessToken;
use GlpiPlugin\Oauthimap\Imap\ImapOauthProtocol;
use GlpiPlugin\Oauthimap\Imap\ImapOauthStorage;

class PluginOauthimapAuthorization extends CommonDBChild
{
    // From CommonGlpi
    protected $displaylist  = false;

    // From CommonDBTM
    public $dohistory       = true;

    // From CommonDBChild
    public static $itemtype = 'PluginOauthimapApplication';
    public static $items_id = 'plugin_oauthimap_applications_id';

    /**
     * Authorization owner details.
     * @var OwnerDetails
     */
    private $owner_details;

    public static function getTypeName($nb = 0)
    {
        return _n('Oauth authorization', 'Oauth authorizations', $nb, 'oauthimap');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $count = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $count = countElementsInTable(
                $this->getTable(),
                [
                    PluginOauthimapApplication::getForeignKeyField() => $item->getID(),
                ]
            );
        }
        return self::createTabEntry(self::getTypeName(1), $count);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if (!($item instanceof PluginOauthimapApplication)) {
            return;
        }

        global $DB;

        $iterator = $DB->request(
            [
                'FROM'  => self::getTable(),
                'WHERE' => [
                    PluginOauthimapApplication::getForeignKeyField() => $item->getID(),
                ]
            ]
        );

        $item->showFormHeader([
            'formtitle' => $item->fields['name'],
            'target'    => Plugin::getWebDir('oauthimap') . '/front/application.form.php',
        ]);

        echo '<div class="row">';
        echo '<div class="col text-end">';
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo Html::hidden('id', ['value' => $item->getID()]);
        echo '<button type="submit" class="btn btn-primary" name="request_authorization" value="1">';
        echo '<i class="fas fa-plus"></i> ' . __('Create an authorization', 'oauthimap');
        echo '</button>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // #mainformtable
        echo '</form>';  // [name=asset_form]

        echo '<table class="table table-striped table-hover my-4">';
        if ($iterator->count() === 0) {
            echo '<tbody><tr><th>' . __('No authorizations.', 'oauthimap') . '</th></tr></tbody>';
        } else {
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . __('Email', 'oauthimap') . '</th>';
            echo '<th></th>';
            echo '</tr>';
            echo '</thead>';

            echo '<tbody>';
            foreach ($iterator as $row) {
                echo '<tr>';

                echo '<td>' . $row['email'] . '</td>';

                echo '<td>';
                $modal_id = 'plugin_oauthimap_authorization_diagnostic_' . mt_rand();
                Ajax::createIframeModalWindow(
                    $modal_id,
                    self::getFormURLWithID($row['id']) . '&diagnose',
                    [
                        'title'  => __('Connection diagnostic', 'oauthimap'),
                        'height' => 650,
                    ]
                );
                echo '<a class="btn btn-primary btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#' . $modal_id . '">';
                echo '<i class="fas fa-bug"></i> ' . __('Diagnose', 'oauthimap');
                echo '</a>';
                echo ' ';
                echo '<a class="btn btn-primary btn-sm" href="' . self::getFormURLWithID($row['id']) . '">';
                echo '<i class="fas fa-edit"></i> ' . __('Update', 'oauthimap');
                echo '</a>';
                echo ' ';
                echo '<form method="POST" action="' . self::getFormURL() . '" style="display:inline-block;">';
                echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
                echo Html::hidden('id', ['value' => $row['id']]);
                echo '<button type="submit" class="btn btn-primary btn-sm" name="delete" value="1">';
                echo '<i class="fas fa-trash-alt"></i> ';
                echo __('Delete', 'oauthimap');
                echo '</button>';
                echo '</form>';
                echo '</td>';

                echo '</tr>';
            }
            echo '</tbody>';
        }
        echo '</table>';

        return true;
    }

    public function showForm($id, $options = [])
    {

        $options['colspan'] = 1;

        $this->initForm($id, $options);
        $this->showFormHeader($options);

        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Email', 'oauthimap');
        echo ' ';
        echo Html::showToolTip(
            __('This email address corresponds to the "user" field of the SASL XOAUTH2 authentication query.'),
            ['display' => false]
        );
        echo '</td>';
        echo '<td>';
        echo Html::input(
            'email',
            [
                'value' => $this->fields['email'],
                'style' => 'width:90%'
            ]
        );
        echo '</td>';
        echo '</tr>';

        $this->showFormButtons($options + ['candel' => false]);

        return true;
    }

    /**
     * Displays diagnostic form.
     *
     * @param array $params
     *
     * @return void
     */
    public function showDiagnosticForm(array $params)
    {

        $application = new PluginOauthimapApplication();
        if (
            !$application->getFromDB($this->fields[PluginOauthimapApplication::getForeignKeyField()])
            || ($provider = $application->getProvider()) === null
        ) {
            return;
        }

        $user    = $params['user'] ?? $this->fields['email'];
        $host    = $params['host'] ?? $provider->getDefaultHost();
        $port    = (int)($params['port'] ?? $provider->getDefaultPort());
        $ssl     = $params['ssl'] ?? $provider->getDefaultSslFlag();
        $timeout = (int)($params['timeout'] ?? 2); // 2 seconds timeout by default

        echo '<form method="post" action="' . $this->getFormURL() . '">';

        echo '<input type="hidden" name="diagnose" value="1" />';
        echo '<input type="hidden" name="id" value="' . $this->fields['id'] . '" />';
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

        echo '<table class="tab_cadre_fixe">';

        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Email', 'oauthimap');
        echo '</td>';
        echo '<td colspan="3">';
        echo Html::input(
            'email',
            [
                'disabled' => 'disabled',
                'value'    => $user,
            ]
        );
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Server host', 'oauthimap');
        echo '</td>';
        echo '<td>';
        echo Html::input(
            'host',
            [
                'value' => $host,
            ]
        );
        echo '</td>';
        echo '<td>';
        echo __('Server port', 'oauthimap');
        echo '</td>';
        echo '<td>';
        echo Html::input(
            'port',
            [
                'type'  => 'integer',
                'min'   => 1,
                'value' => $port,
                'size'  => 5,
            ]
        );
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Security level', 'oauthimap');
        echo '</td>';
        echo '<td>';
        echo Html::select(
            'ssl',
            [
                ''    => '',
                'SSL' => __('SSL', 'oauthimap'),
                'TLS' => __('SSL + TLS', 'oauthimap'),
            ],
            [
                'selected' => $ssl,
                'class'    => 'form-select',
            ]
        );
        echo '</td>';
        echo '<td>';
        echo __('Timeout', 'oauthimap');
        echo '</td>';
        echo '<td>';
        echo Html::input(
            'timeout',
            [
                'type'  => 'integer',
                'min'   => 1,
                'max'   => 30,
                'value' => $timeout,
                'size'  => 5,
            ]
        );
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_2">';
        echo '<td class="center" colspan="8">';
        echo Html::submit(
            __('Refresh connection diagnostic', 'oauthimap'),
            [
                'name'   => 'diagnose',
                'class'  => 'btn btn-secondary',
            ]
        );
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_2">';
        echo '<td colspan="8">';
        echo '<div style="color:red; font-weight:bold; background:rgba(127, 127, 127, 0.2); padding:5px; margin-top:10px;">';
        echo '<i class="fa fa-exclamation-triangle"></i>';
        echo __('Diagnostic log contains sensitive information, such as the access token.', 'oauthimap');
        echo '</div>';
        $protocol = new ImapOauthProtocol($application->fields['id']);
        $protocol->enableDiagnostic();
        $protocol->setTimeout($timeout);
        $error = null;
        try {
            $protocol->connect($host, $port, $ssl);
            if ($protocol->login($user, '')) {
                new ImapOauthStorage($protocol); // Will automatically send 'select INBOX'.
            }
        } catch (\Throwable $e) {
            $error = $e;
        }
        echo '<div style="font-family:monospace; white-space:pre-wrap; word-break:break-all;">';
        echo $protocol->getDiagnosticLog();
        echo '</pre>';
        if ($error !== null) {
            echo '<div style="color:red; font-weight:bold;">';
            echo sprintf(__('Unexpected error: %s', 'oauthimap'), $error->getMessage());
            echo '</div>';
        }
        echo '</td>';
        echo '</tr>';

        echo '</table>';
        echo '</form>';
    }

    public function prepareInputForAdd($input)
    {
        if (!($input = $this->prepareInput($input))) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        // Unset encrypted fields input if corresponding to current value
        // (encryption produces a different value each time, so GLPI will consider them as updated on each form submit)
        foreach (['code', 'token', 'refresh_token'] as $field_name) {
            if (
                array_key_exists($field_name, $input)
                && !empty($input[$field_name]) && $input[$field_name] !== 'NULL'
                && $input[$field_name] === (new GLPIKey())->decrypt($this->fields[$field_name])
            ) {
                unset($input[$field_name]);
            }
        }

        if (!($input = $this->prepareInput($input))) {
            return false;
        }
        return parent::prepareInputForUpdate($input);
    }

    /**
     * Encrypt values of secured fields.
     *
     * @param array $input
     *
     * @return bool|array
     */
    private function prepareInput($input)
    {
        foreach (['code', 'token', 'refresh_token'] as $field_name) {
            if (
                array_key_exists($field_name, $input)
                && !empty($input[$field_name]) && $input[$field_name] !== 'NULL'
            ) {
                $input[$field_name] = (new GLPIKey())->encrypt($input[$field_name]);
            }
        }

        return $input;
    }

    /**
     * Create an authorization based on authorizarion code.
     *
     * @param int    $application_id
     * @param string $code
     *
     * @return bool
     */
    public function createFromCode(int $application_id, string $code): bool
    {
        $application = new PluginOauthimapApplication();
        if (!$application->getFromDB($application_id)) {
            return false;
        }

        $provider = $application->getProvider();

        // Get token
        try {
            $token = $provider->getAccessToken('authorization_code', ['code'  => $code]);
        } catch (\Throwable $e) {
            trigger_error(
                sprintf('Error during authorization code fetching: %s', $e->getMessage()),
                E_USER_WARNING
            );
            return false;
        }

        // Get user details
        $this->owner_details = $provider->getOwnerDetails($token);
        $email = $this->owner_details->email;
        if ($email === null) {
            trigger_error('Unable to get user email', E_USER_WARNING);
            return false;
        }

        // Save informations
        $input = [
            $application->getForeignKeyField() => $application_id,
            'code'                             => $code,
            'token'                            => json_encode($token->jsonSerialize()),
            'refresh_token'                    => $token->getRefreshToken(),
            'email'                            => $email,
        ];

        $exists = $this->getFromDBByCrit(
            [
                $application->getForeignKeyField() => $application_id,
                'email'                            => $email,
            ]
        );
        if ($exists) {
            return $this->update(['id' => $this->fields['id']] + $input);
        } else {
            return $this->add($input);
        }
    }

    /**
     * Get a fresh access token related to given email using given application.
     *
     * @param int    $application_id
     * @param string $email
     *
     * @return string|null
     */
    public static function getAccessTokenForApplicationAndEmail($application_id, $email): ?string
    {
        $application = new PluginOauthimapApplication();
        if (!$application->getFromDB($application_id)) {
            return null;
        }

        $self = new self();
        if (!$self->getFromDBByCrit([$application->getForeignKeyField() => $application_id, 'email' => $email])) {
            return null;
        }

        try {
            $token = new AccessToken(json_decode((new GLPIKey())->decrypt($self->fields['token']), true));
        } catch (\Throwable $e) {
            return null; // Field value may be corrupted
        }

        if ($token->hasExpired()) {
            // Token has expired, refresh it
            $refresh_token = (new GLPIKey())->decrypt($self->fields['refresh_token']);

            $provider = $application->getProvider();
            $token = $provider->getAccessToken(
                'refresh_token',
                [
                    'refresh_token' => $refresh_token,
                ]
            );

            $input = [
                'id' => $self->fields['id'],
                'token' => json_encode($token->jsonSerialize())
            ];
            if (!empty($token->getRefreshToken()) && $token->getRefreshToken() !== $refresh_token) {
               // Update refresh token if a new one has been received in response.
                $input['refresh_token'] = $token->getRefreshToken();
            }

            $self->update($input);
        }

        return $token->getToken();
    }

    /**
     * Get existing access token.
     *
     * @return AccessToken|null
     */
    public function getAccessToken(): ?AccessToken
    {

        try {
            $token = new AccessToken(json_decode((new GLPIKey())->decrypt($this->fields['token']), true));
        } catch (\Throwable $e) {
            return null; // Field value may be corrupted
        }

        return $token;
    }

    /**
     * Returns owner details fetched when creating authorization.
     *
     * @return OwnerDetails|null
     */
    public function getOwnerDetails(): ?OwnerDetails
    {
        return $this->owner_details;
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function post_updateItem($history = 1)
    {
        MailCollectorFeature::postUpdateAuthorization($this);
        parent::post_updateItem($history);
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function post_purgeItem()
    {
        MailCollectorFeature::postPurgeAuthorization($this);
    }

    /**
     * Install all necessary data for this class.
     */
    public static function install(Migration $migration)
    {

        global $DB;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();
        $application_fkey = PluginOauthimapApplication::getForeignKeyField();

        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");

            $query = <<<SQL
CREATE TABLE IF NOT EXISTS `$table` (
  `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
  `$application_fkey` int {$default_key_sign} NOT NULL DEFAULT '0',
  `code` text,
  `token` text,
  `refresh_token` text,
  `email` varchar(255) NOT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `$application_fkey` (`$application_fkey`),
  KEY `date_creation` (`date_creation`),
  KEY `date_mod` (`date_mod`),
  UNIQUE KEY `unicity` (`$application_fkey`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->query($query) or die($DB->error());
        } else {
            if (!$DB->fieldExists($table, 'refresh_token')) {
               // V1.3.1: add new refresh_token field
                $migration->addField(
                    $table,
                    'refresh_token',
                    'text',
                    [
                        'after'     => 'token',
                        'nodefault' => true,
                    ]
                );

                $iterator = $DB->request(['FROM' => $table]);
                foreach ($iterator as $row) {
                     $token_fields = json_decode((new GLPIKey())->decrypt($row['token']), true);
                    if (isset($token_fields['refresh_token'])) {
                        $migration->addPostQuery(
                            $DB->buildUpdate(
                                $table,
                                [
                                    'refresh_token' => (new GLPIKey())->encrypt($token_fields['refresh_token']),
                                ],
                                [
                                    'id'            => $row['id']
                                ]
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * Uninstall previously installed data for this class.
     */
    public static function uninstall(Migration $migration)
    {

        $table = self::getTable();
        $migration->displayMessage("Uninstalling $table");
        $migration->dropTable($table);
    }
}
