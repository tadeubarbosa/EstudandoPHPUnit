<?php

require_once './vendor/autoload.php';

$insert = new \App\Usuarios();

if(!$insert->exists(array('email' => 'tadeufbarbosa@gmail.com')))
{
    $insert->setNome('Tadeu Barbosa');
    $insert->setEmail('tadeufbarbosa@gmail.com');
    $insert->save();
}

$usuario = new \App\Usuarios();
$usuario->findByEmail('tadeufbarbosa@gmail.com');

echo "<h1>Usuário {$usuario->getNome()}</h1>";

echo "<h2>Alterando nome...<h2>";

$usuario->update(array('nome' => 'Tadeu ' . rand(122, 544)));

echo "<h3>Usuário {$usuario->getNome()}</h3>";
