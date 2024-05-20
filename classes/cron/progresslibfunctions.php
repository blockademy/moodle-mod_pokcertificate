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
 * student bulk update.
 *
 * @package   mod_pokcertificate
 * @copyright 2024 Moodle India Information Solutions Pvt Ltd
 * @author    2024 Narendra.Patel <narendra.patel@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pokcertificate\cron;
use csv_import_reader;
use moodle_url;
use core_text;
use moodle_exception;
use html_writer;

/**
 * Class progresslibfunctions
 *
 * This class provides utility functions for validating user upload columns and displaying continue buttons.
 */
class progresslibfunctions {

    /**
     * Validates the columns of a CSV file for user upload.
     *
     * This method validates the columns of a CSV file for user upload against standard fields and profile fields.
     * It checks if the columns are valid and returns the processed fields.
     *
     * @param csv_import_reader $cir           The CSV import reader object.
     * @param array             $stdfields     Array of standard fields in the user table.
     * @param array             $profilefields Array of profile fields in the user table.
     * @param moodle_url        $returnurl     The Moodle return page URL.
     * @return array                           Array of validated fields in the CSV uploaded.
     */
    public function uu_validate_user_upload_columns(csv_import_reader $cir, $stdfields, $profilefields, moodle_url $returnurl) {

        $columns = $cir->get_columns();
        if (empty($columns)) {
            $cir->close();
            $cir->cleanup();
            echo '<div class=local_users_sync_error>' . get_string('cannotreadtmpfile', 'mod_pokcertificate', $field) . '</div>';
                $this->continue_button($returnurl);
        }
        if (count($columns) < 5) {
            $cir->close();
            $cir->cleanup();
            echo '<div class=local_users_sync_error>' . get_string('csvfewcolumns', 'mod_pokcertificate', $field) . '</div>';
                $this->continue_button($returnurl);
        }

        // Test columns.
        $processed = [];

        foreach ($columns as $key => $unused) {
            $field = $columns[$key];
            $lcfield = core_text::strtolower($field);
            if (in_array($field, $stdfields) || in_array($lcfield, $stdfields)) {
                // Standard fields are only lowercase.
                $newfield = $lcfield;
            } else if (in_array($field, $profilefields)) {
                // Exact profile field name match - these are case sensitive.
                $newfield = $field;
            } else if (in_array($lcfield, $profilefields)) {
                // Hack: somebody wrote uppercase in csv file, but the system knows only lowercase profile field.
                $newfield = $lcfield;
            } else if (preg_match('/^(cohort|user|group|type|role|enrolperiod)\d+$/', $lcfield)) {
                // Special fields for enrolments.
                $newfield = $lcfield;
            } else if (preg_match('/^profile_field_/', $lcfield)) {
                $newfield = $lcfield;
            } else {
                $cir->close();
                $cir->cleanup();
                echo '<div class=local_users_sync_error>' . get_string('invalidfieldname', 'mod_pokcertificate', $field) . '</div>';
                $this->continue_button($returnurl);
            }
            if (in_array($newfield, $processed)) {
                $cir->close();
                $cir->cleanup();
                echo '<div class=local_users_sync_error>'
                        . get_string('duplicatefieldname',
                                     'mod_pokcertificate',
                                     $newfield) .
                     '</div>';
                $this->continue_button($returnurl);
            }
            $processed[$key] = $newfield;

        }

        return $processed;
    }

    /**
     * Displays a continue button.
     *
     * This method generates and displays a continue button with the provided return URL.
     *
     * @param moodle_url $returnurl The Moodle return page URL.
     */
    public function continue_button($returnurl) {
        echo html_writer::tag(
            'a',
            get_string('continue'),
            [
                'href' => $returnurl,
                'class' => "btn btn-primary",
            ]
        );
        exit;
    }
}
