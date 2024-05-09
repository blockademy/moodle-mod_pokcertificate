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

    public function verify_authentication() {
        global $CFG;
        return '<div class="verifyauth" >' . get_string('verifyauth', 'pokcertificate') . '
                    <a target="_blank" class="bt btn-primary"
                    style="padding: 7px 18px; border-radius: 4px; color:
                    white; background-color: #2578dd; margin-left: 5px;"
                    href="' . $CFG->wwwroot . '/mod/pokcertificate/pokcertificate.php' . '" >'
            . get_string('clickhere', 'mod_pokcertificate') . '
                    </a></div>';
    }

    /**
     * Render the view page.
     *
     * @param view_page $page
     * @return [output]
     */
    public function display_message() {
        $output = \html_writer::tag('span', get_string('certificatepending', 'pokcertificate'), []);
        return $output;
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
        if (get_config('mod_pokcertificate', 'pokverified')) {
            if ($recexists) {

                if (has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {

                    $certificatetemplatecontent = pok::get_certificate_templates($id);
                    if ($certificatetemplatecontent) {
                        $output = $this->render_certificate_types($id);
                        $output .= $this->render_from_template(
                            'mod_pokcertificate/certificatetemplates',
                            $certificatetemplatecontent
                        );
                    }
                }
                echo $output;
            } else {
                echo get_string('invalidcoursemodule', 'mod_pokcertificate');
            }
        } else if (has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {
            echo self::verify_authentication();
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
            $pokcertificate = pokcertificate::get_record(['id' => $cm->instance, 'course' => $cm->course]);
            if ($pokcertificate && $pokcertificate->get('templateid')) {
                $template = pokcertificate_templates::get_field(
                    'templatename',
                    ['id' => $pokcertificate->get('templateid')]
                );
                $previewdata = [
                    "name" => "John Galt",
                    "title" => $pokcertificate->get('title'),
                    "date" => time(),
                    "institution" => get_config('mod_pokcertificate', 'institution')
                ];
                $previewdata = json_encode($previewdata);
                $templatepreview = (new \mod_pokcertificate\api)->preview_certificate($template, $previewdata);
                if ($templatepreview) {
                    $temppreview = trim($templatepreview, '"');
                    $output .= \html_writer::tag('img', '', ['src' => $temppreview, 'alt' => "Snow"]);
                    $output .= \html_writer::tag('br', '');
                    $output .= \html_writer::tag(
                        'a',
                        get_string('back'),
                        ['class' => 'btn btn-primary ', 'href' => $CFG->wwwroot . '/course/view.php?id=' . $cm->course]
                    );
                }
            }
        } else {
            echo get_string('previewnotexists', 'mod_pokcertificate');
        }
        return $output;
    }

    public function certificate_pending_message() {
        global $CFG, $USER;
        require_once("{$CFG->libdir}/completionlib.php");

        $attributes = [
            'role' => 'promptdialog',
            'aria-labelledby' => 'modal-header',
            'aria-describedby' => 'modal-body',
            'aria-modal' => 'true',
        ];
        $output = $this->box_start('generalbox modal modal-dialog modal-in-page show', 'notice', $attributes);
        $output .= $this->box_start('modal-content', 'modal-content');
        $output .= $this->box_start('modal-header p-x-1', 'modal-header');
        $output .= \html_writer::tag('h6', get_string('certificatepending', 'mod_pokcertificate'));
        $output .= $this->box_end();
        $attributes = [
            'role' => 'prompt',
            'data-aria-autofocus' => 'true',
            'class' => 'certificatestatus',
        ];

        /*  $cinfo = new \completion_info($courseobject);
        $iscomplete = $cinfo->is_course_complete($USER->id); */

        $output .= $this->box_start('modal-body', 'modal-body', $attributes);

        $output .= \html_writer::tag('i', '', ['class' => ' faicon fa-solid fa-circle-check fa-xl']);
        $output .= \html_writer::tag('p', get_string('congratulations', 'mod_pokcertificate'), ['class' => 'mainheading']);
        $output .= \html_writer::tag('p', get_string('completionmsg', 'mod_pokcertificate'), ['class' => 'complheading']);

        $output .= \html_writer::tag(
            'p',
            get_string(
                'pendingcertificatemsg',
                'mod_pokcertificate',
                ['institution' => get_config('mod_pokcertificate', 'institution')]
            ),
            ['class' => 'certmessage']
        );
        $output .= \html_writer::tag(
            'input',
            '',
            ['type' => 'button', 'class' => 'btn btn-secondary', 'value' => 'Certificate Pending']
        );

        $output .= $this->box_end();

        $output .= $this->box_end();
        $output .= $this->box_end();
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

    public function get_incompletestudentprofile($filter = false) {
        global $USER;
        $systemcontext = \context_system::instance();
        $options = array('targetID' => 'viewincompletestudent', 'perPage' => 10, 'cardClass' => 'w_oneintwo', 'viewType' => 'table');

        $options['methodName'] = 'mod_pokcertificate_incompletestudentprofile_view';
        $options['templateName'] = 'mod_pokcertificate/incompletestudentprofile_view';
        $options = json_encode($options);

        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $filterdata = json_encode(array());

        $context = [
            'targetID' => 'viewincompletestudent',
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata
        ];

        if ($filter) {
            return  $context;
        } else {
            return  $this->render_from_template('mod_pokcertificate/cardPaginate', $context);
        }
    }

    public function userbulkupload() {
        global $CFG;
        $systemcontext = \context_system::instance();
        return $this->render_from_template('mod_pokcertificate/userbulkuploadbutton', array('contextid' => $categorycontext->id));
    }


    public function get_generalcertificate($filter = false) {
        global $USER;
        $systemcontext = \context_system::instance();
        $options = array('targetID' => 'view_generalcertificate', 'perPage' => 10, 'cardClass' => 'w_oneintwo', 'viewType' => 'table');

        $options['methodName'] = 'mod_pokcertificate_generalcertificate_view';
        $options['templateName'] = 'mod_pokcertificate/awardedcertificatestatus';
        $options = json_encode($options);

        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $filterdata = json_encode(array());

        $context = [
            'targetID' => 'view_generalcertificate',
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata
        ];

        if ($filter) {
            return  $context;
        } else {
            return  $this->render_from_template('mod_pokcertificate/cardPaginate', $context);
        }
    }

    public function get_courseparticipantslist($filter = false) {
        global $USER;
        $courseid = required_param('courseid', PARAM_INT);
        $systemcontext = \context_system::instance();
        $options = array('targetID' => 'view_courseparticipants', 'perPage' => 10, 'cardClass' => 'w_oneintwo', 'viewType' => 'table');

        $options['methodName'] = 'mod_pokcertificate_courseparticipants_view';
        $options['templateName'] = 'mod_pokcertificate/courseparticipants';
        $options = json_encode($options);

        $dataoptions = json_encode(array('contextid' => $systemcontext->id, 'courseid' => $courseid));
        $filterdata = json_encode(array());

        $context = [
            'targetID' => 'view_courseparticipants',
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata
        ];

        if ($filter) {
            return  $context;
        } else {
            return  $this->render_from_template('mod_pokcertificate/cardPaginate', $context);
        }
    }
}
