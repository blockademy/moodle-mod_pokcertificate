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
 * Strings for component 'pokcertificate', language 'en'
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['configdisplayoptions'] = 'Select all options that should be available, existing settings are not modified. Hold CTRL key to select multiple fields.';
$string['content'] = 'Page content';
$string['contentheader'] = 'Content';
$string['createpokcertificate'] = 'Create a new pokcertificate resource';
$string['displayoptions'] = 'Available display options';
$string['displayselect'] = 'Display';
$string['displayselectexplain'] = 'Select display type.';
$string['indicator:cognitivedepth'] = 'Page cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in a Page resource.';
$string['indicator:cognitivedepthdef'] = 'Page cognitive';
$string['indicator:cognitivedepthdef_help'] = 'The participant has reached this percentage of the cognitive engagement offered by the Page resources during this analysis interval (Levels = No view, View)';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'Page social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in a Page resource.';
$string['indicator:socialbreadthdef'] = 'Page social';
$string['indicator:socialbreadthdef_help'] = 'The participant has reached this percentage of the social engagement offered by the Page resources during this analysis interval (Levels = No participation, Participant alone)';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';
$string['legacyfiles'] = 'Migration of old course file';
$string['legacyfilesactive'] = 'Active';
$string['legacyfilesdone'] = 'Finished';

$string['modulename_link'] = 'https://www.pok.tech/';
$string['optionsheader'] = 'Display options';
$string['pokcertificate-mod-pokcertificate-x'] = 'Any pokcertificate module pokcertificate';

$string['pluginadministration'] = 'Page module administration';

$string['popupheight'] = 'Pop-up height (in pixels)';
$string['popupheightexplain'] = 'Specifies default height of popup windows.';
$string['popupwidth'] = 'Pop-up width (in pixels)';
$string['popupwidthexplain'] = 'Specifies default width of popup windows.';
$string['printintro'] = 'Display pokcertificate description';
$string['printintroexplain'] = 'Display pokcertificate description above content?';
$string['printlastmodified'] = 'Display last modified date';
$string['printlastmodifiedexplain'] = 'Display last modified date below content?';
$string['privacy:metadata'] = 'The Page resource plugin does not store any personal data.';
$string['search:activity'] = 'Page';

// Deprecated since 4.0.
$string['printheading'] = 'Display pokcertificate name';
$string['printheadingexplain'] = 'Display pokcertificate name above content?';

// Newly added string for POK.

// Settings.
$string['modulename'] = 'POK Certificate';
$string['modulenameplural'] = 'POK Certificates';

$string['modulename_help'] = 'POK Certificates allows administrators and teachers to assign certificates from their organization to Moodle courses. These certificates will be automatically sent to enrolled students who meet the course requirements. Or they can be issued manually by administrators.

<b>Advantages of using POK:</b><br>
    <span class="p-5">* Certificates unalterable and easy to verify.<br></span>
    <span class="p-5">* Shareable in multiple formats and social networks.<br></span>
    <span class="p-5">* Recognition of achievements with blockchain technology, guaranteeing security and privacy.<br></span>

<b>A pokcertificate may be used:</b><br>
    <span class="p-5"><b> * Course Completion Certificate: </b>Issued automatically when the student completes all course activities.<br></span>
    <span class="p-5"><b> * Participation badge: </b>Awarded to students who attend a specified number of classes or activities.<br></span>
    <span class="p-5"><b> * Micro-credentials: </b>Recognize specific skills learned during the course, such as completing a module or passing an exam.<br></span>
