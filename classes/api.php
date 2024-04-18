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

use stdClass;
use moodle_exception;

require_once($CFG->libdir . '/filelib.php');

defined('MOODLE_INTERNAL') || die();

class api {

    protected $authenticationtoken;

    const API_KEYS_ROOT = "https://api-keys.credentity.xyz";
    const RBAC_ROOT = "https://rbac.credentity.xyz";
    const MINTER_ROOT = "https://mint.credentity.xyz";

    public function __construct() {
        $this->authenticationtoken = "7cb608d4-0bb6-4641-aa06-594f2fedf2a0"; //get_config('mod_pokcertificate', 'authenticationtoken');
    }

    public function get_wallet() {
        $location = self::API_KEYS_ROOT . '/me';
        return $this->execute_command($location, []);
    }

    public function get_organization($wallet) {
        $location = self::RBAC_ROOT . '/organization/' . $wallet;
        return $this->execute_command($location, []);
    }

    public function get_credits($wallet) {
        $location = self::MINTER_ROOT . '/credits/' . $wallet;
        return $this->execute_command($location, []);
    }

    public function count_certificates($wallet) {
        $location = self::MINTER_ROOT . '/certificate/count';
        $params = "wallet={$wallet}";
        return $this->execute_command($location, $params);
    }

    private function execute_command($location, $params) {
        $curl = new \curl();
        $options = array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: ApiKey 7cb608d4-0bb6-4641-aa06-594f2fedf2a0'
            ),
            'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_ENCODING' => '',
            'CURLOPT_CUSTOMREQUEST' => 'GET',
            'CURLOPT_SSL_VERIFYPEER' => false
        );
        $result = $curl->post($location, $params, $options);
        if($curl->get_errno()) {
            throw new moodle_exception('connecterror', 'mod_pokcertificate', '', array('url' => $location));
        }
        // Insert the API log here.
        /*$log = new stdClass();
        $log->api = $location . '?' . $params;
        $log->responsecode = $curl->get_info()['http_code'];
        $log->responsevalue = $result;
        $log->create();*/
        return $result;
    }
}
