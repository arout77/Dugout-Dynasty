<?php

namespace Core;

use PDO;

/**
 * The base model which all other models will extend.
 */
abstract class BaseModel
{
    protected PDO $db;

    public function __construct()
    {
        // Get the singleton PDO instance
        $this->db = Database::getInstance();
    }

    /**
     * @return mixed
     */
    public function getDb(): PDO
    {
        return $this->db;
    }

    /**
     * Allow direct execution of queries on the model as a fallback/shortcut.
     */
    public function execute( string $sql, array $params = [] )
    {
        $stmt = $this->db->prepare( $sql );
        $stmt->execute( $params );
        return $stmt; // Return the statement so we can call fetchAll()
    }
}
