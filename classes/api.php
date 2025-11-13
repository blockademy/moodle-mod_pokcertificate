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

defined('MOODLE_INTERNAL') || die();

use moodle_exception;
use mod_pokcertificate\persistent\pokcertificate_log;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/pokcertificate/constants.php');

/**
 * Class api
 *
 * Represents an API class for handling certain functionalities.
 */
class api {
    /**
     * Authentication token for API access.
     *
     * @var string
     */
    protected $authenticationtoken = '';

    /**
     * Wallet information for API operations.
     *
     * @var mixed
     */
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
        $location = API_ROOT . '/organization/me';
        return $this->execute_command($location, '');
    }

    /**
     * get template list
     * @return string API response, in json encoded format
     */
    public function get_templates_list() {
        $location = API_ROOT . '/template';
        return $this->execute_command($location, '');
    }

    /**
     * Template definition
     * @param  string $templateid ID of the template
     * @return string API response, in json encoded format
     */
    public function get_template_definition($templateid) {
        $location = API_ROOT . '/template/' . $templateid;
        return $this->execute_command($location, '');
    }

    /**
     * Final Certificate of the user
     *
     * @param  object $data
     * @return string API response, in json encoded format
     */
    public function emit_certificate($data = '') {
        $location = API_ROOT . '/credential';
        return $this->execute_command($location, $data, 'post');
    }

    /**
     * The actual certificate of the student
     * @param  mixed $certid
     * @return string certificate url
     */
    public function get_certificate($certid = '') {
        $location = API_ROOT . '/credential/' . $certid;
        return $this->execute_command($location, '');
    }

    /**
     * Preview the certificate
     * @param  object $data
     * @return string API response, in json encoded format
     */
    public function preview_certificate($data) {
        $location = API_ROOT . '/template/preview';
        return $this->execute_command($location, $data, 'post');
    }

    /**
     * Given a list of pages
     * @return array
     */
    public function get_pages() {
        $location = API_ROOT . '/page';
        $resultjson = $this->execute_command($location, '');
        $result = json_decode($resultjson);
        $pages = ['-' => get_string('none', 'mod_pokcertificate')];

        foreach ($result->data as $page) {
            $pages[$page->id] = $page->name;
        }

        return $pages;
    }

    /**
     * Hit the API
     * @param  string $location   API URL
     * @param  string $params     URL parameters for the API
     * @param  string $method     GET or POST
     * @return string             API response, in json encoded format
     */
    private function execute_command($location, $params, $method = 'get') {

        $curl = new \curl();
        $options = [
            'CURLOPT_HTTPHEADER' => [
                'Authorization: ApiKey ' . $this->authenticationtoken,
            ],
            'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_ENCODING' => '',
            'CURLOPT_SSL_VERIFYPEER' => false,
        ];

        if ($method == 'post') {
            $options['CURLOPT_HTTPHEADER'][] = 'Content-Type: application/json';
        }
        $response = null;
        $apiresult = null;

        $apiurl = $location . '?' . $params;
        $logrec = self::saveupdate_logdata($apiurl, 0);

        $result = $curl->{$method}($location, $params, $options);

        if ($curl->get_errno()) {
            $response = get_string('connecterror', 'mod_pokcertificate') . 'URL : ' . $location;
            self::saveupdate_logdata($apiurl, $response, $curl->get_errno(), $result, $logrec->get('id'));
            throw new moodle_exception('connecterror', 'mod_pokcertificate', '', ['url' => $location]);
        }
        $httpcode = $curl->get_info()['http_code'];

        // Insert the API log here.
        if ($curl->get_info()['http_code'] == 200) {
            $response = get_string('success');
            $apiresult = $result;
        } else {
            $response = get_string('fail', 'pokcertificate');
            self::saveupdate_logdata($apiurl, $logrec->get('id'), $response, $httpcode, $result);
            $url = new \moodle_url('/my/courses.php', []);
            return notice('<p class="errorbox alert alert-danger">' . get_string(
                'curlapierror',
                'mod_pokcertificate'
            ) . '</p>', $url);
        }
        self::saveupdate_logdata($apiurl, $logrec->get('id'), $response, $httpcode, $result);

        return $apiresult;
    }

    /**
     * Saves or updates log data in the database.
     *
     * This method inserts a new log entry if `$logid` is null. If `$logid` is provided,
     * it updates the existing log entry with the given ID. The method records information
     * about the API call, including the URL, response, response code, and any additional
     * result data.
     *
     * @param string $apiurl The URL of the API for which the log entry is being created or updated.
     * @param int|null $logid The unique identifier for the log entry. Pass null to insert a new entry.
     * @param string $responsemsg The response message defined based on code received from the API call.
     * @param int $responsecode The response code received from the API call. Defaults to an empty string.
     * @param string $apiresult The response body received from the API call. Defaults to an empty string.
     *
     * @return object The logid.
     */
    public function saveupdate_logdata($apiurl, $logid, $responsemsg = null, $responsecode = 0, $apiresult = null) {

        if ($logid == 0) {
            $log = new \stdClass();
            $log->api = $apiurl;
            $log->response = $responsemsg;
            $log->responsecode = $responsecode;
            $log->responsevalue = $apiresult;
            $logdata = new pokcertificate_log(0, $log);
            $logid = $logdata->create();
        } else if ($logid != 0) {
            $logdata = new pokcertificate_log($logid);
            $logdata->set('response', $responsemsg);
            $logdata->set('responsecode', $responsecode);
            $logdata->set('responsevalue', $apiresult);
            $logid = $logdata->update();
        }
        return $logid;
    }
}
