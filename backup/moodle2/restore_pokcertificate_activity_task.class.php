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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/restore_pokcertificate_stepslib.php');

/**
 * Restore task for pokcertificate activity.
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Aleti Vinod Kumar <vinod.aleti@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_pokcertificate_activity_task extends restore_activity_task {
    /**
     * this task does not require custom settings
     * only default user setting is considered, see steps for more info
     */
    protected function define_my_settings() {
    }

    /**
     * setup steps definition
     * see restore_pokcertificate_stepslib.php for more info
     */
    protected function define_my_steps() {
        $this->add_step(new restore_pokcertificate_activity_structure_step('pokcertificate_structure', 'pokcertificate.xml'));
    }

    /**
     * no decoding is required
     * @return array empty array
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * no decoding is required
     * @return array empty array
     */
    public static function define_decode_rules() {
        return [];
    }

    /**
     * no actions are required to be performed after restore
     */
    public function after_restore() {
    }
}
