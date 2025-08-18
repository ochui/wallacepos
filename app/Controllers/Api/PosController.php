<?php

namespace App\Controllers\Api;

use App\Auth;
use App\Controllers\Pos\WposPosSetup;
use App\Controllers\Pos\WposPosData;
use App\Controllers\Pos\WposPosSale;
use App\Transaction\WposTransactions;

/**
 * POS API Controller
 * Handles POS-specific API endpoints
 */
class PosController
{
    private $auth;
    private $result = ["errorCode" => "OK", "error" => "OK", "data" => ""];

    public function __construct()
    {
        $this->auth = new Auth();
    }

    /**
     * Get device configuration
     */
    public function getConfig()
    {
        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->getDeviceRecord($this->result);
        return $this->returnResult();
    }

    /**
     * Get items
     */
    public function getItems()
    {
        $jsondata = new WposPosData();
        $this->result = $jsondata->getItems($this->result);
        return $this->returnResult();
    }

    /**
     * Get sales
     */
    public function getSales()
    {
        $data = $this->getRequestData();
        $jsondata = new WposPosData($data);
        $this->result = $jsondata->getSales($this->result);
        return $this->returnResult();
    }

    /**
     * Get taxes
     */
    public function getTaxes()
    {
        $jsondata = new WposPosData();
        $this->result = $jsondata->getTaxes($this->result);
        return $this->returnResult();
    }

    /**
     * Get customers
     */
    public function getCustomers()
    {
        $jsondata = new WposPosData();
        $this->result = $jsondata->getCustomers($this->result);
        return $this->returnResult();
    }

    /**
     * Get devices
     */
    public function getDevices()
    {
        $jsondata = new WposPosData();
        $this->result = $jsondata->getPosDevices($this->result);
        return $this->returnResult();
    }

    /**
     * Get locations
     */
    public function getLocations()
    {
        $jsondata = new WposPosData();
        $this->result = $jsondata->getPosLocations($this->result);
        return $this->returnResult();
    }

    /**
     * Set order
     */
    public function setOrder()
    {
        $data = $this->getRequestData();
        $sale = new WposPosSale($data);
        $this->result = $sale->setOrder($this->result);
        return $this->returnResult();
    }

    /**
     * Remove order
     */
    public function removeOrder()
    {
        $data = $this->getRequestData();
        $sale = new WposPosSale($data);
        $this->result = $sale->removeOrder($this->result);
        return $this->returnResult();
    }

    /**
     * Add sale
     */
    public function addSale()
    {
        $data = $this->getRequestData();
        $sale = new WposPosSale($data);
        $this->result = $sale->insertTransaction($this->result);
        return $this->returnResult();
    }

    /**
     * Void sale
     */
    public function voidSale()
    {
        $data = $this->getRequestData();
        $sale = new WposPosSale($data, false);
        $this->result = $sale->insertVoid($this->result);
        return $this->returnResult();
    }

    /**
     * Search sales
     */
    public function searchSales()
    {
        $data = $this->getRequestData();
        $sale = new WposPosData();
        if (isset($data)) {
            $this->result = $sale->searchSales($data, $this->result);
        }
        return $this->returnResult();
    }

    /**
     * Update sale notes
     */
    public function updateSaleNotes()
    {
        $data = $this->getRequestData();
        $sale = new WposPosSale($data, false);
        $this->result = $sale->updateTransationNotes($this->result);
        return $this->returnResult();
    }

    /**
     * Get transaction
     */
    public function getTransaction()
    {
        $data = $this->getRequestData();
        $trans = new WposTransactions($data);
        $this->result = $trans->getTransaction($this->result);
        return $this->returnResult();
    }

    /**
     * Get and decode request data
     */
    private function getRequestData()
    {
        if (isset($_REQUEST['data']) && $_REQUEST['data'] != "") {
            // Sanitize JSON data
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $cleanData = $purifier->purify($_REQUEST['data']);

            $data = json_decode($cleanData);
            if ($data === false) {
                $this->result['errorCode'] = "request";
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