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

use mod_pokcertificate\helper;
use mod_pokcertificate\persistent\pokcertificate;
use mod_pokcertificate\persistent\pokcertificate_templates;

/**
 * Class actionbar
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class actionbar {

    /** @var int $cmid The course module id. */
    protected $cmid;
    protected $pageurl;
    /**
     * The class constructor.
     *
     * @param  int $cmid
     * @param  moodle_url $pageurl
     * @return void
     */
    public function __construct(int $cmid, \moodle_url $pageurl) {
        $this->cmid = $cmid;
        $this->pageurl = $pageurl;
    }

    /**
     * Export the data for the mustache template.
     *
     * @param \renderer_base $output renderer to be used to render the action bar elements.
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {

        $urlselect = $this->get_action_selector();

        $data = [
            'urlselect' => $urlselect->export_for_template($output),
        ];

        return $data;
    }

    /**
     * Returns the URL selector object.
     *
     * @return \url_select The URL select object.
     */
    private function get_action_selector(): \url_select {
        global $PAGE;

        $menu = [];
        if (has_capability('mod/pokcertificate:manageinstance', $PAGE->context)) {

            $previewlink = new \moodle_url('/mod/pokcertificate/preview.php', ['id' => $this->cmid]);
            $menu[$previewlink->out(false)] = get_string('previewcertificate', 'mod_pokcertificate');

            $certificateslink = new \moodle_url('/mod/pokcertificate/certificates.php', ['id' => $this->cmid]);
            $menu[$certificateslink->out(false)] = get_string('certificateslist', 'mod_pokcertificate');

            $cm = get_coursemodule_from_id('pokcertificate', $this->cmid, 0, false, MUST_EXIST);
            $templateid = pokcertificate::get_field('templateid', ['id' => $cm->instance, 'course' => $cm->course]);
            $template = pokcertificate_templates::get_field('templatename', ['id' => $templateid]);

            $fieldmappinglink = new \moodle_url(
                '/mod/pokcertificate/fieldmapping.php',
                ['id' => $this->cmid, 'temp' => base64_encode($template)]
            );
            $menu[$fieldmappinglink->out(false)] = get_string('fieldmapping', 'mod_pokcertificate');

            if ($PAGE->subpage === 'certificates') {
                return new \url_select($menu, $certificateslink, null, 'pokactionselect');
            } else if ($PAGE->subpage === 'fieldmapping') {
                return new \url_select($menu, $fieldmappinglink, null, 'pokactionselect');
            } else {
                return new \url_select($menu, $previewlink, null, 'pokactionselect');
            }
        }
    }
}
