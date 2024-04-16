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
 * @package    auth_oauth2
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_hsi;

use context_user;
use stdClass;
use moodle_exception;
use moodle_url;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/local/hsi/lib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Static list of api methods for auth oauth2 configuration.
 *
 * @package    auth_oauth2
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    protected $customerid;
    protected $accesscode;
    protected $mode;  

    public function __construct() {
        $this->customerid = get_config('local_hsi', 'customerid');
        $this->accesscode = get_config('local_hsi', 'accesscode');
        $this->mode = get_config('local_hsi', 'mode');  
    }

    public function get_session_token() {
        $location = "https://hsiservices.osmanager4.com/Auth/RequestSessionToken.aspx";
        $params = "AccessCode={$this->accesscode}&CustomerId={$this->customerid}&Mode={$this->mode}";
        return $this->execute_command($location, $params);
    }

    public function send_scorm_completions($params) {
        $location = "https://hsiservices.osmanager4.com/NBIv2.aspx";
        $params[] = "AccessCode={$this->accesscode}";
        $params[] = "Mode={$this->mode}";
        $params = implode('&', $params);
        return $this->execute_command($location, $params);
    }

    private function execute_command($location, $params) {
        $curl = new \curl();
        $options = array(
            'returntransfer' => true,
            'timeout' => 30,
            'CURLOPT_HTTPHEADER' => array(
                "cache-control: no-cache",
              ),
            'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
        );
        $result = $curl->post($location, $params, $options);
        if($curl->get_errno()) {
            throw new moodle_exception('connecterror', 'local_hsi', '', array('url' => $location));
        }
        return $result;
    }
}
