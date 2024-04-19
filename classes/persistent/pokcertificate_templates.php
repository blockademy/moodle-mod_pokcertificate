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
use stdClass;

/**
 * Class pokcertificate_templates
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pokcertificate_templates extends persistent {

    /** Database table pokcertificate_templates. */
    public const TABLE = 'pokcertificate_templates';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {

        return [

            'templatename' => [
                'type' => PARAM_TEXT,
                'optional' => false,
            ],
            'templatedefinition' => [
                'type' => PARAM_TEXT,
                'optional' => false,
            ],
            'responsevalue' => [
                'type' => PARAM_RAW,
                'optional' => false,
            ],
            'usercreated' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
        ];
    }
}
