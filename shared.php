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

// GET CONST FUNC

// function GetWPUser() { return esc_html(wp_get_current_user()->user_login); }
function GetWPUser() { return 'guest'; }
function GetCondaActivate() { return $GLOBALS['bmCondaEnv'].'/bin/activate base'; }
function GetBenchmarkPipeline() { return $GLOBALS['bmPipelineScript']; }
function GetRearrTable() { return $GLOBALS['bmRearrTable']; }
function GetMetrics() { return $GLOBALS['bmMetrics']; }
function GetLogs() { return $GLOBALS['bmLogs']; }
function GetCool() { return $GLOBALS['bmCool']; }

// FUNC

// Error/Success Message

function Message($Message, $IsError) {
	if ($IsError) { return '<font color="red">'.$Message.'</font>'; }
	else { return '<font color="green">'.$Message.'</font>'; }
}

// Data load func

function TsvToArray($FileName) {
	if (!($FileStream = fopen($FileName, 'r'))) {  die(Message('Unable to open file: "'.$FileName.'"', true)); }
	$RowID = 0;
	$ColumnNames = fgetcsv($FileStream, 0, "\t");
	$TableArray = array();
	while ($Row = fgetcsv($FileStream, 0, "\t")) $TableArray[] = array_combine($ColumnNames, $Row);
	fclose($FileStream);
	return $TableArray;
}

// DB func

function DBSelect($DBQuery) {
	$dbPath = 'sqlite:'.GetMetrics();
	try { $DataBase = new PDO($dbPath); } catch(Exception $Exception) { die(Message('Cannot open the database: '.$Exception, true)); }
	$DBResponse = $DataBase->query($DBQuery);
	$DataBase = null;
	return $DBResponse;
}

// Get slices

function GetResolutions() {
	$ResolutionsList = array('0' => '(none)');
	foreach (json_decode(file_get_contents($GLOBALS['bmJsonConfig']), true)['resolutions'] as $Res) $ResolutionsList[strval($Res)] = strval(intval($Res / 1000)).' kb';
	return $ResolutionsList;
	}

function GetSamples() {
	$SamplesList = array('0' => '(none)');
	foreach (TsvToArray($GLOBALS['bmRearrTable']) as $Row) $SamplesList[$Row['rearrangement_ID']] = $Row['rearrangement_ID'];
	return $SamplesList;
}

function GetUploadedFiles($Username) {
	$UserDir = $GLOBALS['bmUploads'].'/'.$Username;
	shell_exec('mkdir -p "'.$UserDir.'"');
	$ScanDir = array_filter(scandir($UserDir), function($File) { return !is_dir($UserDir."/".$File); });
	$UploadedFilesList = array('0' => '(none)');
	foreach ($ScanDir as $FileName) $UploadedFilesList[$UserDir.'/'.htmlspecialchars($FileName)] = htmlspecialchars($FileName);
	return $UploadedFilesList;
}

function GetModelsUser() { 
	$DBResponse = DBSelect('SELECT [Metadata.ModelName] FROM bm_metrics WHERE "Metadata.Author"="'.GetWPUser().'";');
	$TableArray = array(); 
	while ($Row = $DBResponse->fetch()) array_push($TableArray, $Row['Metadata.ModelName']);
	return $TableArray;
	}

// Secret func

function MakeSecretString($Username) { return 'You are the heir of House Targaryen, and your name, your real name, is '.$Username.'.'; }
function GetSecret($Username) { return hash('sha256', MakeSecretString($Username)); }
function CheckSecret($Username, $SecretHash) { return $SecretHash == GetSecret($Username); }

?>
