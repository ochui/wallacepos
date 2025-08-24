<?php

/**
 *
 * AdminCustomers is used to modify administrative items including stored items, suppliers, customers and users.
 *
 */

namespace App\Controllers\Admin;

use App\Communication\SocketIO;
use App\Database\CustomerModel;
use App\Integration\GoogleIntegration;
use App\Utility\JsonValidate;
use App\Utility\Logger;
use App\Utility\Mail;

class AdminCustomers
{
    private $data;

    /**
     * Set any provided data
     * @param $data
     */
    function __construct($data = null)
    {
        // parse the data and put it into an object
        if ($data !== null) {
            $this->data = $data;
        } else {
            $this->data = new \stdClass();
        }
    }

    // CUSTOMERS
    /**
     * Add customer
     * @param $result
     * @return mixed
     */
    public function addCustomer($result)
    {
        $jsonval = new JsonValidate($this->data, '{"postcode":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $res = $this->addCustomerData($this->data);
        if (is_array($res)) {
            $result['data'] = $res;
        } else {
            $result['error'] = $res;
        }
        return $result;
    }

    /**
     * Statically adds a customer to the database
     * @param $data
     * @return bool|string
     */
    public static function addCustomerData($data)
    {
        $settings = AdminSettings::getSettingsObject('general');
        $gid = '';
        if ($settings->gcontact == 1) {
            // add google
            $gres = GoogleIntegration::setGoogleContact($settings, $data);
            $gid = ($gres[0] !== false ? $gres[1] : '');
        }
        $custMdl = new CustomerModel();
        $qresult = $custMdl->create($data->email, $data->name, $data->phone, $data->mobile, $data->address, $data->suburb, $data->postcode, $data->state, $data->country, $gid);
        if ($qresult === false) {
            return $custMdl->errorInfo;
        } else {
            // get full customer record
            $data = self::getCustomerData($qresult);
            // broadcast to devices
            $SocketIO = new SocketIO();
            $SocketIO->sendCustomerUpdate($data);
            // log data
            Logger::write("Customer added with id:" . $qresult, "CUSTOMER", json_encode($data));

            return $data;
        }
    }

    /**
     * Update customer
     * @param $result
     * @return mixed
     */
    public function updateCustomer($result)
    {
        $jsonval = new JsonValidate($this->data, '{"id":1, "postcode":-1}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $res = $this->updateCustomerData($this->data);
        if (is_array($res)) {
            $result['data'] = $res;
        } else {
            $result['error'] = $res;
        }
        return $result;
    }

    /**
     * Statically update customer data
     * @param $data
     * @return bool|string
     */
    public static function updateCustomerData($data)
    {
        $settings = AdminSettings::getSettingsObject('general');
        $custMdl = new CustomerModel();
        $gid = null;
        if ($settings->gcontact == 1) {
            // get google id
            $gid = $custMdl->get($data->id)[0]['googleid'];
            if ($gid) {
                // edit google
                $gres = GoogleIntegration::setGoogleContact($settings, $data, $gid);
            } else {
                // add google
                $gres = GoogleIntegration::setGoogleContact($settings, $data);
            }
            if ($gres[0] == true) {
                $gid = $gres[1];
            }
        }
        $qresult = $custMdl->edit($data->id, $data->email, $data->name, $data->phone, $data->mobile, $data->address, $data->suburb, $data->postcode, $data->state, $data->country, $data->notes, $gid);
        if ($qresult === false) {
            return "Could not edit the customer: " . $custMdl->errorInfo;
        } else {
            // get full customer record
            $_data = self::getCustomerData($data->id);
            // broadcast to devices
            $SocketIO = new SocketIO();
            $SocketIO->sendCustomerUpdate($_data);
            // log data
            Logger::write("Customer updated with id:" . $_data[0]['id'], "CUSTOMER", json_encode($_data));

            return $_data;
        }
    }

    /**
     * Delete customer
     * @param $result
     * @return mixed
     */
    public function deleteCustomer($result)
    {
        // validate input
        if (!is_numeric($this->data->id)) {
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        $custMdl = new CustomerModel();
        $qresult = $custMdl->remove($this->data->id);
        if ($qresult === false) {
            $result['error'] = "Could not delete the customer";
        } else {
            $result['data'] = true;

            // log data
            Logger::write("Customer deleted with id:" . $this->data->id, "CUSTOMER");
        }
        return $result;
    }

    /**
     * Get customer data as an array
     * @param $id
     * @return mixed
     */
    public static function getCustomerData($id)
    {
        $custMdl = new CustomerModel();
        $customer = $custMdl->get($id);
        if (!is_array($customer) || count($customer) == 0) {
            return null;
        }
        $customer['contacts'] = [];
        $contacts = $custMdl->getContacts($id);
        if (is_array($contacts)) {
            foreach ($contacts as $contact) {
                $customer['contacts'][$contact['id']] = $contact;
            }
        }
        return $customer;
    }
    // CUSTOMER CONTACTS
    /**
     * Add customer
     * @param $result
     * @return mixed
     */
    public function addContact($result)
    {
        $jsonval = new JsonValidate($this->data, '{"customerid":1, "name":"name"}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $custMdl = new CustomerModel();
        $qresult = $custMdl->createContact($this->data->customerid, $this->data->email, $this->data->name, $this->data->phone, $this->data->mobile, $this->data->position, $this->data->receivesinv);
        if ($qresult === false) {
            $result['error'] = "Could not add the contact: " . $custMdl->errorInfo;
        } else {
            $result['data'] = $this->getCustomerData($this->data->customerid);
            // broadcast to devices
            $SocketIO = new SocketIO();
            $SocketIO->sendCustomerUpdate($result['data']);
            // log data
            Logger::write("Contact added with id:" . $this->data->id . " to customer id: " . $this->data->customerid, "CUSTOMER", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Update customer
     * @param $result
     * @return mixed
     */
    public function updateContact($result)
    {
        $jsonval = new JsonValidate($this->data, '{"id":1, "customerid":1, "name":"name"}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $custMdl = new CustomerModel();
        $qresult = $custMdl->editContact($this->data->id, $this->data->email, $this->data->name, $this->data->phone, $this->data->mobile, $this->data->position, $this->data->receivesinv);
        if ($qresult === false) {
            $result['error'] = "Could not edit the contact: " . $custMdl->errorInfo;
        } else {
            $result['data'] = $this->getCustomerData($this->data->customerid);;
            // broadcast to devices
            $SocketIO = new SocketIO();
            $SocketIO->sendCustomerUpdate($result['data']);
            // log data
            Logger::write("Contact updated with id:" . $this->data->id, "CUSTOMER", json_encode($this->data));
        }
        return $result;
    }

    /**
     * Delete customer
     * @param $result
     * @return mixed
     */
    public function deleteContact($result)
    {
        // validate input
        if (!is_numeric($this->data->id)) {
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        $custMdl = new CustomerModel();
        $qresult = $custMdl->removeContact($this->data->id);
        if ($qresult === false) {
            $result['error'] = "Could not delete the contact: " . $custMdl->errorInfo;
        } else {
            $result['data'] = true;

            // log data
            Logger::write("Contact deleted with id:" . $this->data->id, "CUSTOMER");
        }
        return $result;
    }
    // Customer Access functions for admin use
    /**
     * Enable or disable customer access
     * @param $result
     * @return mixed
     */
    public function setAccess($result)
    {
        $jsonval = new JsonValidate($this->data, '{"id":1, "disabled":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $custMdl = new CustomerModel();
        $res = $custMdl->editAuth($this->data->id, null, null, $this->data->disabled);
        if ($res === false) {
            $result['error'] = "Could not set customer account status" . $custMdl->errorInfo;
        }
        return $result;
    }

    /**
     * Set customer password
     * @param $result
     * @return mixed
     */
    public function setPassword($result)
    {
        $jsonval = new JsonValidate($this->data, '{"id":1, "hash":""}');
        if (($errors = $jsonval->validate()) !== true) {
            $result['error'] = $errors;
            return $result;
        }
        $custMdl = new CustomerModel();
        $res = $custMdl->editAuth($this->data->id, $this->data->hash, 1, 0);
        if ($res === false) {
            $result['error'] = "Could not set customer account status: " . $custMdl->errorInfo;
        }
        return $result;
    }

    /**
     * Send password reset email to customer
     * @param $result
     * @return mixed
     */
    public function sendResetEmail($result)
    {
        // validate input
        if (!is_numeric($this->data->id)) {
            $result['error'] = "A valid id must be supplied";
            return $result;
        }
        // get customer details
        $custMdl = new CustomerModel();
        $customer = $custMdl->get($this->data->id)[0];
        if (strpos($customer['email'], '@') === -1) {
            $result['error'] = "The customer does not have a valid email";
            return $result;
        }
        // generate url
        $token = AdminUtilities::getToken();
        $link = "https://" . $_SERVER['SERVER_NAME'] . "/myaccount/resetpassword.php?token=" . $token;
        // set token
        if ($custMdl->setAuthToken($this->data->id, $token) === false) {
            $result['error'] = "Could not set auth token: " . $custMdl->errorInfo;
        }
        // send reset email
        $linkhtml = '<a href="' . $link . '">' . $link . '</a>';
        $mailer = new Mail();
        if (($mres = $mailer->sendPredefinedMessage($customer['email'], 'reset_email', ['name' => $customer['name'], 'link' => $linkhtml])) !== true) {
            $result['error'] = $mres;
        }
        return $result;
    }
}
