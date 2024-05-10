<?php
/*
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_users
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
$format = optional_param('format', 'csv', PARAM_ALPHA);

$systemcontext = \context_system::instance();
if ($format) {
    $fields = [
        'username'      => 'username',
        'studentname'   => 'studentname',
        'surname'       => 'surname',
        'email'         => 'email',
        'studentid'     => 'studentid'
    ];

    switch ($format) {
        case 'csv' : user_download_csv($fields);
    }
    die;
}

function user_download_csv($fields) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/csvlib.class.php');
    $filename = clean_filename(get_string('students'));
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($fields);
    $users = $DB->get_records_sql('SELECT id, username, firstname, lastname, email, idnumber FROM {user} WHERE deleted = 0 AND suspended = 0 AND id > 2');
    foreach($users as $user) {
        $userprofiledata = [
            $user->username,
            $user->firstname,
            $user->lastname,
            $user->email,
            $user->idnumber
        ];
        $csvexport->add_data($userprofiledata);
    }
    $csvexport->download_file();
    die;
}
