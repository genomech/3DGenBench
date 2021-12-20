<?php

// CONST

$GLOBALS['bmRearrTable'] = __DIR__.'/rearrangements_table.tsv';
$GLOBALS['bmPipelineScript'] = __DIR__.'/benchmark_pipeline.py';
$GLOBALS['bmJsonConfig'] = __DIR__.'/config.json';
$GLOBALS['bmCondaEnv'] = __DIR__.'/.pyenv';
$GLOBALS['bmExpDatasets'] = __DIR__.'/exp_datasets';
$GLOBALS['bmUploads'] = __DIR__.'/upload';
$GLOBALS['bmMetrics'] = __DIR__.'/benchmark_db.sqlite3';
$GLOBALS['bmLogs'] = __DIR__.'/logs';
$GLOBALS['bmCool'] = __DIR__.'/cool';

// FUNC

// Data load func

function TsvToArray($fname) {
	if (!($fp = fopen($fname, 'r'))) {  die('Unable to open file: "'.$fname.'"'); }
	$id = 0;
	$key = fgetcsv($fp, 0, "\t");
	$arr = array();
	while ($row = fgetcsv($fp, 0, "\t")) { $arr[] = array_combine($key, $row); }
	fclose($fp);
	return $arr;
}

function GetResolutions() {
	$ResolutionsList = array('0' => '(none)');
	foreach (json_decode(file_get_contents($GLOBALS['bmJsonConfig']), true)['resolutions'] as $res) $ResolutionsList[strval($res)] = strval(intval($res / 1000)).' kb';
	return $ResolutionsList;
	}

function GetSamples() {
	$SamplesList = array('0' => '(none)');
	foreach (TsvToArray($GLOBALS['bmRearrTable']) as $row) $SamplesList[$row['rearrangement_ID']] = $row['rearrangement_ID'];
	return $SamplesList;
}

function GetUploadedFiles($Username) {
	$UserDir = $GLOBALS['bmUploads'].'/'.$Username;
	shell_exec('mkdir -p "'.$UserDir.'"');
	$ScanDir = array_filter(scandir($UserDir), function($item) { return !is_dir($UserDir."/".$item); });
	$UploadedFilesList = array('0' => '(none)');
	foreach ($ScanDir as $f) $UploadedFilesList[$UserDir.'/'.htmlspecialchars($f)] = htmlspecialchars($f);
	return $UploadedFilesList;
}

// Secret func

function Message($message, $is_error) {
	if ($is_error) { return '<font color="red">'.$message.'</font>'; }
	else { return '<font color="green">'.$message.'</font>'; }
}

// function GetWPUser() { return esc_html(wp_get_current_user()->user_login); }
function GetWPUser() { return 'guest'; }
function GetCondaActivate() { return $GLOBALS['bmCondaEnv'].'/bin/activate base'; }
function GetBenchmarkPipeline() { return $GLOBALS['bmPipelineScript']; }
function GetRearrTable() { return $GLOBALS['bmRearrTable']; }
function GetMetrics() { return $GLOBALS['bmMetrics']; }
function GetLogs() { return $GLOBALS['bmLogs']; }
function GetCool() { return $GLOBALS['bmCool']; }

function MakeSecretString($Username) { return 'You are the heir of House Targaryen, and your name, your real name, is '.$Username.'.'; }
function GetSecret($Username) { return hash('sha256', MakeSecretString($Username)); }
function CheckSecret($Username, $SecretHash) { return $SecretHash == GetSecret($Username); }

?>
