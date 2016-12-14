<?php

namespace App\Persistence;

use \PDO as PDO;
use \PDOException as PDOException;

/**
 * Efetua ligação a DB
 */
class DB
{

    private static $conn = null;

    /**
     * Efetua a ligação com o banco caso ainda não tenha conectado
     * @return \PDOStatement
     * @throws PDOException
     */
    public static function connect(): \PDO
    {
        if(is_null(self::$conn))
        {
            try
            {
                $stringConnection = sprintf('mysql:host=%s;dbname=%s', HOSTNAME, HOSTDB);
                self::$conn       = new PDO($stringConnection, HOSTUSER, HOSTPASS, array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ));
            }
            catch(PDOException $e)
            {
                throw $e;
            }
        }

        return self::$conn;
    }

    /**
     * Executa uma query no banco e já a executa
     * @param string $sql
     * @return \PDOStatement
     */
    public static function query(string $sql): \PDOStatement
    {
        $db = self::prepare($sql);
        $db->execute();
        return $db;
    }

    /**
     * Executa uma query no banco e retorna para que o usuário efetue o `execute`
     * @param string $sql
     * @return \PDOStatement
     */
    public static function prepare(string $sql): \PDOStatement
    {
        return self::connect()->prepare($sql);
    }

    public function __destruct()
    {
        self::$conn = null;
    }

}
