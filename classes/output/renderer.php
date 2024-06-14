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

use mod_pokcertificate\permission;
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

    /**
     * Display the navigation tabs for the plugin.
     *
     * This function renders the tabs using the 'viewdata' template.
     *
     * @return string HTML content for the tabs.
     */
    public function display_tabs() {
        return $this->render_from_template('mod_pokcertificate/viewdata', []);
    }

    /**
     * Render the certificate types template with the provided course module ID.
     *
     * This function renders the certificate types using the 'certificatetypes' template.
     *
     * @param int $cmid The course module ID.
     * @return string HTML content for the certificate types.
     */
    public function render_certificate_types(int $cmid) {
        return $this->render_from_template('mod_pokcertificate/certificatetypes', ['cmid' => $cmid]);
    }

    /**
     * Display the authentication verification section.
     *
     * This function returns HTML content for the authentication verification section,
     * including a link to the authentication page.
     *
     * @return string HTML content for the authentication verification section.
     */
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
     * @return string HTML to display.
     */
    public function display_message() {
        $output = \html_writer::tag('span', get_string('certificatepending', 'pokcertificate'), []);
        return $output;
    }

    /**
     * Renders the certifcate templates view.
     *
     * @param int $id course module id
     * @return string HTML certificate templates view.
     */
    public function show_certificate_templates(int $id) {
        global $DB;
        $output = '';
        $context = \context_module::instance($id);
        $recexists = $DB->record_exists('course_modules', ['id' => $id]);
        if (get_config('mod_pokcertificate', 'pokverified')) {
            if ($recexists) {

                if (permission::can_manage($context)) {

                    $certificatetemplatecontent = pok::get_certificate_templates($id);
                    if ($certificatetemplatecontent) {
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
        } else if (permission::can_manage($context)) {
            echo self::verify_authentication();
        }
    }


    /**
     * Renders the preview certifcate templates view.
     *
     * @param int $cmid course module id.
     *
     * @return string certificate templates HTML to display.
     */
    public function preview_certificate_template(int $cmid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');
        $output = '';
        $recexists = $DB->record_exists('course_modules', ['id' => $cmid]);
        if ($recexists) {
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
                    $output .= \html_writer::start_tag('div', ['class' => 'pokcertificate_img_container']);
                    $output .= \html_writer::tag('img', '', ['src' => $temppreview, 'alt' => "Snow"]);
                    $output .= \html_writer::end_tag('div');
                    $output .= \html_writer::tag('br', '');
                    $output .= \html_writer::tag(
                        'a',
                        get_string('back'),
                        ['class' => 'btn btn-primary ', 'href' => $CFG->wwwroot . '/course/view.php?id=' . $cm->course]
                    );
                }
            }
        } else {
            $output = get_string('previewnotexists', 'mod_pokcertificate');
        }
        return $output;
    }

    /**
     * Displays a modal dialog indicating that the certificate is pending.
     *
     * This function generates the HTML content for a modal dialog to inform the user
     * that their certificate is pending. It includes various elements like headers,
     * icons, and messages with appropriate attributes for accessibility.
     * @param string $msg Display message string
     * @param object $cm Course module instance
     *
     * @return string HTML content for the certificate pending message modal dialog.
     */
    public function certificate_pending_message($msg, $cm) {
        global $CFG;
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

        $output .= $this->box_start('modal-body', 'modal-body', $attributes);
        $output .= \html_writer::start_tag('div', ['class' => 'text-center']);
        $output .= \html_writer::tag('i', '', ['class' => ' faicon fa-solid fa-circle-check fa-xl']);
        $output .= \html_writer::tag('p', get_string('congratulations', 'mod_pokcertificate'), ['class' => 'mainheading']);
        $output .= \html_writer::tag('p', get_string('completionmsg', 'mod_pokcertificate'), ['class' => 'complheading']);
        $output .= \html_writer::end_tag('div');

        $output .= \html_writer::tag(
            'p',
            $msg,
            ['class' => 'certmessage']
        );
        $output .= $this->action_link(
            new \moodle_url('/course/view.php', ['id' => $cm->course]),
            get_string('certificatepending', 'pokcertificate'),
            null,
            [
                'class' => 'btn btn-secondary pendingbtn',
            ],
        );
        $output .= $this->box_end();
        $output .= $this->box_end();
        $output .= $this->box_end();
        return $output;
    }

    /**
     * Displays a modal dialog indicating the certificate has been successfully generated.
     *
     * This function generates the HTML content for a modal dialog to inform the user
     * that their certificate has been successfully generated. It includes various
     * elements like headers, icons, and messages with appropriate attributes for accessibility.
     *
     * @param object $cm Course module instance.
     * @return string HTML content for the certificate success message modal dialog.
     */
    public function certificate_mail_message($cm) {
        global $CFG, $USER;
        $user = \core_user::get_user($USER->id);
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
        $output .= $this->box_end();
        $attributes = [
            'role' => 'prompt',
            'data-aria-autofocus' => 'true',
            'class' => 'certificatestatus',
        ];

        $output .= $this->box_start('modal-body', 'modal-body', $attributes);
        $output .= \html_writer::start_tag('div', ['class' => 'text-center']);
        $output .= \html_writer::tag('i', '', ['class' => ' faicon fa-solid fa-envelope-open fa-xl']);
        $output .= \html_writer::tag('p', get_string('congratulations', 'mod_pokcertificate'), ['class' => 'success-mainheading']);
        $output .= \html_writer::end_tag('div');

        $output .= \html_writer::tag(
            'p',
            get_string('certificatesuccessmsg', 'mod_pokcertificate', $user->email),
            [
                'class' => 'success-complheading',
            ]
        );
        $output .= \html_writer::start_tag('p', ['class' => 'text-center']);
        $output .= $this->action_link(
            new \moodle_url('/course/view.php', ['id' => $cm->course]),
            get_string('done', 'pokcertificate'),
            null,
            [
                'class' => 'btn btn-primary text-center',
            ],
        );
        $output .= \html_writer::end_tag('p');
        $output .= $this->box_end();
        $output .= $this->box_end();
        $output .= $this->box_end();
        return $output;
    }

    /**
     * Displays a modal dialog indicating the certificate has been successfully generated.
     *
     * This function generates the HTML content for a modal dialog to inform the user
     * that their certificate has been successfully generated. It includes various
     * elements like headers, icons, and messages with appropriate attributes for accessibility.
     *
     * @param moodle_url $url certificate url
     * @return string HTML content for the certificate success message modal dialog.
     */
    public function display_certificate($url) {
        global $CFG;
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
        $output .= \html_writer::tag('h6', get_string('viewcertificate', 'mod_pokcertificate'));
        $output .= $this->box_end();
        $attributes = [
            'role' => 'prompt',
            'data-aria-autofocus' => 'true',
            'class' => 'certificatestatus',
        ];

        $output .= $this->box_start('modal-body', 'modal-body', $attributes);

        $output .= \html_writer::tag(
            'p',
            get_string('displaycertificatemsg', 'pokcertificate'),
            [
                'class' => 'success-complheading',
            ]
        );
        $output .= \html_writer::start_tag('p', ['class' => 'text-center']);
        $output .= $this->action_link(
            $url,
            get_string('viewcertificate', 'pokcertificate'),
            null,
            [
                'class' => 'btn btn-primary text-center',
                'target' => '_blank',
            ],
        );
        $output .= \html_writer::end_tag('p');
        $output .= $this->box_end();
        $output .= $this->box_end();
        $output .= $this->box_end();
        return $output;
    }

    /**
     * This method issues the certificate to student if issued displaying the certificate preview else
     * displaying the messages.
     *
     * @param  int $cmid
     * @param  object $user
     * @return string HTML content for the certificate
     */
    public function emit_certificate_templates($cmid, $user) {
        $output  = '';
        $user = \core_user::get_user($user->id);
        $credits = (new \mod_pokcertificate\api)->get_credits();
        $credits = json_decode($credits);
        $cm = pok::get_cm_instance($cmid);
        $pokissuerec = pokcertificate_issues::get_record(['pokid' => $cm->instance, 'userid' => $user->id]);

        if (
            !empty($pokissuerec) && $pokissuerec->get('status') &&
            !empty($pokissuerec->get('certificateurl'))
        ) {
            $output = self::display_certificate($pokissuerec->get('certificateurl'));
        } else {
            if (!empty($credits) && isset($credits->pokCredits)) {
                set_config('availablecertificate', $credits->pokCredits, 'mod_pokcertificate');
            }
            if (isset($credits->pokCredits) && $credits->pokCredits >= 0) {
                $output = self::render_emit_certificate($cm, $user, $pokissuerec);
            } else {
                $msg = get_string(
                    'mailacceptancepending',
                    'mod_pokcertificate',
                    ['institution' => get_config('mod_pokcertificate', 'institution')]
                );
                $output = self::certificate_pending_message($msg, $cm);
            }
        }
        if (empty($output)) {
            $url = new \moodle_url('/course/view.php', ['id' => $cm->course]);
            $output = notice('<p class="errorbox alert alert-warning">' .
                get_string('certificatenotconfigured', 'mod_pokcertificate') . '</p>', $url);
        }
        return $output;
    }

    /**
     * Return the output message to be displayed based on emit and issue certifcate status.
     *
     * @param  object $cm Course module instance
     * @param  object $user User object
     * @param  object $pokissuerec User pok issues record
     * @return string HTML content for the certificate
     */
    public function render_emit_certificate($cm, $user, $pokissuerec) {

        $output = '';
        if ((empty($pokissuerec)) ||
            ($pokissuerec && $pokissuerec->get('useremail') != $user->email)
        ) {
            $emitcertificate = pok::emit_certificate($cm->id, $user);
            if ($emitcertificate) {
                $output = self::certificate_mail_message($cm);
            }
        } else {
            if ($pokissuerec->get('status') && $pokissuerec->get('certificateurl')) {
                $output = self::display_certificate($pokissuerec->get("certificateurl"));
            } else if (!empty($pokissuerec->get('pokcertificateid'))) {
                $issuecertificate = pok::issue_certificate($pokissuerec);
                if (!empty($issuecertificate)) {
                    if ($issuecertificate->emitted) {
                        $msg = get_string(
                            'pendingcertificatemsg',
                            'mod_pokcertificate',
                            ['institution' => get_config('mod_pokcertificate', 'institution')]
                        );
                        if ($issuecertificate->processing || empty($issuecertificate->viewUrl)) {
                            $output = self::certificate_pending_message($msg, $cm);
                        } else {
                            $issuecertificate->status = true;
                            pok::save_issued_certificate($cm->id, $user, $issuecertificate);
                            $certificateurl = pokcertificate_issues::get_record([
                                'pokid' => $cm->instance, 'userid' => $user->id,
                                'pokcertificateid' => $pokissuerec->get('pokcertificateid'),
                            ]);
                            if (!empty($certificateurl->get('certificateurl'))) {
                                $output = self::display_certificate($certificateurl->get('certificateurl'));
                            } else {
                                $output = self::certificate_pending_message($msg, $cm);
                            }
                        }
                    } else {
                        $msg = get_string(
                            'mailacceptancepending',
                            'mod_pokcertificate',
                            ['institution' => get_config('mod_pokcertificate', 'institution')]
                        );
                        $output = self::certificate_pending_message($msg, $cm);
                    }
                }
            } else {
                $url = new \moodle_url('/course/view.php', ['id' => $cm->course]);
                $output = notice('<p class="errorbox alert alert-warning">' .
                    get_string('certificatenotissued', 'mod_pokcertificate') . '</p>', $url);
            }
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
        $actionbar = new actionbar($id, $pageurl);
        $data = $actionbar->export_for_template($this);
        return $this->render_from_template('mod_pokcertificate/actionbar', $data);
    }

    /**
     * Retrieves the list of students with incomplete profiles.
     *
     * This function fetches a paginated list of students who have incomplete profiles,
     * optionally filtered by student ID. It prepares the data for rendering using the
     * 'mod_pokcertificate/incompletestudentprofileview' template and generates the
     * pagination for the student records.
     *
     * @return array An associative array containing:
     *               - 'recordlist': The rendered HTML content for the incomplete student profiles.
     *               - 'pagination': The HTML content for the pagination controls.
     */
    public function get_incompletestudentprofile() {
        $page = optional_param('page', 0, PARAM_INT);
        $url = new \moodle_url('/mod/pokcertificate/incompletestudent.php', []);
        $studentid = optional_param('studentid', '', PARAM_RAW);
        $recordperpage = 10;
        $offset = $page * $recordperpage;
        $records = pokcertificate_incompletestudentprofilelist($studentid, $recordperpage, $offset);
        $records['showdata'] = $records['data'] ? true : false;
        $return['recordlist'] = $this->render_from_template('mod_pokcertificate/incompletestudentprofileview', $records);
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
     * for rendering using the 'mod_pokcertificate/coursecertificatestatus' template and generates the
     * pagination for the participant records.
     *
     * @return array An associative array containing:
     *               - 'recordlist': The rendered HTML content for the participant records.
     *               - 'pagination': The HTML content for the pagination controls.
     */
    public function get_coursecertificatestatuslist() {

        $courseid = required_param('courseid', PARAM_INT);
        $studentid = optional_param('studentid', '', PARAM_RAW);
        $studentname = optional_param('studentname', '', PARAM_RAW);
        $email = optional_param('email', '', PARAM_RAW);
        $senttopok = optional_param('senttopok', '', PARAM_RAW);
        $coursestatus = optional_param('coursestatus', '', PARAM_RAW);
        $page = optional_param('page', 0, PARAM_INT);
        $url = new \moodle_url(
            '/mod/pokcertificate/coursecertificatestatus.php',
            [
                'courseid' => $courseid,
                'studentid' => $studentid,
                'studentname' => $studentname,
                'email' => $email,
                'senttopok' => $senttopok,
                'coursestatus' => $coursestatus,
            ]
        );
        $recordperpage = 10;
        $offset = $page * $recordperpage;
        $records = pokcertificate_coursecertificatestatuslist(
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
        $return['recordlist'] = $this->render_from_template('mod_pokcertificate/coursecertificatestatus', $records);
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
     * @return array An associative array containing:
     *               - 'recordlist': The rendered HTML content for the certificate records.
     *               - 'pagination': The HTML content for the pagination controls.
     */
    public function get_generalcertificate() {

        $courseid = optional_param('courseid', 0, PARAM_INT);
        $studentid = optional_param('studentid', '', PARAM_RAW);
        $studentname = optional_param('studentname', '', PARAM_RAW);
        $email = optional_param('email', '', PARAM_RAW);
        $certificatestatus = optional_param('certificatestatus', '', PARAM_RAW);
        $page = optional_param('page', 0, PARAM_INT);
        $filters =  [
            'courseid' => $courseid,
            'studentid' => $studentid,
            'studentname' => $studentname,
            'email' => $email,
            'certificatestatus' => $certificatestatus,
        ];
        $url = new \moodle_url(
            '/mod/pokcertificate/generalcertificate.php',
            $filters
        );
        $recordperpage = 10;
        $offset = $page * $recordperpage;
        $records = pokcertificate_awardgeneralcertificatelist(
            $courseid,
            $studentid,
            $studentname,
            $email,
            $certificatestatus,
            $recordperpage,
            $offset
        );
        $records['showdata'] = $records['data'] ? true : false;
        $return['recordlist'] = $this->render_from_template('mod_pokcertificate/awardgeneralcertificates', $records);
        $return['pagination'] = $this->paging_bar($records['count'], $page, $recordperpage, $url);
        return $return;
    }

    /**
     * Verifies authentication check for the POK certificate module.
     *
     * This function checks if the 'pokverified' configuration is set for the POK certificate module.
     * If not, it verifies if the user is a site admin or has the capability to manage the POK certificate instance.
     * Based on the user's permissions, it sets the appropriate error message and URL to redirect.
     *
     * @return void Returns a fatal error with the appropriate message and URL if the 'pokverified' config is not set.
     */
    public function verify_authentication_check() {
        global $CFG, $COURSE;
        if (!get_config('mod_pokcertificate', 'pokverified')) {
            if (permission::can_manage(\context_system::instance())) {
                $errormsg = 'authenticationcheck';
                $url = $CFG->wwwroot . '/mod/pokcertificate/pokcertificate.php';
            } else {
                $errormsg = 'authenticationcheck_user';
                $url = $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id;
            }
            return notice('<p class="errorbox alert alert-warning">' . get_string(
                $errormsg,
                'mod_pokcertificate'
            ) . '</p>', $url);
        }
    }

    /**
     * Displays a notification message with a continue button.
     *
     * This function displays an error notification with the provided message and
     * renders a continue button that redirects to the specified URL.
     *
     * @param moodle_url|string $url The URL to redirect to when the continue button is clicked.
     * @param string $msg The error message to be displayed in the notification.
     * @return string The HTML output containing the notification and the continue button.
     */
    public function display_notif_message($url, $msg) {
        echo \core\notification::error($msg);
        $button = $this->output->single_button(
            $url,
            get_string('continue'),
            'get',
            []
        );

        $output = \html_writer::div($button);
        return $output;
    }

    /**
     * Render the user bulk upload buttons
     *
     * @return void
     */
    public function render_upload_buttons() {
        global $CFG;
        $output = \html_writer::tag(
            'a',
            get_string('back', 'mod_pokcertificate'),
            [
                'href' => $CFG->wwwroot . '/mod/pokcertificate/incompletestudent.php',
                'class' => "btn btn-secondary ml-2 float-right",
            ]
        );
        $output .= \html_writer::tag(
            'a',
            get_string('sample', 'mod_pokcertificate'),
            [
                'href' => $CFG->wwwroot . '/mod/pokcertificate/userupload_sample.php',
                'class' => "btn btn-secondary ml-2 float-right",
            ]
        );
        $output .= \html_writer::tag(
            'a',
            get_string('help_manual', 'mod_pokcertificate'),
            [
                'href' => $CFG->wwwroot . '/mod/pokcertificate/userupload_help.php',
                'class' => "btn btn-secondary ml-2 float-right",
            ]
        );
        return $output;
    }

    /**
     * get_userlist
     *
     * @param  array $userinputs
     * @return void
     */
    public function get_userslist_topreview($userinputs) {

        $records = pokcertificate_userslist($userinputs);
        $records['showdata'] = $records['data'] ? true : false;
        $records['userinputs'] = base64_encode(implode(",", $userinputs));
        $return['recordlist'] = $this->render_from_template('mod_pokcertificate/previewusers', $records);
        return $return;
    }
}
