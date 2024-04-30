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
 * Class for loading/storing oauth2 linked logins from the DB.
 *
 * @package    mod_pokcertificate
 * @copyright  2024 Aleti Vinod Kumar <vinod.aleti@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pokcertificate;

use moodle_exception;
use mod_pokcertificate\persistent\pokcertificate_log;
use mod_pokcertificate\persistent\pokcertificate_templates;
use mod_pokcertificate\persistent\pokcertificate;
use mod_pokcertificate\persistent\pokcertificate_fieldmapping;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');

defined('MOODLE_INTERNAL') || die();

class api {

    protected $authenticationtoken = '';
    protected $wallet = '';

    /**
     * Constructor for the APIs
     */
    public function __construct() {
        $this->authenticationtoken = get_config('mod_pokcertificate', 'authenticationtoken');
        $this->wallet = get_config('mod_pokcertificate', 'wallet');
    }

    /**
     * get the organization details
     * @return string API response, in json encoded format
     */
    public function get_organization() {
        $location = RBAC_ROOT . '/organization/' . $this->wallet;
        return $this->execute_command($location, '');
    }

    /**
     * Get the credits
     * @return string API response, in json encoded format
     */
    public function get_credits() {
        $location = MINTER_ROOT . '/credits/' . $this->wallet;
        return $this->execute_command($location, '');
    }

    /**
     * Certificate count
     * @return string API response, in json encoded format
     */
    public function count_certificates() {
        $location = MINTER_ROOT . '/certificate/count';
        $params = "wallet={$this->wallet}";
        return $this->execute_command($location, $params);
    }

    /**
     * get template list
     * @return string API response, in json encoded format
     */
    public function get_templates_list() {
        $location = TEMPLATE_MANAGER_ROOT . '/templates/' . $this->wallet;
        return $this->execute_command($location, '');
    }

    /**
     * Template definition
     * @param  string $templatename Name of the template
     * @return string API response, in json encoded format
     */
    public function get_template_definition($templatename) {
        $location = TEMPLATE_MANAGER_ROOT . '/templates/' . $this->wallet . '/' . $templatename;
        return $this->execute_command($location, '');
    }

    /**
     * Final Certificate of the user
     * @return string API response, in json encoded format
     */
    private function emit_certificate($data) {
        $location = MINTER_ROOT . '/mint';
        /*'{
            "email": "johngalt@pok.tech",
            "institution": "Ohio State University",
            "identification": "0123456789",
            "first_name": "John",
            "last_name": "Galt",
            "title": "Engineer",
            "template_base64": "{\'version\':1}",
            "date": 1706497200000,
            "free": true,
            "wallet": "0x8cd7c619a1685a1f6e991946af6295ca05210af7",
            "language_tag": "en"
            }
            ';*/
        return $this->execute_command($location, $data, 'post');
    }

    /**
     * The actual certificate of the student
     * @return string certificate url
     */
    public function get_certificate() {
        $response = $this->emit_certificate();
        $cert = json_decode($response);
        $location = MINTER_ROOT . '/certificate/' . $cert->id . '/details';
        return $this->execute_command($location, '');
    }

    /**
     * Preview the certificate
     * @param  string $templatename Name of the template
     * @return string API response, in json encoded format
     */
    public function preview_certificate($templatename, $data) {
        $location = TEMPLATE_MANAGER_ROOT . '/templates/' . $this->wallet . '/' . $templatename . '/render';
        return $this->execute_command($location, $data, 'post');
    }

    /**
     * Hit the API
     * @param  string $location   API URL
     * @param  string $params     URL parameters for the API
     * @param  array  $apioptions Any specific options for the API
     * @param  string $method     GET or POST
     * @return string             API response, in json encoded format
     */
    private function execute_command($location, $params, $method = 'get') {
        $curl = new \curl();
        $options = array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: ApiKey ' . $this->authenticationtoken,
            ),
            'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_ENCODING' => '',
            'CURLOPT_SSL_VERIFYPEER' => false
        );

        if ($method == 'post') {
            $options['CURLOPT_HTTPHEADER'][] = 'Content-Type: application/json';
        }
        $result = $curl->{$method}($location, $params, $options);
        if ($curl->get_errno()) {
            throw new moodle_exception('connecterror', 'mod_pokcertificate', '', array('url' => $location));
        }
        // Insert the API log here.
        $response = NULL;
        if ($curl->get_info()['http_code'] == 200) {
            $response = get_string('success');
        } else {
            $response = get_string('fail', 'pokcertificate');
        }

        $log = new \stdClass();
        $log->api = $location . '?' . $params;
        $log->response = $response;
        $log->responsecode = $curl->get_info()['http_code'];
        $log->responsevalue = $result;
        $logdata = new pokcertificate_log(0, $log);
        $logdata->create();
        return $result;
    }


    /**
     * Saves the selected template definition to the database.
     *
     * @param [string] $template - template name
     * @param [string] $cm - course module instance
     *
     * @return [array] $certid -pok certificate id ,$templateid - template id
     */
    public static function save_template_definition($template, $cm) {
        global $USER;
        $templateid = 0;
        $templatedefdata = new \stdClass();
        $templatedefinition = (new \mod_pokcertificate\api)->get_template_definition($template);
        if ($templatedefinition) {
            $templatedefdata = new \stdclass;
            $templateexists = pokcertificate_templates::get_record(['templatename' => $template]);

            if ($templateexists) {
                $templateid = $templateexists->get('id');
                $templatedata = new pokcertificate_templates($templateexists->get('id'));
                $templatedata->set('templatename', $template);
                $templatedata->set('templatedefinition', $templatedefinition);
                $templatedata->set('usermodified', $USER->id);
                $templatedata->set('timemodified', time());
                $templatedata->update();
            } else {
                $templatedefdata->templatename = $template;
                $templatedefdata->templatedefinition = $templatedefinition;
                $templatedefdata->usercreated = $USER->id;
                $templatedata = new pokcertificate_templates(0, $templatedefdata);
                $newtemplate = $templatedata->create();
                $templateid = $newtemplate->get('id');
            }
            if ($templateid != 0) {

                $pokid = pokcertificate::get_field('id', ['id' => $cm->instance]);
                $pokdata = new pokcertificate($pokid);
                $pokdata->set('templateid', $templateid);
                $pokdata->set('usermodified', $USER->id);
                $pokdata->update();
            }
        }
        return ['certid' => $pokid, 'templateid' => $templateid];
    }

    /**
     * Saves the fieldmapping fields.
     *
     * @param [object] $data - fieldmapping data
     *
     * @return [array]
     */
    public static function save_fieldmapping_data($data) {

        try {
            if ($data->certid) {
                $fields = pokcertificate_fieldmapping::get_records(['certid' => $data->certid]);

                if ($fields) {
                    foreach ($fields as $field) {
                        $mappedfield = new pokcertificate_fieldmapping($field->get('id'));
                        $mappedfield->delete();
                    }
                }
                for ($i = 0; $i < $data->option_repeats; $i++) {
                    if (isset($data->templatefield[$i]) && isset($data->userfield[$i])) {
                        $mappingfield = new \stdClass();
                        $mappingfield->timecreated = time();
                        $mappingfield->certid = $data->certid;
                        $mappingfield->templatefield = $data->templatefield[$i];
                        $mappingfield->userfield = $data->userfield[$i];
                        $fieldmapping = new pokcertificate_fieldmapping(0, $mappingfield);
                        $fieldmapping->create();
                    }
                }
                return true;
            }
            return false;
        } catch (\moodle_exception $e) {
            print_r($e);
            return false;
        }
    }
}
