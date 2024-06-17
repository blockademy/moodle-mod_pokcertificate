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
 * Class issue_certitficate_user
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pokcertificate\task;

use mod_pokcertificate\pok;
use mod_pokcertificate\persistent\pokcertificate_issues;
use mod_pokcertificate\persistent\pokcertificate;

/**
 * Issue certificates scheduled task class.
 *
 * @package     mod_pokcertificate
 * @copyright    2024 Moodle India Information Solutions Pvt Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issue_certitficate_user extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task.
     *
     * @return string
     * @uses \tool_certificate\template
     */
    public function get_name() {
        return get_string('issuecertificatestask', 'pokcertificate');
    }

    /**
     * Execute.
     */
    public function execute() {

        $pokcertificates = $this->get_pokcertificates();
        foreach ($pokcertificates as $pokcertificate) {

            try {
                [$course, $cm] = get_course_and_cm_from_instance(
                    $pokcertificate->id,
                    'pokcertificate',
                    $pokcertificate->course
                );
                if (!$cm->visible) {
                    // Skip pokcertificate modules not visible.
                    continue;
                }
                // Get all the users with requirements that had not been issued.
                $users = pok::get_users_to_issue($pokcertificate, $cm);

                // Issue the certificate.
                foreach ($users as $user) {

                    $pokissuerec = pokcertificate_issues::get_record([
                        'pokid' => $pokcertificate->id,
                        'userid' => $user->userid
                    ]);
                    if ($pokissuerec) {
                        $issuecertificate = pok::issue_certificate($pokissuerec);
                        if (!empty($issuecertificate)) {

                            if ($issuecertificate->emitted && !$issuecertificate->processing) {
                                if (!empty($issuecertificate->viewUrl)) {
                                    $user->id = $user->userid;
                                    $user->email = $user->useremail;
                                    $issuecertificate->status = true;
                                    pok::save_issued_certificate($cm->id, $user, $issuecertificate);
                                    $completion = new \completion_info($course);
                                    $pokrecord = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);
                                    if (
                                        $completion->is_enabled($cm) && $pokrecord->get('completionsubmit')
                                        && !empty($pokissuerec->get('certificateurl'))
                                    ) {
                                        $completion->update_state($cm, COMPLETION_COMPLETE);
                                    }
                                    mtrace("... issued pokcertificate $pokcertificate->id for user
                                            $user->id on course $course->id");
                                }
                            }
                        }
                    }
                }
            } catch (\moodle_exception $e) {
                // Skip if $cm or $course not found anymore in DB.
                continue;
            }
        }
    }

    /**
     * Get all the pokcertificates with templates mapped.
     *
     * @return array
     */
    public function get_pokcertificates(): array {
        global $DB;
        $sql = "SELECT pok.*
                FROM {pokcertificate} pok
                JOIN {pokcertificate_templates} pokt
                ON pok.templateid = pokt.id
                WHERE pok.templateid != 0 ";
        return $DB->get_records_sql($sql);
    }
}
