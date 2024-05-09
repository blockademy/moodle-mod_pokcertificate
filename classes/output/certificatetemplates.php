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

use renderable;
use renderer_base;
use templatable;

/**
 * Class certificatetemplates
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificatetemplates implements templatable, renderable {

    private $sampledata;
    /** @var int */
    protected int $cmid;

    /**
     *  certificatetemplates constructor.
     *
     * @param int|string $id
     */
    public function __construct($id) {

        $this->cmid = $id;
    }

    /**
     * Implementation of exporter from templatable interface
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {

        $cmid = $this->cmid;
        $templateslist = (new \mod_pokcertificate\api)->get_templates_list();
        $templateslist = json_decode($templateslist);
        $templates = [];
        foreach ($templateslist as $template) {
            $data = [];
            $previewdata = json_encode($this->sampledata);
            $templatepreview = (new \mod_pokcertificate\api)->preview_certificate($template, $previewdata);
            $data['tempname'] = base64_encode($template);
            $data['name'] = $template;
            $data['cmid'] = $cmid;
            $data['certimage'] = trim($templatepreview, '"');
            $templates['certdata'][] = $data;
        }

        return $templates;
    }
}
