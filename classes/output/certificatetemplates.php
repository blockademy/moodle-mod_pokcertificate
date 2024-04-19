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

use mod_pokcertificate\api;
use mod_pokcertificate\persistent\pokcertificate_templates;

/**
 * Class certificatetemplates
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificatetemplates implements templatable, renderable {

    /**
     */
    public function __construct() {
    }

    /**
     * Implementation of exporter from templatable interface
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {

        global $USER;

        $templateslist = (new \mod_pokcertificate\api)->get_templates_list();
        $templateslist = json_decode($templateslist);
        $templates = [];
        foreach ($templateslist as $template) {
            $data = [];
            $data['name'] = $template;
            $templatedefinition = (new \mod_pokcertificate\api)->get_template_definition($template);

            //check if template record exists in table
            $templatedefdata = new \stdclass;
            $templateexists = pokcertificate_templates::get_record(['templatename' => $template]);

            if ($templateexists) {
                $templatedata = new pokcertificate_templates($templateexists->get('id'));
                $templatedata->set('templatename', $template);
                $templatedata->set('templatedefinition', $templatedefinition);
                $templatedata->set('usermodified', $USER->id);
                $templatedata->set('timemodified', time());
                $templatedata->update();
            } else {
                $templatedefdata->templatename = $template;
                $templatedefdata->templatedefinition = $templatedefinition;
                $templatedefdata->usercreated = $USER->id;

                $templatedata = new pokcertificate_templates(0, $templatedefdata);
                $templatedata->create();
            }

            $previewdata = '{"name": "John Galt", "title": "Engineer", "date": 1704423600000, "institution": "Ohio State University"}';
            $templatepreview = (new \mod_pokcertificate\api)->preview_certificate($template, $previewdata);

            $data['certimage'] = trim($templatepreview, '"');
            $templates['certdata'][] = $data;
        }
        return $templates;
    }
}
