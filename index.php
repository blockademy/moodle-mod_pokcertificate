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
 * List of all pokcertificates in course
 *
 * @package mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

// Trigger instances list viewed event.
$event = \mod_pokcertificate\event\course_module_instance_list_viewed::create(['context' => context_course::instance($course->id)]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strpokcertificate         = get_string('modulename', 'pokcertificate');
$strpokcertificates        = get_string('modulenameplural', 'pokcertificate');
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/pokcertificate/index.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname . ': ' . $strpokcertificates);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strpokcertificates);
echo $OUTPUT->header();
echo $OUTPUT->heading($strpokcertificates);
if (!$pokcertificates = get_all_instances_in_course('pokcertificate', $course)) {
    notice(get_string('thereareno', 'moodle', $strpokcertificates), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head  = [$strsectionname, $strname, $strintro];
    $table->align = ['center', 'left', 'left'];
} else {
    $table->head  = [$strlastmodified, $strname, $strintro];
    $table->align = ['left', 'left', 'left'];
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($pokcertificates as $pokcertificate) {
    $cm = $modinfo->cms[$pokcertificate->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($pokcertificate->section !== $currentsection) {
            if ($pokcertificate->section) {
                $printsection = get_section_name($course, $pokcertificate->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $pokcertificate->section;
        }
    } else {
        $printsection = '<span class="smallinfo">' . userdate($pokcertificate->timemodified) . "</span>";
    }

    $class = $pokcertificate->visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed.

    $table->data[] = [
        $printsection,
        "<a $class href=\"view.php?id=$cm->id\">" . format_string($pokcertificate->name) . "</a>",
        format_module_intro('pokcertificate', $pokcertificate, $cm->id),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
