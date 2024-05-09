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
 * Definition of log events
 *
 * @package    mod_pokcertificate
 * @category   log
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = [
    ['module' => 'pokcertificate', 'action' => 'view', 'mtable' => 'pokcertificate', 'field' => 'name'],
    ['module' => 'pokcertificate', 'action' => 'view all', 'mtable' => 'pokcertificate', 'field' => 'name'],
    ['module' => 'pokcertificate', 'action' => 'update', 'mtable' => 'pokcertificate', 'field' => 'name'],
    ['module' => 'pokcertificate', 'action' => 'add', 'mtable' => 'pokcertificate', 'field' => 'name'],
];
