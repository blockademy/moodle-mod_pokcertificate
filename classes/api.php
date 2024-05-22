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
        $params['wallet'] = $this->wallet;
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
    private function emit_certificate($data = '') {
        $location = MINTER_ROOT . '/mint';
        return $this->execute_command($location, $data, 'post');
    }

    /**
     * The actual certificate of the student
     * @return string certificate url
     */
    public function get_certificate($data = '') {
        $response = $this->emit_certificate($data);
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
        $apiresult = null;
        if ($curl->get_errno()) {
            throw new moodle_exception('connecterror', 'mod_pokcertificate', '', array('url' => $location));
        }
        // Insert the API log here.
        $response = null;
        if ($curl->get_info()['http_code'] == 200) {
            $response = get_string('success');
            $apiresult = $result;
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
        return $apiresult;
    }
}
