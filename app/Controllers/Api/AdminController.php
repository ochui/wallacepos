<?php

namespace App\Controllers\Api;

use App\Auth;
use App\Controllers\Admin\WposAdminItems;
use App\Controllers\Admin\WposAdminCustomers;
use App\Controllers\Admin\WposAdminStock;
use App\Controllers\Admin\WposAdminStats;
use App\Controllers\Admin\WposAdminGraph;
use App\Controllers\Admin\WposAdminSettings;
use App\Controllers\Admin\WposAdminUtilities;
use App\Controllers\Invoice\WposInvoices;
use App\Controllers\Pos\WposPosSetup;
use App\Controllers\Pos\WposPosData;
use App\Transaction\WposTransactions;
use App\Invoice\WposTemplates;
use App\Communication\WposSocketControl;
use App\Communication\WposSocketIO;
use App\Integration\GoogleIntegration;
use App\Integration\XeroIntegration;
use App\Utility\Logger;

/**
 * Admin API Controller
 * Handles admin-specific API endpoints with permission checks
 */
class AdminController
{
    private $auth;
    private $result = ["errorCode" => "OK", "error" => "OK", "data" => ""];

    public function __construct()
    {
        $this->auth = new Auth();
    }

    /**
     * Check if user is logged in and handle CSRF
     */
    private function checkAuthentication()
    {
        // Check login status
        if (!$this->auth->isLoggedIn()) {
            $this->result['errorCode'] = "auth";
            $this->result['error'] = "Access Denied!";
            $this->returnResult();
        }

        // Check CSRF token
        if ($_SERVER['HTTP_ANTI_CSRF_TOKEN'] != $this->auth->getCsrfToken()) {
            $this->result['errorCode'] = "auth";
            $this->result['error'] = "CSRF token invalid. Please try reloading the page.";
            $this->returnResult();
        }
    }

    /**
     * Check if user has permission for the action
     */
    private function checkPermission($action)
    {
        if ($this->auth->isUserAllowed($action) === false) {
            $this->result['errorCode'] = "priv";
            $this->result['error'] = "You do not have permission to perform this action.";
            $this->returnResult();
        }
    }

    /**
     * Check if user is admin
     */
    private function checkAdminPermission()
    {
        if (!$this->auth->isAdmin()) {
            $this->result['errorCode'] = "priv";
            $this->result['error'] = "You do not have permission to perform this action.";
            $this->returnResult();
        }
    }

    /**
     * Setup device
     */
    public function setupDevice()
    {
        $this->checkAuthentication();
        $this->checkPermission('devices/setup');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->setupDevice($this->result);
        return $this->returnResult();
    }

    /**
     * Get admin config
     */
    public function getAdminConfig()
    {
        $this->checkAuthentication();
        $this->checkPermission('adminconfig/get');

        $setupMdl = new WposPosSetup();
        $this->result = $setupMdl->getAdminConfig($this->result);
        return $this->returnResult();
    }

    // Items management
    public function addItem()
    {
        $this->checkAuthentication();
        $this->checkPermission('items/add');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->addStoredItem($this->result);
        return $this->returnResult();
    }

    public function editItem()
    {
        $this->checkAuthentication();
        $this->checkPermission('items/edit');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->updateStoredItem($this->result);
        return $this->returnResult();
    }

    public function deleteItem()
    {
        $this->checkAuthentication();
        $this->checkPermission('items/delete');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->deleteStoredItem($this->result);
        return $this->returnResult();
    }

    public function setItemImport()
    {
        $this->checkAuthentication();
        $this->checkPermission('items/import/set');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->importItemsSet($this->result);
        return $this->returnResult();
    }

    public function startItemImport()
    {
        $this->checkAuthentication();
        $this->checkPermission('items/import/start');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->importItemsStart($this->result);
        return $this->returnResult();
    }

    // Suppliers management
    public function getSuppliers()
    {
        $this->checkAuthentication();
        $this->checkPermission('suppliers/get');

        $jsondata = new WposPosData();
        $this->result = $jsondata->getSuppliers($this->result);
        return $this->returnResult();
    }

    public function addSupplier()
    {
        $this->checkAuthentication();
        $this->checkPermission('suppliers/add');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->addSupplier($this->result);
        return $this->returnResult();
    }

