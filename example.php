<?php

error_reporting(E_ALL | E_STRICT);
require_once 'PoParser.php';

$parser = new PoParser();
$poObject = $parser->parsePoFile('example.po');

// Print all the strings
var_dump($poObject);

// Print the number of translated/fuzzy/total strings
var_dump($parser->gettextStatus($poObject));

// Print the percentage done
var_dump($parser->getPercentageDone($poObject));