';
$string['pluginname'] = 'POK Certificate';
// Settings.
$string['qa'] = 'QA';
$string['live'] = 'LIVE';
$string['response'] = 'Response';
$string['templateapiurl'] = 'TEMPLATE_MANAGER_ROOT API URL';
$string['minterapiurl'] = 'MINTER_ROOT API URL';
$string['apikeysurl'] = 'API_KEYS_ROOT API URL';
$string['rbacapiurl'] = 'RBAC_ROOT API URL';
$string['custodianapisurl'] = 'CUSTODIAN_ROOT API URL';
$string['linkpokdetails'] = 'Link POK Account Details';
$string['institution'] = 'Name of the Institution';
$string['institution_help'] = 'Institution name will be auto-populated once the authentication token is verified.';
$string['domain'] = 'Domain Name';
$string['domain_help'] = 'Domian Name';
$string['authtoken'] = 'Authentication Token';
$string['authtoken_help'] = 'Please enter valid authentication token to verify POK.';
$string['invalidauthenticationtoken'] = 'Please enter valid authentication token';
$string['verify'] = 'Verify';
$string['verification'] = 'Account Details Verification';
$string['successful'] = 'Verification Succesful';
$string['failed'] = 'Verification Failed';
$string['prodtype'] = 'Production Type';
$string['prodtype_help'] = 'Select Production Type<br>(Based on production type the api url`s will be taken).';
$string['done'] = 'Done';
$string['tryagain'] = 'Try again';
$string['verifyauth'] = 'To verify authentication token';
$string['pokverifyauth'] = 'POK authentication verification';
$string['connecterror'] = 'API Connection error';
$string['fail'] = 'Failed';

// Capabilities.
$string['pokcertificate:view'] = 'View pokcertificate content';
$string['pokcertificate:addinstance'] = 'Add a new pokcertificate resource';
$string['pokcertificate:editinstance'] = 'Edit pokcertificate resource';
$string['pokcertificate:manageinstance'] = 'Manage pokcertificate resource';
$string['pokcertificate:deleteinstance'] = 'Delete pokcertificate resource';

$string['certficatestobesent'] = 'Blockchain credentials to be sent';
$string['incompleteprofile'] = 'Incomplete Student Profile';
$string['basiccredentials'] = 'Basic credentials';
$string['unlimited'] = 'Unlimited';
$string['certificatename'] = 'Certificate Name';
$string['title'] = 'Title';
$string['userfullname'] = 'Userfullname';
$string['userprofilefields'] = 'To add custom User profile fields ';
$string['accessdenied'] = 'Access Denied';
$string['fieldmapping'] = 'Field Mapping';
$string['apifields'] = 'POK field';
$string['userfields'] = 'Moodle field';
$string['userfieldmapping'] = 'User Field Mapping ';
$string['previewnotexists'] = 'Certifcate preview doesn\'t exists';
$string['invalidcoursemodule'] = 'Invalid course module Id';
$string['previewcertificate'] = 'Preview Certificate';
$string['issuecertificate'] = 'Issue Certificate';
$string['certificateslist'] = 'Change Template';
$string['clickhere'] = 'Click here';
$string['notverified'] = 'Not Verified';
$string['verified'] = 'Verified';
$string['free'] = 'Free';
$string['paid'] = 'Paid';
$string['studentname'] = 'Student Name';
$string['surname'] = 'Surname';
$string['email'] = 'Email';
$string['studentid'] = 'Student ID';
$string['language'] = 'Language';
$string['action'] = 'Action';
$string['course'] = 'Course';
$string['profile'] = 'Profile';
$string['no_data_available'] = 'No Data Available';
$string['bulkupload'] = 'Bulk Upload';
$string['coursename'] = 'Course Name';
$string['enrolldate'] = 'Enroll Date';
$string['completedate'] = 'Complete Date';
$string['issuedddate'] = 'Issued Date';
$string['senttopok'] = 'Sent to POK';
$string['typeofcerti'] = 'Type of Certificate';
$string['certificatestatus'] = 'Certificate status';
$string['checkstatus'] = 'Check Status';
$string['coursestatus'] = 'Course Status';
$string['courseparticipants'] = 'Course Participants';
$string['helpmanualsdata'] = '
    <div class="field_type font-weight-bold" style="text-align:left;"></div>
    <br>
    <div class="helpmanual_table"><table class="generaltable" border="1">
        <table class="generaltable" border="1">
            <th>Mandatory Fields</th>
            <th>Restriction</th>
            <tr>
                <td>username</td>
                <td>Please do not change username.</td>
            </tr>
            <tr>
                <td>studentname(firstname)</td>
                <td>Please enter/modify studentname.</td>
            </tr>
            <tr>
                <td>surname(lastname)</td>
                <td>Please enter/modify surname.</td>
            </tr>
            <tr>
                <td>email</td>
                <td>Please enter/modify email.</td>
            </tr>
            <tr>
                <td>studentid(idnumber)</td>
                <td>Please enter/modify studentid.</td>
            </tr>
            <tr>
            <td>customprofilefields</td>
            <td>Custom profile fields if any (starts with profile_field_fieldname).</td>
        </tr>
        </table>
    </div>';
