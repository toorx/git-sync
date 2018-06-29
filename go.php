<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/class/Go.php';

$getopts = new Fostam\GetOpts\Handler();

$getopts->addOption('file_repo')
    ->short('r')
    ->long('repo')
    ->description('file json dei repository')
    ->argument('input-file')
    ->defaultValue('repo.json');

$getopts->addOption('file_config')
    ->short('c')
    ->long('config')
    ->description('file json di config')
    ->argument('input-file')
    ->defaultValue('config.json');

$getopts->addOption('groups')
    ->short('g')
    ->long('groups')
    ->argument('list-groups')
    ->description('select groups separated by ", " (example: pippo, pluto )');

$getopts->addOption('repos')
    ->short('n')
    ->long('names')
    ->argument('list-repo')
    ->description('select repos separated by ", " (example: pippo, pluto )');


$getopts->addOption('postCommands')
    ->short('p')
    ->long('postCommands')
    ->description('enable postCommands after git clone/pull if set to repo into file json');

$getopts->parse();

$go = new Go($getopts->get());
$go->init();