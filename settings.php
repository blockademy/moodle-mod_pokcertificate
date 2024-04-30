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
 * Page module admin settings and defaults
 *
 * @package mod_pokcertificate
 * @copyright 2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;
if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $settings->add(
        new admin_setting_heading(
            'pokcertificate/settings_msg',
            '',
            '<div class="" >' . get_string('verifyauth', 'pokcertificate') . '
            <a target="_blank" class="bt btn-primary"
            style="padding: 7px 18px; border-radius: 4px; color: white; background-color: #2578dd; margin-left: 5px;"
            href="' . $CFG->wwwroot . '/mod/pokcertificate/pokcertificate.php' . '" >' . get_string('clickhere', 'mod_pokcertificate') . '
            </a></div><br>'
        )
    );
}
