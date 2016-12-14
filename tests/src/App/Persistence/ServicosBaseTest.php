<?php

use \App\Usuarios as Usuarios;
use \PDO as PDO;
use App\Persistence\DB as DB;

/**
 * Description of ServicosBaseTest
 *
 * @author Tadeu
 */
class ServicosBaseTest extends PHPUnit_Framework_TestCase
{

    protected $user;

    public function setUp()
    {
        $this->user = new Usuarios;

        $this->user->delete(array('email' => 'tadeu@email.com'));

        $this->user->setNome('Tadeu');
        $this->user->setEmail('tadeu@email.com');
        $this->user->save();
    }

    public function tearDown()
    {
        $this->user->delete();
    }

    public function testSetarVariaveisData()
    {
        $user    = new Usuarios();
        $user->setNome('Tadeu');
        $user->setEmail('tadeu@email.com');
        $allData = $user->getData();

        $expect = array('nome' => 'Tadeu', 'email' => 'tadeu@email.com');
        $this->assertEquals($expect, $allData);
    }

    public function testSalvarDadosNoBD()
    {
        $select = new Usuarios();
        $user   = $select->select(array('email' => 'tadeu@email.com'));
        $user   = $user->fetch(PDO::FETCH_OBJ);

        $this->assertEquals('Tadeu', $user->nome);
    }

    public function testBuscarDadosPorAlgumCampo()
    {
        $find = new Usuarios();
        $find->findByEmail('tadeu@email.com');

        $this->assertEquals('Tadeu', $find->getNome());
    }

    public function testVerificaInformacaoExisteNoBD()
    {
        $exists = $this->user->exists(array('email' => 'tadeu@email.com'));
        $this->assertTrue($exists);
    }

    public function testAtualizarInformacoes()
    {
        $this->user->update(array('nome' => 'Tadeu Barbosa'), array('email' => 'tadeu@email.com'));

        $db   = DB::query("SELECT nome FROM {$this->user->getTable()} WHERE email = 'tadeu@email.com'");
        $user = $db->fetch(\PDO::FETCH_OBJ);

        $this->assertEquals('Tadeu Barbosa', $user->nome);
    }

}
