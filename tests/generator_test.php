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
 * PHPUnit data generator testcase
 *
 * @package    mod_pokcertificate
 * @category   test
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator_test extends \advanced_testcase {

    /**
     * Test create pokkcertificate activity
     * @return void
     */
    public function test_create_instance(): void {
        global $DB;

        $this->resetAfterTest();
        // Turn off debugging.
        set_debugging(DEBUG_NONE);

        // Start event capturing.
        $eventsink = $this->redirectEvents();

        $course = $this->getDataGenerator()->create_course();

        $this->assertEquals(0, $DB->count_records('pokcertificate'));
        $pokcertificate = $this->getDataGenerator()->create_module('pokcertificate', ['course' => $course, 'page' => 1]);
        $this->assertEquals(1, $DB->count_records('pokcertificate', ['id' => $pokcertificate->id]));
        $this->assertTrue($DB->record_exists('pokcertificate', ['course' => $course->id]));
        $this->assertTrue($DB->record_exists('pokcertificate', ['id' => $pokcertificate->id]));

        $cm = get_coursemodule_from_instance('pokcertificate', $pokcertificate->id);
        $this->assertEquals($pokcertificate->id, $cm->instance);
        $this->assertEquals('pokcertificate', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = \context_module::instance($cm->id);
        $this->assertEquals($pokcertificate->cmid, $context->instanceid);

        // Stop event capturing and discard the events.
        $eventsink->close();
    }

    /**
     * Test update pokkcertificate activity
     * @return void
     */
    public function test_update_instance() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/course/modlib.php');

        $this->resetAfterTest();
        // Turn off debugging.
        set_debugging(DEBUG_NONE);

        // Start event capturing.
        $eventsink = $this->redirectEvents();

        $course = $this->getDataGenerator()->create_course();

        $this->assertEquals(0, $DB->count_records('pokcertificate'));
        $pokcertificate = $this->getDataGenerator()->create_module('pokcertificate', ['course' => $course, 'page' => 'default']);
        $this->assertEquals(1, $DB->count_records('pokcertificate', ['id' => $pokcertificate->id]));
        // Check if the records created.
        $this->assertTrue($DB->record_exists('pokcertificate', ['course' => $course->id]));
        $this->assertTrue($DB->record_exists('pokcertificate', ['id' => $pokcertificate->id]));

        // Check the course module exists.
        $cm = get_coursemodule_from_instance('pokcertificate', $pokcertificate->id);
        $this->assertEquals($pokcertificate->id, $cm->instance);
        $this->assertEquals('pokcertificate', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_pokcertificate');
        $generator->set_pokcertificate_settings();
        $poktemplate = $generator->create_pok_template($cm);

        $this->assertTrue($DB->record_exists('pokcertificate_templates', ['pokid' => $pokcertificate->id]));

        // Retrieve few information needed by update_moduleinfo.
        $pokcertificate->templateid = $poktemplate['templateid'];
        $pokcertificate->modulename = $cm->modname;
        $pokcertificate->coursemodule = $cm->id;
        if (!isset($pokcertificate->printintro)) {
            $pokcertificate->printintro = 0;
        }
        if (!isset($pokcertificate->printlastmodified)) {
            $pokcertificate->printlastmodified = 1;
        }
        $pokcertificate->completionsubmit = 0;
        // Test not-enrolled user.
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        $draftideditor = 0;
        file_prepare_draft_area($draftideditor, null, null, null, null);
        $pokcertificate->introeditor = ['text' => 'This is a module', 'format' => FORMAT_HTML, 'itemid' => $draftideditor];

        // Update the module.
        update_moduleinfo($cm, $pokcertificate, $course, null);

        // Stop event capturing and discard the events.
        $eventsink->close();
    }

    /**
     * test_delete_instance
     *
     * @return void
     */
    public function test_delete_instance() {
        global $DB;
        $this->resetAfterTest();
        // Turn off debugging.
        set_debugging(DEBUG_NONE);

        // Start event capturing.
        $eventsink = $this->redirectEvents();
        $course = $this->getDataGenerator()->create_course();
        $this->assertEquals(0, $DB->count_records('pokcertificate'));
        // Crate pokcertificate dummy data.
        $pokcertificate = $this->getDataGenerator()->create_module('pokcertificate', ['course' => $course]);
        $this->assertEquals(1, $DB->count_records('pokcertificate', ['id' => $pokcertificate->id]));
        // Check if the records created.
        $this->assertTrue($DB->record_exists('pokcertificate', ['course' => $course->id]));
        $this->assertTrue($DB->record_exists('pokcertificate', ['id' => $pokcertificate->id]));

        // Delete pokcertificate.
        $delete = pokcertificate_delete_instance($pokcertificate->id);
        $this->assertEquals(1, $delete);

        // Stop event capturing and discard the events.
        $eventsink->close();
    }

    /**
     * test_fieldmapping_save_instance
     *
     * @return void
     */
    public function test_fieldmapping_save_instance() {
        global $DB;
        $this->resetAfterTest(false);
        // Turn off debugging.
        set_debugging(DEBUG_NONE);

        // Start event capturing.
        $eventsink = $this->redirectEvents();
        $course = $this->getDataGenerator()->create_course();

        $this->assertEquals(0, $DB->count_records('pokcertificate'));
        // Create pokcertificate dummy data.
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

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_pokcertificate');
        $generator->set_pokcertificate_settings();
        $poktemplate = $generator->create_pok_template($cm);

        $this->assertTrue($DB->record_exists('pokcertificate_templates', ['pokid' => $pokcertificate->id]));

        // Retrieve few information needed by update_moduleinfo.
        $pokcertificate->templateid = $poktemplate['templateid'];
        $pokcertificate->modulename = $cm->modname;
        $pokcertificate->coursemodule = $cm->id;
        if (!isset($pokcertificate->printintro)) {
            $pokcertificate->printintro = 0;
        }
        if (!isset($pokcertificate->printlastmodified)) {
            $pokcertificate->printlastmodified = 1;
        }
        // Test not-enrolled user.
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        $draftideditor = 0;
        file_prepare_draft_area($draftideditor, null, null, null, null);
        $pokcertificate->introeditor = ['text' => 'This is a module', 'format' => FORMAT_HTML, 'itemid' => $draftideditor];
        // Update the module.
        update_moduleinfo($cm, $pokcertificate, $course, null);

        $poktemplatedata = pokcertificate_templates::get_record(['id' => $poktemplate['templateid']]);

        $templatename = base64_encode($poktemplatedata->get('templatename'));
        $apifields = helper::get_externalfield_list($templatename, $pokcertificate->id);
        if ($apifields) {
            $data = $generator->get_fieldmapping_data($cm->id, $pokcertificate->id, $templatename, $poktemplate['templateid']);
            if (pok::save_fieldmapping_data($data)) {
                $this->assertTrue($DB->record_exists('pokcertificate_fieldmapping', ['pokid' => $pokcertificate->id]));
            }
        }
        // Stop event capturing and discard the events.
        $eventsink->close();
    }
}
