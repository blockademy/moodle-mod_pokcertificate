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

    protected $authenticationtoken = '';
    protected $wallet = '';

    const API_KEYS_ROOT = "https://api-keys.credentity.xyz";
    const RBAC_ROOT = "https://rbac.credentity.xyz";
    const MINTER_ROOT = "https://mint.credentity.xyz";

    public function __construct() {
        $this->authenticationtoken = get_config('mod_pokcertificate', 'authenticationtoken');
        $this->wallet = get_config('mod_pokcertificate', 'wallet');
    }

    public function get_organization() {
        $location = self::RBAC_ROOT . '/organization/' . $this->wallet;
        return $this->execute_command($location, []);
    }

    public function get_credits($wallet) {
        $location = self::MINTER_ROOT . '/credits/' . $this->wallet;
        return $this->execute_command($location, []);
    }

    public function count_certificates($wallet) {
        $location = self::MINTER_ROOT . '/certificate/count';
        $params = "wallet={$this->wallet}";
        return $this->execute_command($location, $params);
    }

    private function execute_command($location, $params) {
        $curl = new \curl();
        $options = array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: ApiKey ' . $this->authenticationtoken
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
