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
 * Page module upgrade code
 *
 * This file keeps track of upgrades to
 * the resource module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade script for the mod_pokcertificate module.
 *
 * This function handles the upgrade steps for the POK certificate module.
 * It is executed whenever the version of the module is upgraded.
 *
 * @param int $oldversion The previous version of the module.
 * @return bool True on successful upgrade.
 */
function xmldb_pokcertificate_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    // Automatically generated Moodle v3.9.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2024041608.01) {
        $table = new xmldb_table('pokcertificate_issues');

        $field = new xmldb_field('pokcertificateid', XMLDB_TYPE_CHAR, '225', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('pokcertificate_log');

        $field2 = new xmldb_field('api');
        if ($dbman->field_exists($table, $field2)) {
            $field2->set_attributes(XMLDB_TYPE_TEXT, null, null, null, null);
            $dbman->change_field_type($table, $field2);
        }
        upgrade_mod_savepoint(true, 2024041608.01, 'pokcertificate');
    }

    if ($oldversion < 2024041608.03) {
        $table = new xmldb_table('pokcertificate_issues');

        $field = new xmldb_field('useremail', XMLDB_TYPE_CHAR, '225', null, null, null, null, 'userid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024041608.03, 'pokcertificate');
    }

    if ($oldversion < 2024041608.05) {
        $table = new xmldb_table('pokcertificate_issues');
        $field = new xmldb_field('certid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'pokid');
        }

        $table = new xmldb_table('pokcertificate_fieldmapping');
        $field = new xmldb_field('certid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'pokid');
        }
        upgrade_mod_savepoint(true, 2024041608.05, 'pokcertificate');
    }

    if ($oldversion < 2024041608.07) {
        $table = new xmldb_table('pokcertificate');

        $field = new xmldb_field('completionsubmit', XMLDB_TYPE_INTEGER, '1', null, null, null, 0, 'displayoptions');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024041608.07, 'pokcertificate');
    }
    return true;
}
