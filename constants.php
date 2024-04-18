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

namespace mod_pokcertificate;

/**
 * Class constants
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$prodtype = get_config('mod_pokcertificate', 'prodtype');

$tempurl = ($prodtype == 1 ? 'https://templates.credentity.xyz' : 'https://templates.pok.tech');
define('TEMPLATE_MANAGER_ROOT', $tempurl);

$minturl = ($prodtype == 1 ? 'https://mint.credentity.xyz' : 'https://mint.pok.tech');
define('MINTER_ROOT', $minturl);

$apikeysurl = ($prodtype == 1 ? 'https://api-keys.credentity.xyz' : 'https://api-keys.pok.tech');
define('API_KEYS_ROOT', $apikeysurl);

$rbacurl = ($prodtype == 1 ? 'https://rbac.credentity.xyz' : 'https://rbac.pok.tech');
define('RBAC_ROOT', $rbacurl);

$custodianurl = ($prodtype == 1 ? 'https://custodian.credentity.xyz' : 'https://custodian.pok.tech');
define('CUSTODIAN_ROOT', $custodianurl);
