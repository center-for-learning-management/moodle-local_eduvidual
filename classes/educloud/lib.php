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
 * @package    local_eduvidual
 * @copyright  2022 Center for Learning Management (https://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual\educloud;

defined('MOODLE_INTERNAL') || die;

class lib {
    /**
     * Get the config of this plugin and check all values.
     * @param noexception prevent throwing an exception if not configured.
     * @return object with configuration for educloud api.
     */
    public static function api_config($noexception = false) {
        $cfg = (object)[
            'apipass' => \get_config('local_eduvidual', 'educloud_apipass'),
            'apipath' => \get_config('local_eduvidual', 'educloud_apipath'),
            'apiuser' => \get_config('local_eduvidual', 'educloud_apiuser'),
            'sourceid' => \get_config('local_eduvidual', 'educloud_sourceid'),
        ];

        if (!$noexception && empty($cfg->apipass) || empty($cfg->apipath) || empty($cfg->apiuser)) {
            throw new \moodle_exception('educloud:exception:incompletesitesettings', 'local_eduvidual');
        }
        return $cfg;
    }

    /**
     * Do a particular API-Call
     * @param module extension to the apipath like e.g. udm/users/user
     * @param get array with get-parameters
     * @param post array with post-parameters
     * @param headers array with headers.
     * @param customrequest set the request method.
     * @param debug show debugging information, default false.
     */
    public static function curl($module, $get = [], $post = [], $headers = [], $customrequest = "", $debug = false) {
        $cfg = self::api_config();
        if ($module[0] == '/') {
            $module = substr($module, 1);
        }
        $url = "$cfg->apipath/$module";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");

        $proxyhost = get_config('core', 'proxyhost');
        $proxyport = get_config('core', 'proxyport');
        $proxytype = get_config('core', 'proxytype');
        $proxyuser = get_config('core', 'proxyuser');
        $proxypassword = get_config('core', 'proxypassword');

        if (!empty($proxyhost)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxyhost);
        }
        if (!empty($proxyport)) {
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxyport);
        }
        if ($proxytype == "HTTP") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        }
        if ($proxytype == "SOCKS5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        if (!empty($proxyuser)) {
            if (!empty($proxypassword)) {
                $proxyuser .= ':' . $proxypassword;
            }
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyuser);
        }

        if (!empty($get) && count($get) > 0) {
            $fields = array();
            foreach ($get as $key => $value) {
                $fields[] = urlencode($key) . '=' . urlencode($value);
            }
            $fields = implode('&', $fields);
            $get = $fields;

            curl_setopt($ch, CURLOPT_URL, $url . '?' . $fields);
        }
        if (!empty($post)) {
            if (is_array($post)) {
                $fields = array();
                foreach ($post as $key => $value) {
                    $fields[] = urlencode($key) . '=' . urlencode($value);
                }
                $post = implode('&', $fields);
            }
            //$post = http_build_query($post);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if (!empty($headers) && count($headers) > 0) {
            $strheaders = array();
            foreach ($headers as $key => $value) {
                $strheaders[] = "$key: $value";
            }
            $headers = $strheaders;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $strheaders);
        }
        if (!empty($basicauth)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $basicauth);
        }
        if (!empty($customrequest)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customrequest);
        }
        if ($debug) {
            echo "<p>CURL: $url</p>";
            echo "<details><summary>Get:</summary><pre>" . print_r($get, 1) . "</pre></details>";
            echo "<details><summary>Post:</summary><pre>" . print_r($post, 1) . "</pre></details>";
            echo "<details><summary>Headers:</summary><pre>" . print_r($headers, 1) . "</pre></details>";
            ob_start();
            $out = fopen('php://output', 'w');
            // enable debugging of curl request.
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, $out);
        }
        $output = curl_exec($ch);
        if ($debug) {
            fclose($out);
            $debug = ob_get_clean();
            echo "<details><summary>Debug:</summary><pre>$debug</pre></details>";
            $info = curl_getinfo($ch);
            $error = curl_error($ch);
            echo "<details><summary>Info:</summary><pre>" . print_r($info, 1) . "</pre></details>";
            echo "<details><summary>Error:</summary><pre>" . print_r($error, 1) . "</pre></details>";
            echo "<details open><summary>Output:</summary><pre>" . print_r($output, 1) . "</pre></details>";
        }
        curl_close($ch);
        return $output;
    }

    /**
     * Receive an API token.
     * @param forcereload force a new token.
     */
    public static function token($forcereload = false) {
        $apiauth = \local_eduvidual\locallib::cache(
            'session',
            'educloud_apiauth',
        );
        if (empty($apiauth) || $forcereload) {
            $cfg = self::api_config();
            $token = self::curl(
                'ucsschool/kelvin/token',
                [],
                [
                    'username' => $cfg->apiuser,
                    'password' => $cfg->apipass,
                ],
                ['Content-Type: application/x-www-form-urlencoded'],
            );
            $token = json_decode($token);
            if (!empty($token->access_token)) {
                $token->token_type = ucfirst($token->token_type);
                $apiauth = "$token->token_type $token->access_token";
                \local_eduvidual\locallib::cache(
                    'session',
                    'educloud_apiauth',
                    $apiauth
                );
            }
        }
        return $apiauth;
    }
}
