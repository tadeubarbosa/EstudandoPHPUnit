<?php

switch($_SERVER['SERVER_NAME'])
{
    #
    default:
        define('HOSTNAME', 'localhost');
        define('HOSTDB', 'test_phpunit');
        define('HOSTUSER', 'root');
        define('HOSTPASS', '');
        break;
    #
}