<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_pokcertificate\cron;
/**
 * Validation callback function - verified the column line of csv file.
 * Converts standard column names to lowercase.
 * @param csv_import_reader $cir
 * @param array $stdfields standard user fields
 * @param array $profilefields custom profile fields
 * @param moodle_url $returnurl return url in case of any error
 * @return array list of fields
 */
use csv_import_reader;
use moodle_url;
use core_text;
use moodle_exception;
use html_writer;
class progresslibfunctions {
    /**
     * [uu_validate_user_upload_columns description]
     * @param  csv_import_reader $cir           [description]
     * @param  array             $stdfields     [standarad fields in user table]
     * @param  array             $profilefields [profile fields in user table]
     * @param  moodle_url        $returnurl     [moodle return page url]
     * @return array                            [validated fields in csv uploaded]
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

        // test columns
        $processed = array();

        foreach ($columns as $key => $unused) {
            $field = $columns[$key];
            $lcfield = core_text::strtolower($field);
            if (in_array($field, $stdfields) || in_array($lcfield, $stdfields)) {
                // standard fields are only lowercase
                $newfield = $lcfield;
            } else if (in_array($field, $profilefields)) {
                // exact profile field name match - these are case sensitive
                $newfield = $field;
            } else if (in_array($lcfield, $profilefields)) {
                // hack: somebody wrote uppercase in csv file, but the system knows only lowercase profile field
                $newfield = $lcfield;
            } else if (preg_match('/^(cohort|user|group|type|role|enrolperiod)\d+$/', $lcfield)) {
                // special fields for enrolments
                $newfield = $lcfield;
            }else if (preg_match('/^profile_field_/', $lcfield)) {
                $newfield = $lcfield;
               
            }else {
                $cir->close();
                $cir->cleanup();
                echo '<div class=local_users_sync_error>' . get_string('invalidfieldname', 'mod_pokcertificate', $field) . '</div>';
                $this->continue_button($returnurl);
            }
            if (in_array($newfield, $processed)) {
                $cir->close();
                $cir->cleanup();
                echo '<div class=local_users_sync_error>' . get_string('duplicatefieldname', 'mod_pokcertificate', $newfield) . '</div>';   
                $this->continue_button($returnurl);
            }
            $processed[$key] = $newfield;

        }

        return $processed;
    }

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
