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

defined('MOODLE_INTERNAL') || die();

use mod_pokcertificate\pok;
use mod_pokcertificate\persistent\pokcertificate;
use mod_pokcertificate\persistent\pokcertificate_issues;
use mod_pokcertificate\persistent\pokcertificate_templates;

require_once($CFG->dirroot . '/mod/pokcertificate/lib.php');
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
        $context = \context_module::instance($id);
        $recexists = $DB->record_exists('course_modules', ['id' => $id]);
        if (get_config('mod_pokcertificate', 'pokverified')) {
            if ($recexists) {

                if (has_capability('mod/pokcertificate:manageinstance', $context)) {

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
        } else if (has_capability('mod/pokcertificate:manageinstance', $context)) {
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
                    "institution" => get_config('mod_pokcertificate', 'institution'),
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
        global $CFG, $USER, $COURSE;
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
        /*  $output .= \html_writer::tag(
            'input',
            '',
            ['type' => 'button', 'class' => 'close', 'data-dismiss' => 'modal']
        ); */
        $output .= $this->box_end();
        $attributes = [
            'role' => 'prompt',
            'data-aria-autofocus' => 'true',
            'class' => 'certificatestatus',
        ];

        /*  $cinfo = new \completion_info($COURSE);
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
    public function certificate_success_message($certificateurl) {
        global $CFG, $USER, $COURSE;
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
        $output .= \html_writer::tag('h6', get_string('certificatesuccess', 'mod_pokcertificate'));
        /*  $output .= \html_writer::tag(
            'input',
            '',
            ['type' => 'button', 'class' => 'close', 'data-dismiss' => 'modal']
        ); */
        $output .= $this->box_end();
        $attributes = [
            'role' => 'prompt',
            'data-aria-autofocus' => 'true',
            'class' => 'certificatestatus',
        ];

        /*  $cinfo = new \completion_info($COURSE);
        $iscomplete = $cinfo->is_course_complete($USER->id); */

        $output .= $this->box_start('modal-body', 'modal-body', $attributes);

        $output .= \html_writer::tag('i', '', ['class' => ' faicon fa-solid fa-envelope-open fa-xl']);
        $output .= \html_writer::tag('p', get_string('congratulations', 'mod_pokcertificate'), ['class' => 'success-mainheading']);
        $output .= \html_writer::tag('p', get_string('certificatesuccessmsg', 'mod_pokcertificate',$USER->email), ['class' => 'success-complheading']);
        $output .= \html_writer::start_tag('p', ['class' => 'text-center']);
        $output .= $this->action_link(
            $certificateurl,
            'View Certificate',
            null,
            array('class' => 'btn btn-secondary text-center'),
        );
        $output .= \html_writer::end_tag('p');
        $output .= $this->box_end();
        $output .= $this->box_end();
        $output .= $this->box_end();
        return $output;
    }

    /**
     * emit_certificate_templates
     *
     * @param  mixed $cmid
     * @param  mixed $user
     * @return string
     */
    public function emit_certificate_templates($cmid, $user) {
        $output  = '';
        $availablecredits = get_config('mod_pokcertificate', 'availablecertificates');
        $credits = (new \mod_pokcertificate\api)->get_credits();
        $credits = json_decode($credits);
        if (!empty($credits)) {
            if (($availablecredits != $credits->pokCredits || $credits->pokCredits > $availablecredits)) {
                set_config('availablecertificate', $credits->pokCredits, 'mod_pokcertificate');
            }
        }
        $availablecredits = get_config('mod_pokcertificate', 'availablecertificates');
        if ($availablecredits >= 0) {
            $cm = pok::get_cm_instance($cmid);
            $certificateissue = pokcertificate_issues::get_record(['certid' => $cm->instance, 'userid' => $user->id]);
            $emitcertificate = pok::emit_certificate($cmid, $user);
            if (!empty($emitcertificate) && empty($certificateissue)) {
                if ($emitcertificate->processing) {
                    $output = self::certificate_pending_message();
                } else {
                    pok::save_issued_certificate($cmid, $user, $emitcertificate);
                    // redirect($emitcertificate->viewUrl);
                    $output = self::certificate_success_message($emitcertificate->viewUrl);
                }
            } else if (!empty($certificateissue)) {
                redirect($certificateissue->get('certificateurl'));
            }
        } else if ($availablecredits == 0) {
            $output = self::certificate_pending_message();
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

    /**
     * Retrieves the list of students with incomplete profiles.
     *
     * This function fetches a paginated list of students who have incomplete profiles,
     * optionally filtered by student ID. It prepares the data for rendering using the
     * 'mod_pokcertificate/incompletestudentprofile_view' template and generates the
     * pagination for the student records.
     *
     * @return array An associative array containing:
     *               - 'recordlist': The rendered HTML content for the incomplete student profiles.
     *               - 'pagination': The HTML content for the pagination controls.
     */
    public function get_incompletestudentprofile() {
        global $USER;
        $systemcontext = \context_system::instance();
        $page = optional_param('page', 0, PARAM_INT);
        $url = new \moodle_url('/mod/pokcertificate/incompletestudent.php', []);
        $studentid = optional_param('studentid', '', PARAM_RAW);
        $recordperpage = 10;
        $offset = $page * $recordperpage;
        $records = pokcertificate_incompletestudentprofilelist($studentid, $recordperpage, $offset);
        $records['showdata'] = $records['data'] ? true : false;
        $return['recordlist'] = $this->render_from_template('mod_pokcertificate/incompletestudentprofile_view', $records);
        $return['pagination'] = $this->paging_bar($records['count'], $page, $recordperpage, $url);
        return $return;
    }

    /**
     * Renders the user bulk upload button.
     *
     * This function prepares and returns the HTML content for a button that allows
     * bulk uploading of users. It uses the 'mod_pokcertificate/userbulkuploadbutton'
     * template for rendering.
     *
     * @return string The rendered HTML content for the bulk upload button.
     */
    public function userbulkupload() {
        global $CFG;
        $categorycontext = \context_system::instance();
        return $this->render_from_template(
            'mod_pokcertificate/userbulkuploadbutton',
            ['contextid' => $categorycontext->id]
        );
    }

    /**
     * Retrieves the list of course participants with optional filters.
     *
     * This function fetches a paginated list of participants in a course, optionally filtered by
     * student ID, whether the record was sent to POK, and the course status. It prepares the data
     * for rendering using the 'mod_pokcertificate/courseparticipants' template and generates the
     * pagination for the participant records.
     *
     * @return array An associative array containing:
     *               - 'recordlist': The rendered HTML content for the participant records.
     *               - 'pagination': The HTML content for the pagination controls.
     */
    public function get_courseparticipantslist() {
        global $USER, $CFG;
        $courseid = required_param('courseid', PARAM_INT);
        $studentid = optional_param('studentid', '', PARAM_RAW);
        $studentname = optional_param('studentname', '', PARAM_RAW);
        $email = optional_param('email', '', PARAM_RAW);
        $senttopok = optional_param('senttopok', '', PARAM_RAW);
        $coursestatus = optional_param('coursestatus', '', PARAM_RAW);
        $page = optional_param('page', 0, PARAM_INT);
        $url = new \moodle_url(
            '/mod/pokcertificate/courseparticipants.php',
            [
                'courseid' => $courseid,
                'studentid' => $studentid,
                'senttopok' => $senttopok,
                'coursestatus' => $coursestatus,
            ]
        );
        $recordperpage = 10;
        $offset = $page * $recordperpage;
        $records = pokcertificate_courseparticipantslist(
            $courseid,
            $studentid,
            $studentname,
            $email,
            $senttopok,
            $coursestatus,
            $recordperpage,
            $offset
        );
        $records['showdata'] = $records['data'] ? true : false;
        $return['recordlist'] = $this->render_from_template('mod_pokcertificate/courseparticipants', $records);
        $return['pagination'] = $this->paging_bar($records['count'], $page, $recordperpage, $url);
        return $return;
    }

    /**
     * Retrieves the list of general certificates with optional filtering.
     *
     * This function fetches a paginated list of general certificates awarded to students,
     * optionally filtered by student ID. It prepares the data for rendering using the
     * 'mod_pokcertificate/awardedcertificatestatus' template and generates the pagination
     * for the certificate records.
     *
     * @param bool $filter Whether to apply a filter on the certificate records. Default is false.
     * @return array An associative array containing:
     *               - 'recordlist': The rendered HTML content for the certificate records.
     *               - 'pagination': The HTML content for the pagination controls.
     */
    public function get_generalcertificate($filter = false) {
        global $USER;
        $systemcontext = \context_system::instance();
        $page = optional_param('page', 0, PARAM_INT);
        $url = new \moodle_url('/mod/pokcertificate/generalcertificate.php', []);
        $studentid = optional_param('studentid', '', PARAM_RAW);
        $recordperpage = 10;
        $offset = $page * $recordperpage;
        $records = pokcertificate_awardgeneralcertificatelist($studentid, $recordperpage, $offset);
        $records['showdata'] = $records['data'] ? true : false;
        $return['recordlist'] = $this->render_from_template('mod_pokcertificate/awardedcertificatestatus', $records);
        $return['pagination'] = $this->paging_bar($records['count'], $page, $recordperpage, $url);
        return $return;
    }

    public function verify_authentication_check() {
        global $CFG, $COURSE;
        if (!get_config('mod_pokcertificate', 'pokverified')) {
            if (is_siteadmin() || has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {
                $errormsg = 'authenticationcheck';
                $url = $CFG->wwwroot . '/mod/pokcertificate/pokcertificate.php';
            } else {
                $errormsg = 'authenticationcheck_user';
                $url = $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id;
            }
            return pokcertificate_fatal_error($errormsg,
           'mod_pokcertificate',
           $url);
        }
    }

    public function display_notif_message($url, $msg) {
        global $OUTPUT;
        echo \core\notification::error($msg);
        $button = $OUTPUT->single_button(
            $url,
            get_string('continue'),
            'get',
            []
        );

        $output = \html_writer::div($button);
        return $output;
    }
}
