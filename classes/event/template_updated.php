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
 * The mod_pokcertificate course module viewed event.
 *
 * @package    mod_pokcertificate
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pokcertificate\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The mod_pokcertificate course module viewed event class.
 *
 * @package    mod_pokcertificate
 * @since      Moodle 2.6
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_updated extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'pokcertificate_templates';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    public static function get_name() {
        return get_string('templateupdated', 'mod_pokcertificate');
    }

    public function get_description() {
        return "The user with id '$this->userid' attached the template with id '{$this->objectid}' ";
    }

    public function get_url() {
        return new \moodle_url('/mod/pokcertificate/view.php', [
            'cmid' => $this->contextinstanceid
        ]);
    }

    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'objectid\' value must be set.');
        }

        if (!isset($this->contextinstanceid)) {
            throw new \coding_exception('The \'contextinstanceid\' value must be set.');
        }

        if (!isset($this->other['pokcertificateid'])) {
            throw new \coding_exception('The \'pokcertificateid\' value must be set in other.');
        }

        if (!isset($this->other['templateid'])) {
            throw new \coding_exception('The \'templateid\' value must be set in other.');
        }
    }

    public static function get_objectid_mapping() {
        return array('db' => 'pokcertificate', 'restore' => 'pokcertificate');
    }
}