<?php

use App\Persistence\DB;

/**
 * Description of DBTest
 *
 * @author Tadeu
 */
class DBTest extends PHPUnit_Framework_TestCase
{

    public function startUp()
    {
        DB::query('DROP TABLE IF EXISTS `test_phpunit`');
    }

    public function tearDown()
    {
        DB::query('DROP TABLE IF EXISTS `test_phpunit`');
    }

    public function testConexaoComBanco()
    {

        $create = DB::query('CREATE TABLE IF NOT EXISTS `test_phpunit`'
                        . ' (`nome` VARCHAR(200) NOT NULL , `email` VARCHAR(200) NOT NULL )');

        $insert = DB::query('INSERT INTO `test_phpunit` (nome, email) VALUES ("Tadeu", "tadeufbarbosa@gmail.com")');

        $usuarios = DB::query('SELECT COUNT(*) FROM `test_phpunit`');
        $count    = $usuarios->fetchColumn();

        $this->assertEquals(1, $count);
    }

}
