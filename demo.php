<?php

require __DIR__ . '/RottenTomatoes.php';

$rt	= new RottenTomatoes;

try {
	var_dump($rt->search('ant'));
} catch (Exception $e) {
	var_dump($e->getMessage());
}