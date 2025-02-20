<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * List of all pokcertificates constants
 *
 * @package mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pokcertificate;

defined('MOODLE_INTERNAL') || die;

define('FREE', 0);
define('PAID', 1);
$prodtype = get_config('mod_pokcertificate', 'prodtype');

$tempurl = ($prodtype == 2 ? 'https://templates.pok.tech' : 'https://templates.credentity.xyz');
define('TEMPLATE_MANAGER_ROOT', $tempurl);

$minturl = ($prodtype == 2 ? 'https://minter.pok.tech' : 'https://minter.credentity.xyz');
define('MINTER_ROOT', $minturl);

$creditsurl = ($prodtype == 2 ? 'https://credits.pok.tech' : 'https://credits.credentity.xyz');
define('CREDITS_ROOT', $creditsurl);

$apiurl = ($prodtype == 2 ? 'https://api.pok.tech' : 'https://api.credentity.xyz');
define('API_ROOT', $apiurl);

$apikeysurl = ($prodtype == 2 ? 'https://api-keys.pok.tech' : 'https://api-keys.credentity.xyz');
define('API_KEYS_ROOT', $apikeysurl);

$rbacurl = ($prodtype == 2 ? 'https://rbac.pok.tech' : 'https://rbac.credentity.xyz');
define('RBAC_ROOT', $rbacurl);

$custodianurl = ($prodtype == 2 ? 'https://custodian.pok.tech' : 'https://custodian.credentity.xyz');
define('CUSTODIAN_ROOT', $custodianurl);

$sampledata = [
    "name" => "John Galt",
    "title" => "Engineer",
    "date" => time(),
    "institution" => "Ohio State University",
];
define('SAMPLE_DATA', $sampledata);
