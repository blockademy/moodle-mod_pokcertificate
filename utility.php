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
 * Private pokcertificate module utility functions
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/pokcertificate/lib.php");


/**
 * File browsing support class
 */
class pokcertificate_content_file_info extends file_info_stored {
    /**
     * Get the parent file information.
     *
     * Overrides the parent method to handle special cases.
     *
     * @return file_info The parent file information.
     */
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' && $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }

    /**
     * Get the visible name of the file.
     *
     * Overrides the parent method to handle special cases.
     *
     * @return string The visible name of the file.
     */
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' && $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}

/**
 * Get editor options for the POK certificate module.
 *
 * This function retrieves options used for configuring the editor in the POK certificate module.
 *
 * @param context $context The context in which the editor is being used.
 * @return array An array of editor options.
 */
function pokcertificate_get_editor_options($context) {
    global $CFG;
    return [
        'subdirs' => 1, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => -1,
        'changeformat' => 1, 'context' => $context, 'noclean' => 1, 'trusttext' => 0,
    ];
}
