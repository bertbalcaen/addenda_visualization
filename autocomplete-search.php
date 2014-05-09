<?php
$fp = fopen('autocomplete-values.txt', 'r');

$matches = array();
while (($val = fgets($fp, 4096)) !== false) {
	if(strpos(strtolower($val), strtolower($_GET['term'])) !== false){
		$matches[] = strtolower($val);
		if (count($matches) > 40) {
			break;
		}
	}
}
header('Content-Type: text/json; charset=utf-8');
print json_encode($matches);