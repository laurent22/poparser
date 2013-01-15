<?php

error_reporting(E_ALL | E_STRICT);
require_once 'PoParser.php';
$p = new PoParser();
$po = $p->parsePoFile('appetizer.po');
var_dump($p->gettextStatus($po));
var_dump($p->getPercentageDone($po));
