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

use mod_pokcertificate\persistent\pokcertificate_templates;
use mod_pokcertificate\pok;
use mod_pokcertificate\helper;

/**
 * Tests for POK Certificate
 *
 * @package    mod_pokcertificate
 * @category   test
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class helper_test extends \advanced_testcase {

    /**
     * Validates the API key for the POK certificate.
     *
     * This function performs validation on an API key used for POK certificate operations.
     * Detailed description of what validations are performed or any specific requirements.
     *
     * @return void
     */
    public function test_pokcertificate_validate_apikey(): void {

        $this->resetAfterTest();
        // Turn off debugging.
        set_debugging(DEBUG_NONE);
        $valid = helper::pokcertificate_validate_apikey('7cb608d4-0bb6-4641-aa06-594f2fedf2a0');
        $this->assertTrue($valid);
    }

    /**
     * Tests the retrieval of incomplete student profiles for POK certificates.
     *
     * This function tests the retrieval process for incomplete student profiles
     * that are eligible for POK certificates. It verifies the correctness of
     * fetching and processing these profiles.
     *
     * @return void
     */

    public function test_pokcertificate_incompletestudentprofilelist(): void {

        $this->resetAfterTest();
        // Turn off debugging.
        set_debugging(DEBUG_NONE);
        $user = self::getDataGenerator()->create_user();
        $incompleteprofiles = helper::pokcertificate_incompletestudentprofilelist();
        $count = $incompleteprofiles['count'];
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function test_pokcertificate_coursecertificatestatuslist(): void {
        global $DB;
        $this->resetAfterTest();
        // Turn off debugging.
        set_debugging(DEBUG_NONE);
        $user = self::getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();

        // Enrol them into the course.
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->assertEquals(0, $DB->count_records('pokcertificate'));
        $pokcertificate = $this->getDataGenerator()->create_module('pokcertificate', ['course' => $course]);
        $this->assertEquals(1, $DB->count_records('pokcertificate', ['id' => $pokcertificate->id]));
        $this->assertTrue($DB->record_exists('pokcertificate', ['course' => $course->id]));
        $this->assertTrue($DB->record_exists('pokcertificate', ['id' => $pokcertificate->id]));

        $cm = get_coursemodule_from_instance('pokcertificate', $pokcertificate->id);
        $this->assertEquals($pokcertificate->id, $cm->instance);
        $this->assertEquals('pokcertificate', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $coursecertificates = helper::pokcertificate_coursecertificatestatuslist($course->id);
        $count = $coursecertificates['count'];
        $this->assertGreaterThanOrEqual(1, $count);
    }

    /**
     * Tests the validation of user inputs.
     *
     * This function performs testing on the validation process of various user inputs,
     * ensuring that input validation functions correctly identify and handle invalid
     * user data.
     *
     * @return void
     */
    public function test_validate_userinputs(): void {
        global $DB;
        $this->resetAfterTest();
        // Turn off debugging.
        set_debugging(DEBUG_NONE);
        $user = self::getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();

        // Enrol them into the course.
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->assertEquals(0, $DB->count_records('pokcertificate'));
        $pokcertificate = $this->getDataGenerator()->create_module('pokcertificate', ['course' => $course]);
        $this->assertEquals(1, $DB->count_records('pokcertificate', ['id' => $pokcertificate->id]));
        $this->assertTrue($DB->record_exists('pokcertificate', ['course' => $course->id]));
        $this->assertTrue($DB->record_exists('pokcertificate', ['id' => $pokcertificate->id]));

        $cm = get_coursemodule_from_instance('pokcertificate', $pokcertificate->id);
        $this->assertEquals($pokcertificate->id, $cm->instance);
        $this->assertEquals('pokcertificate', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $selecteditems = base64_encode(serialize([$course->id . '_' . $cm->instance . '_' . $user->id]));
        $selecteditems = (array)$selecteditems;
        if (!empty($selecteditems) && count($selecteditems) > 0) {
            $isvalid = helper::validate_userinputs($selecteditems);
            $this->assertTrue($isvalid);
        }
    }
}
