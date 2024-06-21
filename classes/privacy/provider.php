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

namespace mod_pokcertificate\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * The mod_pokcertificate module does not store any data.
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table(
            'pokcertificate_issues',
            [
                'pokid' => 'privacy:metadata:pokcertificate_issues:pokid',
                'userid' => 'privacy:metadata:pokcertificate_issues:userid',
                'useremail' => 'privacy:metadata:pokcertificate_issues:useremail',
                'status' => 'privacy:metadata:pokcertificate_issues:status',
                'templateid' => 'privacy:metadata:pokcertificate_issues:templateid',
                'certificateurl' => 'privacy:metadata:pokcertificate_issues:certificateurl',
                'pokcertificateid' => 'privacy:metadata:pokcertificate_issues:pokcertificateid',
                'timecreated' => 'privacy:metadata:pokcertificate_issues:timecreated',
            ],
            'privacy:metadata:pokcertificate_issues'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm
                    ON cm.id = c.instanceid
                   AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m
                    ON m.id = cm.module
                   AND m.name = :modulename
            INNER JOIN {pokcertificate} pok
                    ON pok.id = cm.instance
            INNER JOIN {pokcertificate_issues} pokissues
                    ON pokissues.pokid = pok.id
                 WHERE pokissues.userid = :userid";

        $params = [
            'modulename' => 'pokcertificate',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Fetch all users who have a custom certificate.
        $sql = "SELECT pokissues.userid
                  FROM {course_modules} cm
                  JOIN {modules} m
                    ON m.id = cm.module AND m.name = :modname
                  JOIN {pokcertificate} pok
                    ON pok.id = cm.instance
                  JOIN {pokcertificate_issues} pokissues
                    ON pokissues.pokid = pok.id
                 WHERE cm.id = :cmid";

        $params = [
            'cmid'      => $context->instanceid,
            'modname'   => 'pokcertificate',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist.
     * User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        // Filter out any contexts that are not related to modules.
        $cmids = array_reduce($contextlist->get_contexts(), function ($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);

        if (empty($cmids)) {
            return;
        }

        $user = $contextlist->get_user();

        // Get all the pokcertificate activities associated with the above course modules.
        $pokcertificateidstocmids = self::get_pokcertificate_ids_to_cmids_from_cmids($cmids);

        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($pokcertificateidstocmids), SQL_PARAMS_NAMED);
        $params = array_merge($inparams, ['userid' => $user->id]);
        $recordset = $DB->get_recordset_select(
            'pokcertificate_issues',
            "pokid $insql AND userid = :userid",
            $params,
            'timecreated, id ASC'
        );
        self::recordset_loop_and_export($recordset, 'pokid', [], function ($carry, $record) {
            $carry[] = [
                'pokcertificateid' => $record->pokcertificateid,
                'certificateurl' => $record->certificateurl,
                'status' => transform::yesno($record->status),
                'timecreated' => transform::datetime($record->timecreated),
            ];
            return $carry;
        }, function ($pokid, $data) use ($user, $pokcertificateidstocmids) {
            $context = \context_module::instance($pokcertificateidstocmids[$pokid]);
            $contextdata = helper::get_context_data($context, $user);
            $finaldata = (object) array_merge((array) $contextdata, ['issues' => $data]);
            helper::export_context_files($context, $user);
            writer::with_context($context)->export_data([], $finaldata);
        });
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if (!$cm = get_coursemodule_from_id('pokcertificate', $context->instanceid)) {
            return;
        }

        $DB->delete_records('pokcertificate_issues', ['pokid' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $DB->delete_records('pokcertificate_issues', ['pokid' => $instanceid, 'userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('pokcertificate', $context->instanceid);
        if (!$cm) {
            // Only pokcertificate module will be handled.
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $select = "pokid = :id AND userid $usersql";
        $params = ['id' => $cm->instance] + $userparams;
        $DB->delete_records_select('pokcertificate_issues', $select, $params);
    }

    /**
     * Return a list of pokcertificate IDs mapped to their course module ID.
     *
     * @param array $cmids The course module IDs.
     * @return array In the form of [$pokid => $cmid].
     */
    protected static function get_pokcertificate_ids_to_cmids_from_cmids(array $cmids) {
        global $DB;

        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $sql = "SELECT pok.id, cm.id AS cmid
                 FROM {pokcertificate} pok
                 JOIN {modules} m
                   ON m.name = :modulename
                 JOIN {course_modules} cm
                   ON cm.instance = pok.id
                  AND cm.module = m.id
                WHERE cm.id $insql";
        $params = array_merge($inparams, ['modulename' => 'pokcertificate']);

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Loop and export from a recordset.
     *
     * @param \moodle_recordset $recordset The recordset.
     * @param string $splitkey The record key to determine when to export.
     * @param mixed $initial The initial data to reduce from.
     * @param callable $reducer The function to return the dataset, receives current dataset, and the current record.
     * @param callable $export The function to export the dataset, receives the last value from $splitkey and the dataset.
     * @return void
     */
    protected static function recordset_loop_and_export(
        \moodle_recordset $recordset,
        $splitkey,
        $initial,
        callable $reducer,
        callable $export
    ) {
        $data = $initial;
        $lastid = null;

        foreach ($recordset as $record) {
            if ($lastid && $record->{$splitkey} != $lastid) {
                $export($lastid, $data);
                $data = $initial;
            }
            $data = $reducer($data, $record);
            $lastid = $record->{$splitkey};
        }
        $recordset->close();

        if (!empty($lastid)) {
            $export($lastid, $data);
        }
    }
}
