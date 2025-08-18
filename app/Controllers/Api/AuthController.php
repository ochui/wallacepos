<?php

namespace App\Controllers\Api;

use App\Auth;

/**
 * API Authentication Controller
 * Handles authentication-related API endpoints
 */
class AuthController
{
    private $auth;
    private $result = ["errorCode" => "OK", "error" => "OK", "data" => ""];

    public function __construct()
    {
        $this->auth = new Auth();
    }

    /**
     * Handle user authentication
     */
    public function authenticate()
    {
        if (!isset($_REQUEST['data'])) {
            $this->result['errorCode'] = "request";
            $this->result['error'] = "No authentication data provided";
            return $this->returnResult();
        }

        $data = json_decode($_REQUEST['data']);
        if ($data === false) {
            $this->result['errorCode'] = "jsondec";
            $this->result['error'] = "Error decoding the json request!";
            return $this->returnResult();
        }

        $authres = $this->auth->login($data->username, $data->password, isset($data->getsessiontokens));
        
        switch ($authres) {
            case true:
                $this->result['data'] = $this->auth->getUser();
                if ($this->result['data'] == null) {
                    $this->result['error'] = "Could not retrieve user data from php session.";
                }
                break;

            case -1:
                $this->result['errorCode'] = "authdenied";
                $this->result['error'] = "Your account has been disabled, please contact your system administrator!";
                break;

            case false:
            default:
                $this->result['errorCode'] = "authdenied";
                $this->result['error'] = "Access Denied!";
        }

        return $this->returnResult();
    }

    /**
     * Handle token session renewal
     */
    public function renewToken()
    {
        if (!isset($_REQUEST['data'])) {
            $this->result['errorCode'] = "request";
            $this->result['error'] = "No authentication data provided";
            return $this->returnResult();
        }

        $data = json_decode($_REQUEST['data']);
        if ($data === false) {
            $this->result['errorCode'] = "jsondec";
            $this->result['error'] = "Error decoding the json request!";
            return $this->returnResult();
        }

        $authres = $this->auth->renewTokenSession($data->username, $data->auth_hash);
        
        switch ($authres) {
            case true:
                $this->result['data'] = $this->auth->getUser();
                if ($this->result['data'] == null) {
                    $this->result['error'] = "Could not retrieve user data from php session.";
                }
                break;

            case -1:
                $this->result['errorCode'] = "authdenied";
                $this->result['error'] = "Your account has been disabled, please contact your system administrator!";
                break;

            case false:
            default:
                $this->result['errorCode'] = "authdenied";
                $this->result['error'] = "Access Denied!";
        }

        return $this->returnResult();
    }

    /**
     * Handle user logout
     */
    public function logout()
    {
        $this->auth->logout();
        return $this->returnResult();
    }

    /**
     * Handle hello/connectivity check
     */
    public function hello()
    {
        if ($this->auth->isLoggedIn()) {
            $this->result['data'] = $this->auth->getUser();
        } else {
            $this->result['data'] = false;
        }
        return $this->returnResult();
    }

    /**
     * Handle customer authentication
     */
    public function customerAuth()
    {
        // Enable cross origin requests for customer API
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: OPTIONS, POST, GET");

        if (!isset($_REQUEST['data'])) {
            $this->result['errorCode'] = "request";
            $this->result['error'] = "No authentication data provided";
            return $this->returnResult();
        }

        $data = json_decode($_REQUEST['data']);
        if ($data === false) {
            $this->result['errorCode'] = "jsondec";
            $this->result['error'] = "Error decoding the json request!";
            return $this->returnResult();
        }

        $authres = $this->auth->customerLogin($data->username, $data->password);
        
        if ($authres === true) {
            $this->result['data'] = $this->auth->getCustomer();
        } else if ($authres == -1) {
            $this->result['errorCode'] = "authdenied";
            $this->result['error'] = "Your account has been disabled, please contact your system administrator!";
        } else if ($authres == -2) {
            $this->result['errorCode'] = "authdenied";
            $this->result['error'] = "Your account has not yet been activated, please activate your account or reset your password.";
        } else {
            $this->result['errorCode'] = "authdenied";
            $this->result['error'] = "Access Denied!";
        }

        return $this->returnResult();
    }

    /**
     * Handle customer hello/connectivity check
     */
    public function customerHello()
    {
        // Enable cross origin requests for customer API
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: OPTIONS, POST, GET");

        $this->result['data'] = new \stdClass();
        if ($this->auth->isCustomerLoggedIn()) {
            $this->result['data']->user = $this->auth->getCustomer();
        } else {
            $this->result['data']->user = false;
        }
        
        // Provide business info for customer interface
        $conf = \App\Controllers\Admin\WposAdminSettings::getSettingsObject('general');
        $this->result['data']->bizname = $conf->bizname;
        $this->result['data']->bizlogo = $conf->bizlogo;

        return $this->returnResult();
    }

    /**
     * Authorize websocket connection
     */
    public function authorizeWebsocket()
    {
        $this->result['data'] = $this->auth->authoriseWebsocket();
        return $this->returnResult();
    }

    /**
     * Return JSON result and exit
     */
    private function returnResult()
    {
        if (($resstr = json_encode($this->result)) === false) {
            echo(json_encode(["error" => "Failed to encode the response data into json"]));
        } else {
            echo($resstr);
        }
        die();
    }
}