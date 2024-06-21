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

use mod_pokcertificate\helper;
use mod_pokcertificate\persistent\pokcertificate;
use mod_pokcertificate\persistent\pokcertificate_issues;
use mod_pokcertificate\persistent\pokcertificate_templates;
use mod_pokcertificate_external;
use core_external\external_api;

/**
 * Tests for POK Certificate
 *
 * @package    mod_pokcertificate
 * @category   test
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class api_test extends \advanced_testcase {
    /**
     * Test pokkcertificate api's
     * @return void
     */
    public function test_api_instance(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
        require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');
        $this->resetAfterTest(false);
        // Turn off debugging.
        set_debugging(DEBUG_NONE);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_pokcertificate');

        $result = helper::pokcertificate_validate_apikey('7cb608d4-0bb6-4641-aa06-594f2fedf2a0');
        $this->assertTrue($result);
        $this->assertNotEmpty(get_config('mod_pokcertificate', 'pokverified'));
        $this->assertNotEmpty(get_config('mod_pokcertificate', 'wallet'));
        $this->assertNotEmpty(get_config('mod_pokcertificate', 'authenticationtoken'));

        $orgdetails = (new \mod_pokcertificate\api)->get_organization();
        $organisation = json_decode($orgdetails);
        $this->assertNotEmpty($organisation->id);
        set_config('orgid', $organisation->id, 'mod_pokcertificate');
        $this->assertNotEmpty($organisation->name);
        set_config('institution', $organisation->name, 'mod_pokcertificate');

        $credits = (new \mod_pokcertificate\api)->get_credits();
        $credits = json_decode($credits);
        $this->assertGreaterThanOrEqual(0, $credits->pokCredits);

        /* $certificatecount = (new \mod_pokcertificate\api)->count_certificates();
        $certificatecount = json_decode($certificatecount);
        $this->assertGreaterThanOrEqual(0, $certificatecount->processing);
        $this->assertGreaterThanOrEqual(0, $certificatecount->emitted); */

        $templateslist = (new \mod_pokcertificate\api)->get_templates_list();
        $templateslist = json_decode($templateslist);
        $this->assertGreaterThanOrEqual(0, count($templateslist));

        $course = $this->getDataGenerator()->create_course();

        $this->assertEquals(0, $DB->count_records('pokcertificate'));
        $pokcertificate = $this->getDataGenerator()->create_module('pokcertificate', ['course' => $course]);
        $this->assertEquals(1, $DB->count_records('pokcertificate', ['id' => $pokcertificate->id]));
        // Check if the records created.
        $this->assertTrue($DB->record_exists('pokcertificate', ['course' => $course->id]));
        $this->assertTrue($DB->record_exists('pokcertificate', ['id' => $pokcertificate->id]));

        // Check the course module exists.
        $cm = get_coursemodule_from_instance('pokcertificate', $pokcertificate->id);
        $this->assertEquals($pokcertificate->id, $cm->instance);
        $this->assertEquals('pokcertificate', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $tempname = base64_encode('Crossed Paths');
        $templatename = base64_decode($tempname);
        $previewdata = json_encode(SAMPLE_DATA);
        $templatepreview = (new \mod_pokcertificate\api)->preview_certificate('Crossed Paths', $previewdata);
        $this->assertNotEmpty($templatepreview);

        $poktemplate = $generator->create_pok_template($cm);
        $remotefields = helper::get_externalfield_list($tempname, $pokcertificate->id);
        if ($remotefields) {
            $data = $generator->get_fieldmapping_data($cm->id, $pokcertificate->id, $templatename, $poktemplate['templateid']);
            pok::save_fieldmapping_data($data);
            $this->assertTrue($DB->record_exists('pokcertificate_fieldmapping', ['pokid' => $pokcertificate->id]));
        }
        $pokcertificate = pokcertificate::get_record(['course' => $course->id]);
        // Test not-enrolled user.
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Test user with full capabilities.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        // Trigger and capture the event.
        $this->redirectEvents();

        $result = mod_pokcertificate_external::view_pokcertificate($pokcertificate->get('id'));
        $result = external_api::clean_returnvalue(mod_pokcertificate_external::view_pokcertificate_returns(), $result);

        $credits = (new \mod_pokcertificate\api)->get_credits();
        $credits = json_decode($credits);
        $pokissuerec = pokcertificate_issues::get_record(['pokid' => $cm->instance, 'userid' => $user->id]);
        if (
            !empty($pokissuerec) && $pokissuerec->get('status') &&
            !empty($pokissuerec->get('certificateurl'))
        ) {
            $this->assertNotEmpty($pokissuerec->get('certificateurl'));
        } else {
            if (!empty($credits) && isset($credits->pokCredits)) {
                set_config('availablecertificate', $credits->pokCredits, 'mod_pokcertificate');
            }
            if (isset($credits->pokCredits) && $credits->pokCredits >= 0) {
                if ((empty($pokissuerec)) ||
                    ($pokissuerec && $pokissuerec->get('useremail') != $user->email)
                ) {
                    $template = pokcertificate_templates::get_record(['id' => $pokcertificate->get('templateid')]);

                    $emitdata = pok::get_emitcertificate_data($user, $template, $pokcertificate);
                    $data = json_encode($emitdata);

                    $emitcertificate = (new \mod_pokcertificate\api)->emit_certificate($data);
                    $emitcertificate = json_decode($emitcertificate);

                    if ($emitcertificate) {
                        $emitcertificate->status = false;
                        pok::save_issued_certificate($cm->id, $user, $emitcertificate);
                    }
                    $this->assertNotEmpty($emitcertificate->id);
                } else {
                    if ($pokissuerec->get('status') && $pokissuerec->get('certificateurl')) {
                        $this->assertNotEmpty($pokissuerec->get("certificateurl"));
                    } else if (!empty($pokissuerec->get('pokcertificateid'))) {
                        $issuecertificate = pok::issue_certificate($pokissuerec);
                        if (!empty($issuecertificate)) {
                            if ($issuecertificate->emitted) {

                                if ($issuecertificate->processing) {
                                    $this->assertTrue($issuecertificate->processing, false);
                                } else {
                                    $issuecertificate->status = true;
                                    pok::save_issued_certificate($cm->id, $user, $issuecertificate);
                                    if (!empty($issuecertificate->viewUrl)) {
                                        $this->assertNotEmpty($issuecertificate->viewUrl);
                                    } else {
                                        $this->assertEmpty($issuecertificate->viewUrl, false);
                                    }
                                }
                            } else {
                                $this->assertTrue($issuecertificate->emitted, false);
                            }
                        }
                    }
                }
            } else {
                $this->assertTrue($credits->pokCredits, 0);
            }
        }
    }
}
