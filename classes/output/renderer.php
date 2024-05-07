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

namespace mod_pokcertificate\output;

use mod_pokcertificate\pok;
use mod_pokcertificate\persistent\pokcertificate;
use mod_pokcertificate\persistent\pokcertificate_templates;
use mod_pokcertificate\persistent\pokcertificatestudent;

/**
 * Renderer for POK Certificate
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    public function display_tabs() {
        return $this->render_from_template('mod_pokcertificate/viewdata', []);
    }

    public function render_certificate_types(int $cmid) {
        return $this->render_from_template('mod_pokcertificate/certificatetypes', ['cmid' => $cmid]);
    }
    /**
     * Renders the certifcate templates view.
     *
     * @param [int] $id course module id
     * @param [bool] $formedit for redirection form hidden field to preview
     *
     * @return [template] certificate templates view mustache file
     */
    public function show_certificate_templates(int $id) {
        global $DB;
        $output = '';
        $recexists = $DB->record_exists('course_modules', ['id' => $id]);

        if ($recexists) {

            if (has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {

                $certificatetemplatecontent = pok::get_certificate_templates($id);
                if ($certificatetemplatecontent) {
                    $output = $this->render_certificate_types($id);
                    $output .= $this->render_from_template('mod_pokcertificate/certificatetemplates', $certificatetemplatecontent);
                }
            }
            echo $output;
        } else {
            echo get_string('invalidcoursemodule', 'mod_pokcertificate');
        }
    }


    /**
     * Renders the preview certifcate templates view.
     *
     * @param [int] $cmid course module id
     * @return [template] certificate templates view mustache file
     */
    public function preview_certificate_template(int $cmid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');
        $output = '';
        $recexists = $DB->record_exists('course_modules', ['id' => $cmid]);
        if ($recexists) { // has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {
            $cm = get_coursemodule_from_id('pokcertificate', $cmid, 0, false, MUST_EXIST);
            $templateid = pokcertificate::get_field('templateid', ['id' => $cm->instance, 'course' => $cm->course]);
            if ($templateid) {
                $template = pokcertificate_templates::get_field('templatename', ['id' => $templateid]);
                $previewdata = SAMPLE_DATA;
                $previewdata = json_encode($previewdata);
                $templatepreview = (new \mod_pokcertificate\api)->preview_certificate($template, $previewdata);
                if ($templatepreview) {
                    $temppreview = trim($templatepreview, '"');
                    $output .= \html_writer::tag('img', '', ['src' =>  $temppreview, 'alt' => "Snow"]);
                    $output .= \html_writer::tag('br', '');
                    $output .= \html_writer::tag('a', get_string('back'), ['class' => 'btn btn-primary ', 'href' => $CFG->wwwroot . '/course/view.php?id=' . $cm->course]);
                }
            }
        } else {
            echo get_string('previewnotexists', 'mod_pokcertificate');
        }
        return $output;
    }

    /**
     * This method returns the action bar.
     *
     * @param int $cmid The course module id.
     * @param \moodle_url $pageurl The page url.
     * @return string The HTML for the action bar.
     */

    public function action_bar(int $id,  \moodle_url $pageurl): string {
        $actionbar = new action_bar($id, $pageurl);
        $data = $actionbar->export_for_template($this);
        return $this->render_from_template('mod_pokcertificate/action_bar', $data);
    }

    public function get_incompletestudent() {

        $systemcontext = \context_system::instance();
        $data = pokcertificatestudent::incompletestudentlist();
        $view = true;
        $delete = false;
        $update = false;
        $results = [];
        $lang = current_language();
        foreach ($data as $rec) {

            $studentlist = [];
            $studentlist['id'] = $rec->id;
            $studentlist['firstname'] = $rec->firstname;
            $studentlist['lastname'] = $rec->lastname;
            $studentlist['email'] = $rec->email;
            $studentlist['studentid'] = $rec->username;
            $studentlist['language'] = 'Hindi';
            $results[] = $studentlist;
        }

        // if (is_siteadmin() || (permission::has_view_capability($systemcontext))) {
        //     $view = true;
        // }
        // if (is_siteadmin() || (permission::has_update_capability($systemcontext))) {
        $update = true;
        // }
        // if (is_siteadmin() || (permission::has_delete_capability($systemcontext))) {
        //     $delete = true;
        // }
        if ($update == true || $delete == true) {
            $action = true;
        }

        return  $this->render_from_template(
            'mod_pokcertificate/incompletestudentprofile',
            [
                'results' => array_values(array_values($results)),
                'canview' => $view,
                'action' => $action,
                'update' => $update,
                'delete' => $delete,
            ]
        );
    }

    public function get_generalcertificate() {

        $systemcontext = \context_system::instance();
        $data = pokcertificatestudent::awardedgeneralcertificateusers();
        $view = true;
        $delete = false;
        $update = false;
        $results = [];
        $lang = current_language();
        foreach ($data as $rec) {

            $studentlist = [];
            $studentlist['id'] = $rec->id;
            $studentlist['firstname'] = $rec->firstname;
            $studentlist['lastname'] = $rec->lastname;
            $studentlist['email'] = $rec->email;
            $studentlist['studentid'] = $rec->username;
            $studentlist['program'] = 'Programname';
            $results[] = $studentlist;
        }

        return  $this->render_from_template(
            'mod_pokcertificate/awardedcertificatestatus',
            [
                'results' => array_values(array_values($results)),
                'canview' => $view,
                'action' => $action,
                'update' => $update,
                'delete' => $delete,
            ]
        );
    }
}
