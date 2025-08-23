<?php

/**
 *
 * ConfigModel extends the DbConfig PDO class to interact with the config DB table
 *
 */

namespace App\Database;

class ConfigModel extends DbConfig
{

    protected $_columns = ['id', 'name', 'data'];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $name
     * @param string $data
     *
     * @return bool|string Returns false on an unexpected failure, returns -1 if a unique constraint in the database fails, or the new rows id if the insert is successful
     */
    public function create($name, $data)
    {
        $sql = "INSERT INTO `config` (`name`, `data`) VALUES (:name, :data)";
        $placeholders = [":name" => $name, ":data" => $data];

        return $this->insert($sql, $placeholders);
    }

    /**
     * @param null $name config entry name
     *
     * @return array|bool Returns false on an unexpected failure or the number of rows affected by the update operation
     */
    public function get($name = null)
    {
        $sql = 'SELECT * FROM `config`';
        $placeholders = [];
        if ($name !== null) {
            if (empty($placeholders)) {
                $sql .= ' WHERE';
            }
            $sql .= ' name= :name';
            $placeholders[':name'] = $name;
        }

        return $this->select($sql, $placeholders);
    }

    /**
     * @param $name
     * @param $data
     *
     * @return array|bool  Returns false on an unexpected failure or the number of rows affected by the update operation
     */
    public function edit($name, $data)
    {

        $sql = 'UPDATE `config` SET `data`= :data WHERE `name`= :name';
        $placeholders = [":name" => $name, ":data" => $data];

        return $this->update($sql, $placeholders);
    }
}
