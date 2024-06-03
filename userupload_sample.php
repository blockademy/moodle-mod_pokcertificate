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
 * pokcertificate User bulk update sample file
 *
 * @package     mod_pokcertificate
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

$format = optional_param('format', 'csv', PARAM_ALPHA);
$systemcontext = \context_system::instance();
$localfields = [];
if ($format) {
    $fields = [
        'username'      => 'username',
        'studentname'   => 'studentname',
        'surname'       => 'surname',
        'email'         => 'email',
        'studentid'     => 'studentid',
    ];
    $allcustomfields = profile_get_custom_fields();
    $customfields = array_combine(array_column($allcustomfields, 'shortname'), $allcustomfields);
    foreach ((array)$customfields as $key => $field) {
        $fields['profile_field_' . $key] = 'profile_field_' . $field->shortname;
    }

    switch ($format) {
        case 'csv':
            user_download_csv($fields);
    }
    die;
}

/**
 * Generates a CSV file containing user data based on the provided fields array and prompts the user to download it.
 *
 * @param array $fields An array containing the fields to include in the CSV file.
 * @return void
 */
function user_download_csv($fields) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/csvlib.class.php');
    $filename = clean_filename(get_string('students'));
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($fields);

    $userprofiledata = [
        'hemanth',
        'Hemanth',
        'Reddy',
        'hemanath@mail.com',
        '1101',
    ];
    $csvexport->add_data($userprofiledata);

    $userprofiledata = [
        'radhika',
        'Radhika',
        'Dubey',
        'radhika@mail.com',
        '1102',
    ];
    $csvexport->add_data($userprofiledata);
    $csvexport->download_file();
    die;
}
