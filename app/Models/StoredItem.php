<?php

namespace App\Models;

/**
 *
 * StoredItem: lightweight DTO representing an administrative inventory item.
 *
 */

class StoredItem extends \stdClass
{

    public $code = "";
    public $qty = "";
    public $name = "";
    public $alt_name = "";
    public $description = "";
    public $taxid = 1;
    public $price = "";
    public $cost = "";
    public $supplierid = 0;
    public $categoryid = 0;
    public $type = "general";
    public $modifiers = [];

    /**
     * Set any provided data
     * @param $data
     */
    function __construct($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
