<?php

namespace App\Persistence;

use \Exception as Exception;
use \PDOStatement as PDOStatement;

/**
 * Efetua a ligação entre o service e a DB
 */
class ServicosBase
{

    protected $data       = array();
    protected $table      = null;
    protected $updatedKey = "updatedKey_";

    /**
     * Operação de adicionar um valor ou retornar de dentro do array $data
     * @param string $key
     * @param array $values
     * @throws Exception
     */
    public function __call(string $key, array $values)
    {
        if(empty($key))
        {
            throw new Exception('Não foi pssível setar a variável, nenhum valor foi passado.');
        }

        if(preg_match("/set([a-zA-Z0-9]{1,})/", $key, $match))
        {
            $keyName              = strtolower($match[1]);
            $this->data[$keyName] = $values[0];
        }

        if(preg_match("/get([a-zA-Z0-9]{1,})/", $key, $match))
        {
            $keyName = strtolower($match[1]);
            return $this->data[$keyName] ?: null;
        }

        if(preg_match("/findBy([a-zA-Z0-9]{1,})/", $key, $match))
        {
            $keyName = strtolower($match[1]);
            $value   = $values[0];
            $this->findBy($keyName, $value);
        }
    }

    public function table(string $table)
    {
        $this->table = $table;
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Efetua o `UPDATE data` do banco
     *
     * @param array $update
     * @param array $where
     * @return PDOStatement
     */
    public function update(array $update, array $where = array()): PDOStatement
    {
        $this->verifyUpdateParams($update, $where);

        $where        = $this->whereGetCollumUnique();
        $updateString = $this->arrayDataStringDelimiter($update, ",");
        $whereString  = $this->arrayDataStringDelimiter($where, "AND");

        $db = DB::prepare("UPDATE {$this->table} SET {$updateString} WHERE {$whereString}");

        $data = array_merge($update, $where);
        foreach($data as $key => $value)
        {
            $db->bindParam(":{$key}", $value);
            unset($data[$key]);
            $data[":{$key}"] = $value;

            $this->data[$key] = $value;
        }

        $db->execute($data);

        return $db;
    }

    /**
     * Efetua o `INSERT data` do banco
     *
     * @param array $data
     * @return PDOStatement
     */
    public function insert(array $data = array()): PDOStatement
    {
        $data     = $this->verifyIfDataIsEmpty($data);
        $items    = array_keys($data);
        $collumns = implode(',', $items);
        $values   = ':' . implode(',:', $items);

        $db = DB::prepare("INSERT INTO {$this->table} ({$collumns}) VALUES ({$values})");

        foreach($data as $key => $value)
        {
            $db->bindParam(":{$key}", $value);
            unset($data[$key]);
            $data[":{$key}"] = $value;
        }

        $db->execute($data);

        return $db;
    }

    /**
     * Efeuta o `SELECT * FROM` do banco
     *
     * @param array $data
     * @param string $collumsSelect
     * @return PDOStatement
     */
    public function select(array $data = array(), string $collumsSelect = "*"): PDOStatement
    {
        $data   = $this->verifyIfDataIsEmpty($data);
        $select = $this->arrayDataStringDelimiter($data, "AND");

        $db = DB::prepare("SELECT {$collumsSelect} FROM `{$this->table}` WHERE {$select}");

        foreach($data as $key => $value)
        {
            $db->bindParam(":{$key}", $value);
            unset($data[$key]);
            $data[":{$key}"] = $value;
        }

        $db->execute($data);

        return $db;
    }

    /**
     * Efetua o `DELETE * FROM` do banco
     * @param array $data
     * @return PDOStatement
     */
    public function delete(array $data = array()): PDOStatement
    {
        if(empty($data))
        {
            $data = $this->whereGetCollumUnique();
        }
        else
        {
            $data = $this->verifyIfDataIsEmpty($data);
        }

        $delete = $this->arrayDataStringDelimiter($data, "AND");

        $db = DB::prepare("DELETE FROM `{$this->table}` WHERE {$delete}");

        foreach($data as $key => $value)
        {
            $db->bindParam(":{$key}", $value);
            unset($data[$key]);
            $data[":{$key}"] = $value;
        }

        $db->execute($data);

        return $db;
    }

    /**
     * Verifica se os dados passados existem no banco
     * @param array $data
     * @return bool
     */
    public function exists(array $data = array()): bool
    {
        if(empty($data))
        {
            $data = $this->getWheresByItems();
        }

        $db = $this->select($data);
        return $db->rowCount() ? true : false;
    }

    /**
     * Salva os dados no banco
     *
     * @param array $where
     * @return PDOStatement
     * @throws Exception
     */
    public function save(array $where = array()): PDOStatement
    {
        $where = $this->getWheresByItems($where);

        if($this->exists())
        {
            $data   = $this->getData();
            $unique = current(array_keys($where));
            unset($data[$unique]);

            if(empty($data))
            {
                throw new Exception('Ops! Nada para salvar.');
            }

            $db = $this->update($data, $where);
        }
        else
        {
            $db = $this->insert();
        }

        return $db;
    }

    /**
     * Busca por um dado no banco
     *
     * @param type $key
     * @param type $value
     * @return \App\Persistence\ServicosBase
     * @throws Exception
     */
    public function findBy($key, $value): ServicosBase
    {
        if(is_null($value))
        {
            throw new Exception('Você não passou nenhum valor para o findBy.');
        }

        $find = array("{$key}" => "{$value}");
        $db   = $this->select($find);
        $data = $db->fetch(\PDO::FETCH_ASSOC);

        if(!is_array($data))
        {
            throw new Exception("A sua busca em findBy não retornou nenhum resultado: findby{$key}({$value})");
        }

        foreach($data as $key => $value)
        {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Prepara um array para a consulta SQL
     *
     * @param array $dataReceived
     * @param string $delimieter
     * @return string
     */
    protected function arrayDataStringDelimiter(array $dataReceived, string $delimieter): string
    {
        $data     = $this->verifyIfDataIsEmpty($dataReceived);
        $collumns = array();

        foreach($data as $key => $value)
        {
            $collumns[] = sprintf("{$key}=:{$key}");
        }

        return implode(" {$delimieter} ", $collumns);
    }

    /**
     * Verifica se o array $data está vazio
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function verifyIfDataIsEmpty(array $data = array()): array
    {
        $data = !empty($data) ? $data : $this->getData();

        if(empty($data) and empty($this->getData()))
        {
            throw new \Exception('Nenhum dado passado para selecionar no banco.');
        }

        if(empty($this->getTable()))
        {
            throw new \Exception('Nenhuma tabela setada.');
        }

        return $data;
    }

    /**
     * Verifica se o array $where está vazio
     *
     * @param array $where
     * @return array
     * @throws Exception
     */
    public function getWheresByItems(array $where = array()): array
    {

        if(empty($where) and ! isset($this->collumUnique))
        {
            throw new Exception('Você deve setar no controller uma coluna que tenha um valor único.');
        }
        elseif(isset($this->collumUnique) and ! isset($this->getData()[$this->collumUnique]))
        {
            throw new Exception("Ops! Você não setou a coluna: {$this->collumUnique}");
        }
        elseif(isset($this->getData()[$this->collumUnique]))
        {
            $where[$this->collumUnique] = $this->getData()[$this->collumUnique];
        }

        return $where;
    }

    /**
     * Verifica se os parametros para o UPDATE foram passados corretamente
     *
     * @param array $update
     * @param array $where
     * @throws Exception
     */
    protected function verifyUpdateParams(array $update, array $where = array())
    {
        if(is_array(current($update)) or is_array(current($where)))
        {
            throw new Exception('Os arrays de itens estão vazios (update|where). Esperado: array("nome"=>"valor").');
        }
    }

    /**
     * Biusca pelo item da coluna única setado no Service pela variável $collumUnique
     * @return array
     * @throws Exception
     */
    public function whereGetCollumUnique(): array
    {

        $data = $this->getData();

        if(isset($data[$this->collumUnique]))
        {
            $where = array("{$this->collumUnique}" => "{$data[$this->collumUnique]}");
        }
        else
        {
            throw new Exception('Você não definiu nenhum valor para `where` e o valor para `collumUnique` não foi setado.');
        }

        return $where;
    }

}
