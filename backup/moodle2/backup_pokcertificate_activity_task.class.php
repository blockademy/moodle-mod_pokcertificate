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

require_once(__DIR__ . '/backup_pokcertificate_stepslib.php');

/**
 * Backup task for pokcertificate activity.
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Aleti Vinod Kumar <vinod.aleti@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_pokcertificate_activity_task extends backup_activity_task {
    /**
     * this task does not require custom settings
     * only userinfo setting is considered, see steps for more info
     */
    protected function define_my_settings() {
    }

    /**
     * setup steps definition to perform the backup
     * see backup_pokcertificate_stepslib.php for more info
     */
    protected function define_my_steps() {
        $this->add_step(new backup_pokcertificate_activity_structure_step('pokcertificate_structure', 'pokcertificate.xml'));
    }

    /**
     * this task does not require custom encoding
     * @param object $content the content to encode
     */
    public static function encode_content_links($content) {
        return $content;
    }
}
