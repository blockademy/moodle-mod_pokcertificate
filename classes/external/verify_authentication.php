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

namespace mod_pokcertificate\external;

global $CFG;
require_once("{$CFG->libdir}/externallib.php");

use external_api;
use external_files;
use external_format_value;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use util;
use mod_pokcertificate\pok;
use mod_pokcertificate\helper;

/**
 * pokcertificate external functions
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verify_authentication extends \external_api {
    /**
     * Get parameters for verifying authentication.
     *
     * @return external_function_parameters The parameters for verifying authentication.
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'prodtype' => new external_value(PARAM_INT, get_string('prodtype', 'mod_pokcertificate')),
                'authtoken' => new external_value(PARAM_RAW, get_string('authtoken', 'mod_pokcertificate')),
                'institution' => new external_value(PARAM_TEXT, get_string('institution', 'mod_pokcertificate')),
            ]
        );
    }

    /**
     * Verify authentication for a POK using an authentication token and institution.
     *
     * This method verifies the authentication for a specific product type using the provided
     * authentication token and institution information.
     *
     * @param string $prodtype The type of product for which authentication is being verified.
     * @param string $authtoken The authentication token used for verification.
     * @param string $institution The institution against which the authentication is performed.
     * @return bool Returns true if authentication is successful, false otherwise.
     */
    public static function execute($prodtype, $authtoken, $institution) {
        global $CFG;

        set_config('prodtype', $prodtype, 'mod_pokcertificate');
        require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['prodtype' => $prodtype, 'authtoken' => $authtoken, "institution" => $institution]
        );
        $context = \context_system::instance();
        self::validate_context($context);
        if (has_capability('moodle/course:manageactivities', $context)) {
            $result = helper::pokcertificate_validate_apikey($params['authtoken']);

            if ($result) {
                $orgdetails = (new \mod_pokcertificate\api())->get_organization();
                $organisation = json_decode($orgdetails);
                if (isset($organisation->wallet) && isset($organisation->name)) {
                    set_config('orgid', $organisation->id, 'mod_pokcertificate');
                    set_config('institution', $organisation->name, 'mod_pokcertificate');
                }
                set_config('availablecertificate', $organisation->availableCredits, 'mod_pokcertificate');
                set_config('pendingcertificates', $organisation->processingCredentials, 'mod_pokcertificate');
                set_config('issuedcertificates', $organisation->emittedCredentials, 'mod_pokcertificate');
                $msg = get_string("success");
                return ["status" => 0, "msg" => $msg, "response" => $orgdetails];
            } else {
                $msg = get_string("error");
                return ["status" => 1, "msg" => $msg, "response" => ''];
            }
        } else {
            $msg = get_string('accessdenied', 'pokcertificate');
            return ["status" => 1, "msg" => $msg, "response" => ''];
        }
    }

    /**
     * Get parameters for verifying authentication returns.
     *
     * @return external_single_structure The parameters for verifying authentication returns.
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'status'  => new external_value(PARAM_TEXT, get_string('status')),
                'msg'  => new external_value(PARAM_RAW, get_string('error')),
                'response'  => new external_value(PARAM_RAW, get_string('response', 'mod_pokcertificate')),
            ]
        );
    }
}
