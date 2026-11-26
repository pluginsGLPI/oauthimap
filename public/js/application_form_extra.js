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

(function ($) {
    'use strict';

    document.querySelectorAll('[data-oauthimap-provider-config]:not([data-oauthimap-ready])').forEach((config) => {
        config.dataset.oauthimapReady = '1';

        const rand = config.dataset.oauthimapRand;
        const documentation_urls = JSON.parse(config.dataset.oauthimapDocumentationUrls);

        const updateDocumentationLink = function () {
            const provider = $(`#dropdown_provider${rand}`).val();

            const url = documentation_urls.hasOwnProperty(provider)
                ? documentation_urls[provider]
                : null;

            $('.help-link').attr('href', url).toggle(url !== null);
        };

        const onProviderChange = function () {
            const provider = $.escapeSelector($(this).val()); // escape selector as il contient des slashes
            $(`[data-provider="${provider}"]`).closest('.form-field').show();
            $(`[data-provider]:not([data-provider="${provider}"])`).closest('.form-field').hide();

            updateDocumentationLink();
        };

        $(`#dropdown_provider${rand}`).change(onProviderChange);
        onProviderChange.call($(`#dropdown_provider${rand}`));
    });
})(jQuery);