    public function editSupplier()
    {
        $this->checkAuthentication();
        $this->checkPermission('suppliers/edit');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->updateSupplier($this->result);
        return $this->returnResult();
    }

    public function deleteSupplier()
    {
        $this->checkAuthentication();
        $this->checkPermission('suppliers/delete');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->deleteSupplier($this->result);
        return $this->returnResult();
    }

    // Categories management
    public function getCategories()
    {
        $this->checkAuthentication();
        $this->checkPermission('categories/get');

        $jsondata = new WposPosData();
        $this->result = $jsondata->getCategories($this->result);
        return $this->returnResult();
    }

    public function addCategory()
    {
        $this->checkAuthentication();
        $this->checkPermission('categories/add');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->addCategory($this->result);
        return $this->returnResult();
    }

    public function editCategory()
    {
        $this->checkAuthentication();
        $this->checkPermission('categories/edit');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->updateCategory($this->result);
        return $this->returnResult();
    }

    public function deleteCategory()
    {
        $this->checkAuthentication();
        $this->checkPermission('categories/delete');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->deleteCategory($this->result);
        return $this->returnResult();
    }

    // Settings management
    public function getSettings($name = null)
    {
        $this->checkAuthentication();
        $this->checkPermission('settings/get');

        $data = $this->getRequestData();
        $configMdl = new WposAdminSettings();
        $configMdl->setName($name ?? $data->name);
        $this->result = $configMdl->getSettings($this->result);
        return $this->returnResult();
    }

    public function saveSettings($name = null)
    {
        $this->checkAuthentication();
        $this->checkPermission('settings/set');

        $data = $this->getRequestData();
        $configMdl = new WposAdminSettings($data);
        if ($name) {
            $configMdl->setName($name);
        }
        $this->result = $configMdl->saveSettings($this->result);
        return $this->returnResult();
    }

    public function getPosSettings()
    {
        $this->checkAuthentication();
        $this->checkPermission('settings/pos/get');

        $data = $this->getRequestData();
        $configMdl = new WposAdminSettings();
        $configMdl->setName('pos');
        $this->result = $configMdl->getSettings($this->result);
        return $this->returnResult();
    }

    public function savePosSettings()
    {
        $this->checkAuthentication();
        $this->checkPermission('settings/pos/set');

        $data = $this->getRequestData();
        $configMdl = new WposAdminSettings($data);
        $configMdl->setName('pos');
        $this->result = $configMdl->saveSettings($this->result);
        return $this->returnResult();
    }

    public function getGeneralSettings()
    {
        $this->checkAuthentication();
        $this->checkPermission('settings/general/get');

        $data = $this->getRequestData();
        $configMdl = new WposAdminSettings();
        $configMdl->setName('general');
        $this->result = $configMdl->getSettings($this->result);
        return $this->returnResult();
    }

    public function saveGeneralSettings()
    {
        $this->checkAuthentication();
        $this->checkPermission('settings/general/set');

        $data = $this->getRequestData();
        $configMdl = new WposAdminSettings($data);
        $configMdl->setName('general');
        $this->result = $configMdl->saveSettings($this->result);
        return $this->returnResult();
    }

    public function getInvoiceSettings()
    {
        $this->checkAuthentication();
        $this->checkPermission('settings/invoice/get');

        $data = $this->getRequestData();
        $configMdl = new WposAdminSettings();
        $configMdl->setName('invoice');
        $this->result = $configMdl->getSettings($this->result);
        return $this->returnResult();
    }

    public function getOverviewStats()
    {
        $this->checkAuthentication();
        $this->checkPermission('stats/general');

        $data = $this->getRequestData();
        $statsMdl = new WposAdminStats($data);
        $this->result = $statsMdl->getOverviewStats($this->result);
        return $this->returnResult();
    }

    public function getItemSellingStats()
    {
        $this->checkAuthentication();
        $this->checkPermission('stats/itemselling');

        $data = $this->getRequestData();
        $statsMdl = new WposAdminStats($data);
        $this->result = $statsMdl->getWhatsSellingStats($this->result);
        return $this->returnResult();
    }

