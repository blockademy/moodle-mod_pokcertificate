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
import Templates from 'core/templates';
import LoadingIcon from 'core/loadingicon';

const SELECTORS = {
    VERIFYAUTH: '[id="id_verifyauth"]',
    CERTIFICATETEMPLATETYPE: '[data-action="templatetype"]',
    SAVECERTIFICATE: '[data-action="savecert"]',
    ADDROW: '[class => addmapping]'
};
var SERVICES = {
    VERIFY_AUTHENTICATION: 'mod_pokcertificate_verify_auth',
    INCOMPLETE_PROFILES: 'mod_pokcertificate_incomplete_profiles',
    SHOW_CERTIFICATE_TEMPLATES: 'mod_pokcertificate_show_certificate_templates'

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
    var prodtype = $("#id_prodtype").val();
    var loadElement = $('.loadElement');
    var loadingIcon = LoadingIcon.addIconToContainerWithPromise(loadElement);

    $("#verify_response").css("display", "none");
    Str.get_strings([
        {key: 'confirm'},
        {key: 'notverified', component: 'mod_pokcertificate'},
        {key: 'verified', component: 'mod_pokcertificate'},
    ]).then(function(s) {
            $('#loading-image').show();
            var promises = Ajax.call([
                {
                    methodname:SERVICES.VERIFY_AUTHENTICATION,
                    args: {prodtype:prodtype,authtoken: authtoken, institution:institution}
                }
            ]);
            promises[0].done(function(data) {
               loadingIcon.resolve();

                if(data.status == 1){

                    $("#verifyresponse").html('<i class="notverified fa-solid fa-circle-xmark"></i>' +
                                                '<span">' +s[1]+'</span>');
                }else{

                    $("#verifyresponse").html('<i class="verified fa-solid fa-circle-check"></i>' +
                                                '<span>' +s[2]+ '</span>');
                    var resp = JSON.parse(data.response);
                    $("#id_institution").val(resp.name);
                    window.location.reload();
                }

            }).fail(Notification.exception);

    }).fail(Notification.exception);

};

/**
* Load certificate templates
*
* @param {Event} e
*/
export const loadtemplates = function(e){
    e.preventDefault();
    var type = $(e.currentTarget).attr('data-value');//$("input[type='radio'][name='optradio']:checked").val();
    var cmid = $(e.currentTarget).attr('data-cmid');
    var promise = Ajax.call([{
        methodname:SERVICES.SHOW_CERTIFICATE_TEMPLATES,
        args: {type:type,cmid:cmid}
    }]);
    promise[0].done(function(data) {
        var resp = JSON.parse(data);
        var content = Templates.render('mod_pokcertificate/certificatetemplates', resp);
        content.then(function (html) {
            $('.certtemplatedata').html(html);
        });
    }).fail(function() {
    });
};


/**
 * Initialise masterdata aboutus actions
 */
export const init = () => {

    $(SELECTORS.VERIFYAUTH).on('click', function(e) {
        e.preventDefault();
        verify(e);
    });

   /*  $(SELECTORS.CERTIFICATETEMPLATETYPE).on('change', function(e) {
        e.preventDefault();
        loadtemplates(e);
    }); */
};