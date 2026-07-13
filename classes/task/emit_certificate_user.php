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
 * Class emit_certificate_user
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pokcertificate\task;

use mod_pokcertificate\pok;

/**
 * Adhoc task that emits a POK credential for a single user out of the page rendering cycle.
 *
 * Credential emission performs an external API call plus a database write. Running it
 * synchronously from {@see mod_pokcertificate_cm_info_dynamic()} broke course rendering
 * (it surfaced as "Cannot call moodle_page::add_body_class after output has been started")
 * and caused repeated emission notifications on every page reload. This task moves the
 * emission off the render path so a failure can neither break the page nor re-notify the
 * user on each reload.
 *
 * @package     mod_pokcertificate
 * @copyright    2024 Moodle India Information Solutions Pvt Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class emit_certificate_user extends \core\task\adhoc_task {

    /**
     * Get a descriptive name for this task (used in admin task logs).
     *
     * @return string
     */
    public function get_name() {
        return get_string('emitcertificatetask', 'mod_pokcertificate');
    }

    /**
     * Execute the task: emit the credential for the queued course module / user.
     *
     * {@see pok::emit_certificate()} is idempotent: it skips emission when an issue record
     * already exists for the user, so a task that is retried (or queued more than once)
     * will not emit a second credential once the first attempt has persisted its record.
     * On a genuine transient failure the method throws, letting Moodle retry the adhoc
     * task with the usual backoff.
     */
    public function execute() {
        $data = $this->get_custom_data();
        if (empty($data) || empty($data->cmid) || empty($data->userid)) {
            return;
        }

        $user = \core_user::get_user($data->userid);
        if (empty($user)) {
            // User no longer exists; nothing to do.
            return;
        }

        pok::emit_certificate($data->cmid, $user);
    }
}