$string['authenticationmethods'] = 'Authentication method';
$string['missing'] = 'Missing {$a->field} at line {$a->linenumber}.';
$string['nouserrecord'] = 'No data available with username \'{$a->username}\' at line {$a->linenumber}';
$string['updatedusers_msg'] = 'Total {$a} users details updated.';
$string['errorscount_msg'] = 'Total {$a} errors occured in the bulk upload.';
$string['empfile_syncstatus'] = 'Student file sync status';
$string['back'] = 'Back';
$string['sample'] = 'Sample';
$string['help_manual'] = 'Help Manual';
$string['incompletestudent'] = 'Incomplete Student Profiles';
$string['coursecertificatestatus'] = 'Course Certificate Status';
$string['generalcertificate'] = 'Award General Certificates';
$string['contact'] = 'Contact POK';
$string['invalidemail_msg'] = 'Invalid email at line {$a->linenumber}.';
$string['invalidsapecialcharecter'] = 'Invalid {$a->field} at line {$a->linenumber}. Enter without special charecters.';
$string['invalidfieldname'] = '\'{$a}\' is not a valid field name.';
$string['duplicatefieldname'] = 'Duplicate field name \'{$a}\' detected.';
$string['cannotreadtmpfile'] = 'Uploaded file is empty. Please upload a valid file.';
$string['csvfewcolumns'] = 'Not enough columns. Upload file with columns \'username, studentname, surname, email, studentid\'.';
$string['studentexist'] = 'Student already exists with {$a->field} \'{$a->data}\' at line {$a->linenumber}.';
$string['helpmanual'] = 'Help Manual';
$string['certificatepending'] = 'Certificate Pending';
$string['congratulations'] = 'Congratulations';
$string['completionmsg'] = 'You have completed the course';
$string['pendingcertificatemsg'] = 'Your certificate is being issued by {$a->institution}.
We would like to remind you that it is important for you to check your email in order
to accept the certificate. For any questions please contact {$a->institution} . <br /><br />';
$string['mailacceptancepending'] = 'Your certificate is currently being processed. You will receive an email with
instructions on how to accept your certificate. For any questions please contact {$a->institution}.
<br /><br />';
$string['firstname'] = 'Student Name';
$string['firstname_help'] = 'Student name which will be displayed on certificate.';
$string['lastname'] = 'Surname';
$string['lastname_help'] = 'Surname which will be displayed on certificate.';
$string['email_help'] = 'Email which will be displayed on certificate.';
$string['idnumber'] = 'Student ID / IDnumber';
$string['idnumber_help'] = 'IDnumber itself is Student ID which will be displayed on certificate.';
$string['institution_help_help'] = 'This field will be displayed on the certificates to be issued
<br> (Please verify authentication token in settings inorder to get institution name.)';
$string['title_help'] = 'This field will be displayed on the certificates to be issued';
$string['participants'] = 'Course Particpants';
$string['authenticationcheck_user'] = "Authentication was not verfied! Please contact Site Administartor";
$string['authenticationcheck'] = "Authentication was not verfied! click continue to verify authentication";
$string['activity'] = "Activity";
$string['invalidemail'] = "Please enter a valid email";
$string['invalidspechar'] = "Please do not use special characters";
$string['usethistemplate'] = "Use this template";
$string['inprogress'] = "Inprogress";
$string['notissued'] = 'Not Issued';
$string['invalidtemplatedef'] = 'Invalid Template Definition';
$string['invalidtemplate'] = 'Invalid template name please select valid template';
$string['certificatesuccess'] = 'Account Details Verification';
$string['certificatesuccessmsg'] = 'You have received an email to accept your certificate <br/><br/> <b><center>{$a}</center></b>';
$string['certificateissuemsg'] = 'You have received an email to accept your certificate ';
$string['pokcertificate:managecoursecertificate'] = 'Manage course certificate status';
$string['pokcertificate:uploadincompleteprofile'] = 'Bulk update incomplete profile';
$string['pokcertificate:awardcertificate'] = 'Award Certificate';
$string['pokcertificate:viewstatusreport'] = 'View status report';
$string['pokcertificate:updateincompleteprofile'] = 'Self update incomplete profile';
$string['templateupdated'] = 'Template updated for POK certificate';
$string['displaycertificatemsg'] = 'Please click below link to view Certificate. <br/>';
$string['viewcertificate'] = 'View Certificate';
$string['issuecertificatestask'] = 'Issue POK Certificate to user task';
$string['certificatenotconfigured'] = 'Certificate is not configured yet! Please contact Site Administartor';
$string['description'] = 'Description';
$string['certificatenotissued'] = 'Certificate not issued! Please contact Site Administartor';
$string['removeissues'] = 'Remove issued certificates';
$string['awardcertificate'] = 'Award Certificate';
$string['award'] = 'Award';
$string['certificatetitle'] = 'Certificate Title';
$string['issueddate'] = 'Issued Date';
$string['certificatstatus'] = 'Certificate Status';
$string['selectcourse'] = 'Select Course';
$string['norecordavailable'] = 'No Record Available';
$string['incomplete'] = 'Incomplete Profile';
$string['complete'] = 'Complete';
$string['profilestatus'] = 'Profile status';
$string['certificatesent'] = 'Certificate sent success';
$string['mailpending'] = 'Mail acceptance pending';
$string['completionmustrecievecert'] = 'Must receive certificate';
$string['generalcertstatus'] = 'General Certitifacte status';
$string['completiondetail:submit'] = 'Must receive certificate';
$string['fieldmappingerror'] = 'Please select user field to be mapped';
$string['validuserprofiles'] = 'Note: Certificates are issued to users whose profile status is Complete.';
$string['validuserprofilesforteachers'] = 'Note: Teachers can view/issue certificates to users whose profile status is Complete. ';
$string['cannotedit'] = 'Teacher`s cannot edit';
$string['profilefields'] = 'User Profile Fields';
// Privacy provider strings.
$string['privacy:metadata:pokcertificate_issues'] = 'The list of issued POKcertificates';
$string['privacy:metadata:pokcertificate_issues:pokid'] = 'The ID that belongs to the POKcertificate activity';
$string['privacy:metadata:pokcertificate_issues:status'] = 'The status of POKcertificate whether issued or not';
$string['privacy:metadata:pokcertificate_issues:useremail'] = 'The user email to which the confirmation mail is sent to issue POKcertificate';
$string['privacy:metadata:pokcertificate_issues:timecreated'] = 'The time the POKcertificate was issued';
$string['privacy:metadata:pokcertificate_issues:templateid'] = 'The ID of the template to which the POKcertificate activity is mapped';
$string['privacy:metadata:pokcertificate_issues:certificateurl'] = 'The POKcertificate URL that belongs to the user';
$string['privacy:metadata:pokcertificate_issues:pokcertificateid'] = 'The POKcertificate code that belongs to the user';
$string['privacy:metadata:pokcertificate_issues:userid'] = 'The ID of the user who was issued the POKcertificate';
$string['invalidinputs'] = 'Invalid inputs received';

$string['verfiytext'] = 'Verify your Authentication Token';
$string['verfiydesc'] = 'Enter the token generated in POK to link your moodle activities and issue credentials automatically';
$string['credential_link'] = 'Digital credentials';
$string['credential_title'] = 'Issue Verifiable Credentials, Micro-Credentials, Certificates, Diplomas, Badges and Proof of Assistance as NFTs in the Blockchain';
$string['credential_desc'] = 'With POK,issue credentials to students enrolled in your courses,ensuring authenticity and transparency on the block chain.';
$string['contact_text'] = "If you don't have your authentication token";
$string['contact_link'] = 'Contact us at <a href="#">contacto@pok.tech</a> to get it';
$string['invalid'] = 'Invalid';