    public function getTakingsStats()
    {
        $this->checkAuthentication();
        $this->checkPermission('stats/takings');

        $data = $this->getRequestData();
        $statsMdl = new WposAdminStats($data);
        $this->result = $statsMdl->getCountTakingsStats($this->result);
        return $this->returnResult();
    }

    public function getLocationStats()
    {
        $this->checkAuthentication();
        $this->checkPermission('stats/locations');

        $data = $this->getRequestData();
        $statsMdl = new WposAdminStats($data);
        $this->result = $statsMdl->getDeviceBreakdownStats($this->result, 'location');
        return $this->returnResult();
    }

    public function getDeviceStats()
    {
        $this->checkAuthentication();
        $this->checkPermission('stats/devices');

        $data = $this->getRequestData();
        $statsMdl = new WposAdminStats($data);
        $this->result = $statsMdl->getDeviceBreakdownStats($this->result, 'device');
        return $this->returnResult();
    }

    public function getGeneralGraph()
    {
        $this->checkAuthentication();
        $this->checkPermission('graph/general');

        $data = $this->getRequestData();
        $graphMdl = new WposAdminGraph($data);
        $this->result = $graphMdl->getOverviewGraph($this->result);
        return $this->returnResult();
    }

    // Stock management
    public function getStock()
    {
        $this->checkAuthentication();
        $this->checkPermission('stock/get');

        $jsondata = new WposPosData();
        $this->result = $jsondata->getStock($this->result);
        return $this->returnResult();
    }

    public function addStock()
    {
        $this->checkAuthentication();
        $this->checkPermission('stock/add');

        $data = $this->getRequestData();
        $stockMdl = new WposAdminStock($data);
        $this->result = $stockMdl->addStock($this->result);
        return $this->returnResult();
    }

    public function setStock()
    {
        $this->checkAuthentication();
        $this->checkPermission('stock/set');

        $data = $this->getRequestData();
        $stockMdl = new WposAdminStock($data);
        $this->result = $stockMdl->setStockLevel($this->result);
        return $this->returnResult();
    }

    public function transferStock()
    {
        $this->checkAuthentication();
        $this->checkPermission('stock/transfer');

        $data = $this->getRequestData();
        $stockMdl = new WposAdminStock($data);
        $this->result = $stockMdl->transferStock($this->result);
        return $this->returnResult();
    }

    public function getStockHistory()
    {
        $this->checkAuthentication();
        $this->checkPermission('stock/history');

        $data = $this->getRequestData();
        $stockMdl = new WposAdminStock($data);
        $this->result = $stockMdl->getStockHistory($this->result);
        return $this->returnResult();
    }

    // Customer management
    public function addCustomer()
    {
        $this->checkAuthentication();
        $this->checkPermission('customers/add');

        $data = $this->getRequestData();
        $custMdl = new WposAdminCustomers($data);
        $this->result = $custMdl->addCustomer($this->result);
        return $this->returnResult();
    }

    public function editCustomer()
    {
        $this->checkAuthentication();
        $this->checkPermission('customers/edit');

        $data = $this->getRequestData();
        $custMdl = new WposAdminCustomers($data);
        $this->result = $custMdl->updateCustomer($this->result);
        return $this->returnResult();
    }

    public function deleteCustomer()
    {
        $this->checkAuthentication();
        $this->checkPermission('customers/delete');

        $data = $this->getRequestData();
        $custMdl = new WposAdminCustomers($data);
        $this->result = $custMdl->deleteCustomer($this->result);
        return $this->returnResult();
    }

    // User management
    public function getUsers()
    {
        $this->checkAuthentication();
        $this->checkPermission('users/get');

        $data = new WposPosData();
        $this->result = $data->getUsers($this->result);
        return $this->returnResult();
    }

    public function addUser()
    {
        $this->checkAuthentication();
        $this->checkPermission('users/add');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->addUser($this->result);
        return $this->returnResult();
    }

    public function editUser()
    {
        $this->checkAuthentication();
        $this->checkPermission('users/edit');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->updateUser($this->result);
        return $this->returnResult();
    }

    public function deleteUser()
    {
        $this->checkAuthentication();
        $this->checkPermission('users/delete');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->deleteUser($this->result);
        return $this->returnResult();
    }

