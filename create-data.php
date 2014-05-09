<?php
require_once(dirname(__FILE__) . '/../../../../default/settings.php');

define('FORMAT', 'json');

$vocabularies = array(
	1 => array(
		'vocabulary' => 'category',
		'type' => 'single',
	),
	3 => array(
		'vocabulary' => 'action',
		'type' => 'multiple',
	),
	4 => array(
		'vocabulary' => 'country',
		'type' => 'single',
	),
	5 => array(
		'vocabulary' => 'date',
		'type' => 'single',
	),
	6 => array(
		'vocabulary' => 'subject',
		'type' => 'single',
	),
	7 => array(
		'vocabulary' => 'location',
		'type' => 'single',
	),
	8 => array(
		'vocabulary' => 'shot_type',
		'type' => 'single',
	),
	9 => array(
		'vocabulary' => 'object',
		'type' => 'multiple',
	),
	10 => array(
		'vocabulary' => 'archetype',
		'type' => 'single',
	),
	11 => array(
		'vocabulary' => 'archetype2',
		'type' => 'single',
	),
	12 => array(
		'vocabulary' => 'geography',
		'type' => 'multiple',
	),
);

mysql_connect($databases['default']['default']['host'], $databases['default']['default']['username'], $databases['default']['default']['password']);
mysql_select_db($databases['default']['default']['database']);
mysql_query('SET NAMES utf8');

$q = 'SELECT * FROM node WHERE type="memory"';
// $q .= ' LIMIT 100';
$res = mysql_query($q);
$memories = array();
$counter = 0;
while ($row = mysql_fetch_assoc($res)) {
	$memory = array();
	$memory['id'] = (int)$row['nid'];
	$memory['title'] = $row['title'];
	$memory['thumb'] = getThumb($row['nid']);
	// $memory['duration'] = getDuration($row['nid']);
	// $memory['start_time'] = getStartTime($row['nid']);
	$memory['url'] = getField($row['nid'], 'field_data_field_file_url');
	foreach($vocabularies as $vId => $props) { 
		$terms = getTerms($vId, $row['nid'], $props['type']);
		if(FORMAT == 'csv' && is_array($terms)){
			$terms = join($terms, ';');
		}
		$memory[$props['vocabulary']] = $terms;
	}
	$memories[] = $memory;
	print ++$counter . PHP_EOL;
}

if (FORMAT == 'json') {
	// file_put_contents('memories.json', 'var data = ' . json_encode($memories));
	file_put_contents('memories.json', json_encode($memories));
	$vals = [];
	foreach ($memories as $memory) {
		foreach ($memory as $key => $value) {
			if (in_array($key, array('thumb', 'url'))) {
				continue;
			}
			if (!is_array($value)) {
				$values = array($value);
			} else {
				$values = $value;
			}
			foreach ($values as $value) {
				$vals[] = $value;
			}
		}
	}
	$vals = array_unique($vals);
	sort($vals);
	function prepareForExport($s){
		return $s . PHP_EOL;
	}
	$vals = array_map('prepareForExport', $vals);
	file_put_contents('autocomplete-values.txt', $vals);
} elseif(FORMAT == 'csv'){
	$output = fopen("memories.csv",'w') or die("Can't open memories.csv");
	$headers = array('id','name','thumb', 'start', 'duration', 'url');
	foreach ($vocabularies as $vocabulary) {
		$headers[] = $vocabulary['vocabulary'];
	}
	// var_dump($headers); exit;
	fputcsv($output, $headers);
	foreach($memories as $memory) {
		fputcsv($output, $memory);
	}
	
}

function getThumb($nodeId){
	$q = 'SELECT * FROM file_usage JOIN file_managed ON file_usage.fid = file_managed.fid WHERE id=' . $nodeId;
	$res = mysql_query($q);
	$row = mysql_fetch_assoc($res);
	return $row['filename'];
}

function getTerms($vId, $nodeId, $type){
	$q = 'SELECT * FROM taxonomy_term_data JOIN taxonomy_index ON taxonomy_term_data.tid = taxonomy_index.tid WHERE nid=' . $nodeId . ' AND vid = ' . $vId;
	$res = mysql_query($q);
	$terms = array();
	while ($row = mysql_fetch_assoc($res)) {
		// var_dump($row);
		// $terms[] = (int)$row['tid'];
		$terms[] = $row['name'];
	}
	if ($type == 'single') {
		if (!empty($terms[0])) {
			return $terms[0];
		} else {
			// var_dump($nodeId);
			// var_dump($terms);
			// return null;
			return 'none';
		}
	} else if($type == 'multiple'){
		if (empty($terms)) {
			$terms = array('none');
		}
		return $terms;
	}
}

function getField($nId, $field){
	$q = 'SELECT * FROM ' . $field . ' WHERE entity_id = ' . $nId;
	$res = mysql_query($q);
	$row = mysql_fetch_assoc($res);
	return $row[str_replace('field_data_', '', $field) . '_value'];
}

function getDuration($nId){
	$timeStr = getField($nId, 'field_data_field_duration_timecode');
	$hours = (int) substr($timeStr, 0, 2);
	$mins = (int) substr($timeStr, 3, 2) + $hours * 60;
	$secs = (int) substr($timeStr, 6, 2) + $mins * 60;
	$secs += ((int) substr($timeStr, 9, 2)) / 100;
	return $secs;	
}

function getStartTime($nId){
	$timeStr = getField($nId, 'field_data_field_start_timecode');
	return timeCode2Secs($timeStr);
}

function timeCode2Secs($timeStr){
	$hours = (int) substr($timeStr, 0, 2);
	$mins = (int) substr($timeStr, 3, 2) + $hours * 60;
	$secs = (int) substr($timeStr, 6, 2) + $mins * 60;
	$secs += ((int) substr($timeStr, 9, 2)) / 100;
	return $secs;	
}