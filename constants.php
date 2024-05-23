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

$prodtype = get_config('mod_pokcertificate', 'prodtype');

$tempurl = ($prodtype == 2 ? 'https://templates.pok.tech' : 'https://templates.credentity.xyz');
define('TEMPLATE_MANAGER_ROOT', $tempurl);

$minturl = ($prodtype == 2 ? 'https://mint.pok.tech' : 'https://minter.credentity.xyz');
define('MINTER_ROOT', $minturl);

$apikeysurl = ($prodtype == 2 ? 'https://api-keys.pok.tech' : 'https://api-keys.credentity.xyz');
define('API_KEYS_ROOT', $apikeysurl);

$rbacurl = ($prodtype == 2 ? 'https://rbac.pok.tech' : 'https://rbac.credentity.xyz');
define('RBAC_ROOT', $rbacurl);

$custodianurl = ($prodtype == 2 ? 'https://custodian.pok.tech' : 'https://custodian.credentity.xyz');
define('CUSTODIAN_ROOT', $custodianurl);

$sampledata = [
    "name" => "John Galt",
    "title" => "Engineer",
    "date" => 1704423600000,
    "institution" => "Ohio State University",
];
define('SAMPLE_DATA', $sampledata);
