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
 * TODO describe module pokcertificate
 *
 * @module     mod_pokcertificate/pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import * as Str from 'core/str';
import Notification from 'core/notification';
import Ajax from 'core/ajax';
import Modal from 'core/modal';

const SELECTORS = {
    VERIFYAUTH: '[id="id_verifyauth"]',
};
var SERVICES = {
    VERIFY_AUTHENTICATION: 'mod_pokcertificate_verify_auth',
};
/**
* Displays a modal form
*
* @param {Event} e
*/

const verify = function(e){
    e.preventDefault();
    var institution = $("#id_institution").val();
    var authtoken = $("#id_authtoken").val();
    var domain = $("#id_domain").val();
    var prodtype = $("#id_prodtype").val();

    Str.get_strings([
        {key: 'confirm'},
        {key: 'tryagain',component: 'mod_pokcertificate'},
        {key: 'done',component: 'mod_pokcertificate'},
    ]).then(function(s) {

            var promises = Ajax.call([
                {
                    methodname:SERVICES.VERIFY_AUTHENTICATION,
                    args: {prodtype:prodtype,authtoken: authtoken, institution:institution, domain:domain}
                }
            ]);
            promises[0].done(function(data) {

                if(data.status == 0){
                    Modal.create({
                        title: Str.get_string('verification', 'mod_pokcertificate'),
                        body:  Str.get_string('successful', 'mod_pokcertificate'),
                        footer: '<button type="button" class="btn btn-primary" data-action="save">'+s[2]+'</button>&nbsp;'
                    }).then(function(modal) {
                        this.modal = modal;
                        modal.getRoot().find('[data-action="save"]').on('click', function() {
                            modal.destroy();
                            var resp = JSON.parse(data.response);
                            $("#id_institution").val(resp.name);
                        }.bind(this));
                        modal.show();
                    }.bind(this));
                }else  if(data.status == 1){
                    Modal.create({
                        title: Str.get_string('verification', 'mod_pokcertificate'),
                        body: Str.get_string('failed', 'mod_pokcertificate'),
                        footer: '<button type="button" class="btn btn-primary" data-action="save">'+s[1]+'</button>&nbsp;'
                    }).then(function(modal) {
                        this.modal = modal;
                        modal.getRoot().find('[data-action="save"]').on('click', function() {
                            modal.destroy();
                            //window.location.href ='index.php?delete='+elem+'&confirm=1&sesskey=' + M.cfg.sesskey;
                        }.bind(this));
                        modal.show();
                    }.bind(this));
                }

            }).fail(Notification.exception);

    }).fail(Notification.exception);


};

/**
 * Initialise masterdata aboutus actions
 */
export const init = () => {
    $(SELECTORS.VERIFYAUTH).on('click', function(e) {
        e.preventDefault();
        verify(e);
    });




};