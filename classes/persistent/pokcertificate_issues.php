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
 * Class pokcertificate_issues
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pokcertificate_issues extends persistent {

    /** Database table pokcertificate_issues. */
    public const TABLE = 'pokcertificate_issues';

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
            'userid' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
            'useremail' => [
                'type' => PARAM_RAW,
                'optional' => true,
            ],
            'status' => [
                'type' => PARAM_BOOL,
                'optional' => false,
            ],
            'templateid' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
            'certificateurl' => [
                'type' => PARAM_RAW,
                'optional' => false,
            ],
            'pokcertificateid' => [
                'type' => PARAM_RAW,
                'optional' => false,
            ],
        ];
    }
}
