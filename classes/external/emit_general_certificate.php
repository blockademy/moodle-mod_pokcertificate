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

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
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
class emit_general_certificate extends \external_api {
    /**
     * Get parameters for emitting general certificate templates to selected users.
     *
     * @return external_function_parameters The parameters for showing certificate templates.
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'userinputs' => new external_value(PARAM_RAW, 'userinputs'),
                'courseid' => new external_value(PARAM_INT, 'courseid'),
            ]
        );
    }

    /**
     * Emit a general certificate based on user inputs.
     *
     * This method generates and emits a general certificate using the provided user inputs.
     * It performs necessary operations to create the certificate based on the current state.
     *
     * @param string $userinputs An array containing user inputs necessary for generating the certificate.
     *                          This typically includes information such as name, date, and details required for the certificate.
     * @param int $courseid
     * @return string|false The generated certificate content as a string on success, or false on failure.
     */
    public static function execute($userinputs, $courseid = 0) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
        self::validate_parameters(
            self::execute_parameters(),
            ['userinputs' => $userinputs, 'courseid' => $courseid]
        );
        $emitcount = 0;
        $context = \context_system::instance();

        if ($courseid > 0) {
            $context = \context_course::instance($courseid, MUST_EXIST);
        }
        self::validate_context($context);
        if (has_capability('mod/pokcertificate:awardcertificate', $context)) {
            $useridsarr = explode(",", $userinputs);
            if ($useridsarr) {
                foreach ($useridsarr as $userrec) {
                    $data = unserialize(base64_decode($userrec));
                    $rec = implode("", $data);
                    $inp = explode("_", $rec);
                    $activityid = $inp[1];
                    $user = $inp[2];
                    $cm = get_coursemodule_from_instance('pokcertificate', $activityid);
                    $user = \core_user::get_user($user);
                    $user = helper::load_user_custom_fields($user);
                    $validuser = helper::check_usermapped_fielddata($cm, $user);

                    if ($validuser) {
                        pok::emit_certificate($cm->id, $user);
                        $emitcount++;
                    }
                }
            }
        }

        return ($emitcount > 0) ? true : false;
    }

    /**
     * Get returns for showing certificate templates.
     *
     * @return external_value The returns for showing certificate templates.
     */
    public static function execute_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
}
