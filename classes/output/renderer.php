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

    /**
     * Renders the certifcate templates view.
     *
     * @param [int] $id course module id
     * @param [bool] $formedit for redirection form hidden field to preview
     *
     * @return [template] certificate templates view mustache file
     */
    public function show_certificate_templates(int $id, bool $formedit = false) {
        global $DB, $USER;
        $recexists = $DB->record_exists('course_modules', ['id' => $id]);
        if ($recexists) {
            if (has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {
                $output = new certificatetemplates($id);
                $certificatetemplatecontent = $output->export_for_template($this);
                if ($certificatetemplatecontent) {
                    return $this->render_from_template('mod_pokcertificate/certificatetemplates', $certificatetemplatecontent);
                }
            } /* else {
                redirect(new \moodle_url('/mod/pokcertificate/updateprofile.php', ['cmid' => $id, 'id' => $USER->id]));
            } */
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
    public function preview_cetificate_template(int $cmid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');
        $output = '';
        $recexists = $DB->record_exists('course_modules', ['id' => $cmid]);
        if ($recexists) { // has_capability('mod/pokcertificate:manageinstance', \context_system::instance())) {
            $cm = get_coursemodule_from_id('pokcertificate', $cmid, 0, false, MUST_EXIST);
            $templateid = pokcertificate::get_field('templateid', ['course' => $cm->course]);
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
}
