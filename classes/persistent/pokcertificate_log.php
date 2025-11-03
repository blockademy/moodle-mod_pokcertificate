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
 * Class pokcertificate_log
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pokcertificate_log extends persistent {
    /** Database table pokcertificate_log. */
    public const TABLE = 'pokcertificate_log';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {

        return [
            'api' => [
                'type' => PARAM_TEXT,
                'optional' => false,
            ],
            'response' => [
                'type' => PARAM_TEXT,
                'optional' => true,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'responsecode' => [
                'type' => PARAM_INT,
                'optional' => true,
                'default' => 0,
            ],
            'responsevalue' => [
                'type' => PARAM_RAW,
                'optional' => true,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }
}
