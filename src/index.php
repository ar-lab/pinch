<?php

require_once '../vendor/autoload.php';

$pinch = new Pinch();

$pinch->setConfig(array(
    'title' => 'Pinch Docs Viewer',
    'description' => 'Create your documentation easily',
    'theme' => 'lightview',
));

$pinch->showContent();