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

/**
 * TODO describe file preview
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

use mod_pokcertificate\persistent\pokcertificate;
use mod_pokcertificate\persistent\pokcertificate_templates;

require_login();
$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$url = new moodle_url('/mod/pokcertificate/preview.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
$cm = get_coursemodule_from_id('pokcertificate', $id, 0, false, MUST_EXIST);
$templateid = pokcertificate::get_field('templateid', ['course' => $cm->course]);
$templatename = pokcertificate_templates::get_field('templatename', ['id' => $templateid]);

$templatepreview = (new \mod_pokcertificate\api)->preview_certificate($template, $previewdata);
$templatedefinition = (new \mod_pokcertificate\api)->get_template_definition($templatename);
$templatedefinition = json_decode($templatedefinition, true);

echo $OUTPUT->footer();
