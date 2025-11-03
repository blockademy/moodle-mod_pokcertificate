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

namespace mod_pokcertificate\persistent;

use core\persistent;

/**
 * Class pokcertificate_fieldmapping
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pokcertificate_fieldmapping extends persistent {
    /** Database table pokcertificate_fieldmapping. */
    public const TABLE = 'pokcertificate_fieldmapping';
    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {

        return [
            'pokid' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
            'templatefield' => [
                'type' => PARAM_TEXT,
                'optional' => false,
            ],
            'userfield' => [
                'type' => PARAM_TEXT,
                'optional' => false,
            ],
        ];
    }

    /**
     * Get a number of records as an array of objects where all the given conditions met for fieldmapping.
     *
     * @param array $data optional array $fieldname=>requestedvalue with AND in between
     * @param string $fields a comma separated list of fields to return (optional, by default
     *   all fields are returned). The first field will be used as key for the
     *   array so must be a unique field such as 'id'.
     * @return array An array of Objects indexed by first column.
     */
    public static function fieldmapping_records($data, $fields = '*') {
        global $DB;
        return $DB->get_records(self::TABLE, $data, $fields);
    }
}