    public function disableUser()
    {
        $this->checkAuthentication();
        $this->checkPermission('user/disable');

        $data = $this->getRequestData();
        $adminMdl = new WposAdminItems($data);
        $this->result = $adminMdl->setUserDisabled($this->result);
        return $this->returnResult();
    }

    // Device management
    public function addDevice()
    {
        $this->checkAuthentication();
        $this->checkPermission('devices/add');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->addDevice($this->result);
        return $this->returnResult();
    }

    public function editDevice()
    {
        $this->checkAuthentication();
        $this->checkPermission('devices/edit');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->updateDevice($this->result);
        return $this->returnResult();
    }

    public function deleteDevice()
    {
        $this->checkAuthentication();
        $this->checkPermission('devices/delete');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->deleteDevice($this->result);
        return $this->returnResult();
    }

    public function disableDevice()
    {
        $this->checkAuthentication();
        $this->checkPermission('devices/disable');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->setDeviceDisabled($this->result);
        return $this->returnResult();
    }

    public function getDeviceRegistrations()
    {
        $this->checkAuthentication();
        $this->checkPermission('devices/registrations');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->getDeviceRegistrations($this->result);
        return $this->returnResult();
    }

    public function deleteDeviceRegistration()
    {
        $this->checkAuthentication();
        $this->checkPermission('devices/registrations/delete');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->deleteDeviceRegistration($this->result);
        return $this->returnResult();
    }

    // Location management
    public function addLocation()
    {
        $this->checkAuthentication();
        $this->checkPermission('locations/add');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->addLocation($this->result);
        return $this->returnResult();
    }

    public function editLocation()
    {
        $this->checkAuthentication();
        $this->checkPermission('locations/edit');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->updateLocationName($this->result);
        return $this->returnResult();
    }

    public function deleteLocation()
    {
        $this->checkAuthentication();
        $this->checkPermission('locations/delete');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->deleteLocation($this->result);
        return $this->returnResult();
    }

    public function disableLocation()
    {
        $this->checkAuthentication();
        $this->checkPermission('locations/disable');

        $data = $this->getRequestData();
        $setup = new WposPosSetup($data);
        $this->result = $setup->setLocationDisabled($this->result);
        return $this->returnResult();
    }

    // Invoice management
    public function getInvoices()
    {
        $this->checkAuthentication();
        $this->checkPermission('invoices/get');

        $data = $this->getRequestData();
        $invMdl = new WposInvoices($data);
        $this->result = $invMdl->getInvoices($this->result);
        return $this->returnResult();
    }

    public function searchInvoices()
    {
        $this->checkAuthentication();
        $this->checkPermission('invoices/search');

        $data = $this->getRequestData();
        $invMdl = new WposInvoices();
        if (isset($data)) {
            $this->result = $invMdl->searchInvoices($data, $this->result);
        }
        return $this->returnResult();
    }

    public function addInvoice()
    {
        $this->checkAuthentication();
        $this->checkPermission('invoices/add');

        $data = $this->getRequestData();
        $invMdl = new WposInvoices($data);
        $this->result = $invMdl->createInvoice($this->result);
        return $this->returnResult();
    }

    public function editInvoice()
    {
        $this->checkAuthentication();
        $this->checkPermission('invoices/edit');

        $data = $this->getRequestData();
        $invMdl = new WposInvoices($data);
        $this->result = $invMdl->updateInvoice($this->result);
        return $this->returnResult();
    }

    public function deleteInvoice()
    {
        $this->checkAuthentication();
        $this->checkPermission('invoices/delete');

        $data = $this->getRequestData();
        $invMdl = new WposInvoices($data);
        $this->result = $invMdl->removeInvoice($this->result);
        return $this->returnResult();
    }

    // Tax management
    public function addTaxRule()
    {
        $this->checkAuthentication();
        $this->checkPermission('tax/rules/add');

        $data = $this->getRequestData();
        $tax = new WposAdminItems($data);
        $this->result = $tax->addTaxRule($this->result);
        return $this->returnResult();
    }

    public function editTaxRule()
    {
        $this->checkAuthentication();
        $this->checkPermission('tax/rules/edit');

        $data = $this->getRequestData();
        $tax = new WposAdminItems($data);
        $this->result = $tax->updateTaxRule($this->result);
        return $this->returnResult();
    }

