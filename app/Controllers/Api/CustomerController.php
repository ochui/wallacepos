<?php

namespace App\Controllers\Api;

use App\Auth;
use App\Customer\WposCustomerAccess;

/**
 * Customer API Controller
 * Handles customer-specific API endpoints
 */
class CustomerController
{
    private $auth;
    private $result = ["errorCode" => "OK", "error" => "OK", "data" => ""];

    public function __construct()
    {
        $this->auth = new Auth();
    }

    /**
     * Customer registration
     */
    public function register()
    {
        $data = $this->getRequestData();
        $wCust = new WposCustomerAccess($data);
        $this->result = $wCust->register($this->result);
        return $this->returnResult();
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail()
    {
        $data = $this->getRequestData();
        $wCust = new WposCustomerAccess($data);
        $this->result = $wCust->sendResetPasswordEmail($this->result);
        return $this->returnResult();
    }

    /**
     * Reset password
     */
    public function resetPassword()
    {
        $data = $this->getRequestData();
        $wCust = new WposCustomerAccess($data);
        $this->result = $wCust->doPasswordReset($this->result);
        return $this->returnResult();
    }

    /**
     * Get customer settings/config
     */
    public function getConfig()
    {
        $data = $this->getRequestData();
        $wCust = new WposCustomerAccess($data);
        $this->result = $wCust->getSettings($this->result);
        return $this->returnResult();
    }

    /**
     * Get current customer details (requires authentication)
     */
    public function getMyDetails()
    {
        if (!$this->auth->isCustomerLoggedIn()) {
            $this->result['errorCode'] = "auth";
            $this->result['error'] = "Access Denied!";
            return $this->returnResult();
        }

        $data = $this->getRequestData();
        $wCust = new WposCustomerAccess($data);
        $this->result = $wCust->getCurrentCustomerDetails($this->result);
        return $this->returnResult();
    }

    /**
     * Save customer details (requires authentication)
     */
    public function saveMyDetails()
    {
        if (!$this->auth->isCustomerLoggedIn()) {
            $this->result['errorCode'] = "auth";
            $this->result['error'] = "Access Denied!";
            return $this->returnResult();
        }

        $data = $this->getRequestData();
        $wCust = new WposCustomerAccess($data);
        $this->result = $wCust->saveCustomerDetails($this->result);
        return $this->returnResult();
    }

    /**
     * Get customer transactions (requires authentication)
     */
    public function getTransactions()
    {
        if (!$this->auth->isCustomerLoggedIn()) {
            $this->result['errorCode'] = "auth";
            $this->result['error'] = "Access Denied!";
            return $this->returnResult();
        }

        $data = $this->getRequestData();
        $wCust = new WposCustomerAccess($data);
        $this->result = $wCust->getCustomerTransactions($this->result);
        return $this->returnResult();
    }

    /**
     * Generate customer invoice (requires authentication)
     */
    public function generateInvoice()
    {
        if (!$this->auth->isCustomerLoggedIn()) {
            $this->result['errorCode'] = "auth";
            $this->result['error'] = "Access Denied!";
            return $this->returnResult();
        }

        $wCust = new WposCustomerAccess();
        $wCust->generateCustomerInvoice($_REQUEST['id']);
    }

    /**
     * Get and decode request data
     */
    private function getRequestData()
    {
        if (isset($_REQUEST['data']) && $_REQUEST['data'] != "") {
            $data = json_decode($_REQUEST['data']);
            if ($data === false) {
                $this->result['error'] = "Could not parse the provided json request";
                $this->returnResult();
            }
            return $data;
        }
        return new \stdClass();
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