    public function deleteTaxRule()
    {
        $this->checkAuthentication();
        $this->checkPermission('tax/rules/delete');

        $data = $this->getRequestData();
        $tax = new WposAdminItems($data);
        $this->result = $tax->deleteTaxRule($this->result);
        return $this->returnResult();
    }

    // Node/Socket management
    public function getNodeStatus()
    {
        $this->checkAuthentication();
        $this->checkPermission('node/status');

        $Sserver = new WposSocketControl();
        $this->result = $Sserver->isServerRunning($this->result);
        return $this->returnResult();
    }

    public function startNode()
    {
        $this->checkAuthentication();
        $this->checkPermission('node/start');

        $Sserver = new WposSocketControl();
        $this->result = $Sserver->startSocketServer($this->result);
        return $this->returnResult();
    }

    public function stopNode()
    {
        $this->checkAuthentication();
        $this->checkPermission('node/stop');

        $Sserver = new WposSocketControl();
        $this->result = $Sserver->stopSocketServer($this->result);
        return $this->returnResult();
    }

    public function restartNode()
    {
        $this->checkAuthentication();
        $this->checkPermission('node/restart');

        $Sserver = new WposSocketControl();
        $this->result = $Sserver->restartSocketServer($this->result);
        return $this->returnResult();
    }

    // Logging
    public function listLogs()
    {
        $this->checkAuthentication();
        $this->checkPermission('logs/list');

        $this->result['data'] = Logger::ls();
        return $this->returnResult();
    }

    public function readLog()
    {
        $this->checkAuthentication();
        $this->checkPermission('logs/read');

        $data = $this->getRequestData();
        $this->result['data'] = Logger::read($data->filename);
        return $this->returnResult();
    }

    // Database backup
    public function backupDatabase()
    {
        $this->checkAuthentication();
        $this->checkPermission('db/backup');

        $util = new WposAdminUtilities();
        $util->backUpDatabase();
    }

    // Message sending
    public function sendMessage()
    {
        $this->checkAuthentication();
        $this->checkPermission('message/send');

        $data = $this->getRequestData();
        $socket = new WposSocketIO();
        if ($data->device === null) {
            if (($error = $socket->sendMessageToDevices(null, $data->message)) !== true) {
                $this->result['error'] = $error;
            }
        } else {
            $devid = intval($data->device);
            $devices = new \stdClass();
            $devices->{$devid} = $devid;
            if (($error = $socket->sendMessageToDevices($devices, $data->message)) !== true) {
                $this->result['error'] = $error;
            }
        }
        return $this->returnResult();
    }

    // Device reset
    public function resetDevice()
    {
        $this->checkAuthentication();
        $this->checkPermission('device/reset');

        $data = $this->getRequestData();
        $socket = new WposSocketIO();
        if ($data->device === null) {
            if (($error = $socket->sendResetCommand()) !== true) {
                $this->result['error'] = $error;
            }
        } else {
            $devid = intval($data->device);
            $devices = new \stdClass();
            $devices->{$devid} = $devid;
            if (($error = $socket->sendResetCommand($devices)) !== true) {
                $this->result['error'] = $error;
            }
        }
        return $this->returnResult();
    }

    // File upload
    public function uploadFile()
    {
        $this->checkAuthentication();
        $this->checkPermission('file/upload');

        if (isset($_FILES['file'])) {
            $uploaddir = 'storage';
            $file_type = $_FILES['foreign_character_upload']['type'];

            $allowed = array("image/jpeg", "image/gif", "image/png", "application/pdf");
            if (!in_array($file_type, $allowed)) {
                $this->result['error'] = 'Only jpg, gif, and pdf files are allowed.';
                return $this->returnResult();
            }

            $newpath = $uploaddir . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);

            if (move_uploaded_file($_FILES['file']['tmp_name'], base_path($newpath)) !== false) {
                $this->result['data'] = ["path" => "/" . $newpath];
            } else {
                $this->result['error'] = "There was an error uploading the file " . $newpath;
            }
        } else {
            $this->result['error'] = "No file selected";
        }
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
            echo (json_encode(["error" => "Failed to encode the response data into json"]));
        } else {
            echo ($resstr);
        }
        die();
    }
}
