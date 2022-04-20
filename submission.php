<?php

require('shared.php');

$Author = $_POST['submission_user'];
$Model = $_POST['submission_model'];
$Secret = $_POST['submission_secret'];
$DataType = $_POST['data_type'];

// Check secret
if (!CheckSecret($Author, $Secret)) die(Message('There is an impostor among us', true));

// Check model name
if (!preg_match('/^[A-Za-z0-9_]{5,100}$/', $Model)) die(Message('Wrong model name (5-100 Latin and numeric chars only)', true));

// Load Data
switch ($DataType) {
	case 'p': $SamplesList = GetSamples(); break;
	case 's': $SamplesList = GetSamplesWG(); break;
	default: die(Message('Wrong data type', true)); 
	}
$ResolutionsList = GetResolutions();
$UploadedFilesList = GetUploadedFiles($Author);

// Get TSs
function _matcher ($m, $str) { if (preg_match('/sample_(\d+)/i', $str, $matches)) $m[] = $matches[1]; return $m; }
$TssList = array_reduce(array_keys($_POST), '_matcher', array());

// CHECK TEXT DATA

$ProcessingList = array();

foreach ($TssList as $index) {
	
	$res = $_POST['resolution_'.$index];
	$samp = $_POST['sample_'.$index];
	if (!in_array($res, array_keys($ResolutionsList))) die(Message('Unknown resolution', true));
	if (!in_array($samp, array_keys($SamplesList))) die(Message('Unknown sample', true));
	if ($res == '0') die(Message('Resolution not defined', true));
	if ($samp == '0') die(Message('Sample not defined', true));
	$ProcessingList[$index] = array(
		'ID' => 'bm'.strtoupper(dechex(intval($index))),
		'Author' => $Author,
		'ModelName' => $Model,
		'SampleName' => $samp,
		'Resolution' => intval($res)
	);
}

// CHECK FILES

$Repeats = array();

foreach ($TssList as $index) {
	
	$wt_file = htmlspecialchars_decode($_POST['file_WT_'.$index]);
	if ($wt_file == '0') die(Message('No WT files were selected', true));
	if (!file_exists($wt_file)) die(Message('Some WT files were not found. Please reload the page', true));
	if (!in_array($wt_file, $Repeats)) array_push($Repeats, $wt_file);
	else die(Message('Some files have been chosen more than 1 times', true));
	$ProcessingList[$index]['WT'] = $wt_file;
	
	if ($DataType == 'p') {
		$mut_file = htmlspecialchars_decode($_POST['file_MUT_'.$index]);
		if ($mut_file == '0') die(Message('No MUT files were selected', true));
		if (!file_exists($mut_file)) die(Message('Some MUT files were not found. Please reload the page', true));
		if (!in_array($mut_file, $Repeats)) array_push($Repeats, $mut_file);
		else die(Message('Some files have been chosen more than 1 times', true));
		$ProcessingList[$index]['MUT'] = $mut_file;
	}
}

// SQL

$TableName = array('p' => 'bm_metrics', 's' => 'bm_metrics_wg')[$DataType];
$dbpath = 'sqlite:'.GetMetrics();
try { $dbh  = new PDO($dbpath); } catch(Exception $e) { die(Message('Cannot open the database: '.$e, true)); }
$query = 'INSERT INTO '.$TableName.' ( ID, Status, [Metadata.Author], [Metadata.ModelName], [Metadata.SampleName], [Metadata.Resolution], [Metadata.SubmissionDate]) VALUES ';
$values = array();
foreach ($ProcessingList as $index => $meta) array_push($values, '("'.$meta['ID'].'", 3, "'.$meta['Author'].'", "'.$meta['ModelName'].'", "'.$meta['SampleName'].'", '.$meta['Resolution'].', "'.date('Y-m-d H:i:s').'")');
$query .= implode(', ', $values).';';
$conn = $dbh->exec($query);
if (!$conn) {
	if ($dbh->errorCode() == 23000) die(Message('Units are already added to queue. Please reload the page', true)); 
	die(Message('Database error: '.$dbh->errorCode().' ('.$dbh->errorInfo()[2].')', true)); 
}
$dbh = null;

// MAKE SCRIPT

$units_list = '';

foreach ($ProcessingList as $index => $meta) {
$cmd = 'CMD="source '.GetCondaActivate().'; "; ';
$cmd .= 'CMD=\'\'$CMD\' echo "\'; ';
$cmd .= 'CMD=""$CMD"update '.$TableName.' set Status=\'1\' where ID=\''.$meta['ID'].'\';";';
$cmd .= 'CMD=\'\'$CMD\'" | sqlite3 "'.GetMetrics().'"; \'; ';
	if ($DataType == 'p') {
	$cmd .= 'CMD=\'\'$CMD\'python3 "'.GetBenchmarkPipeline().'" -i "'.$meta['ID'].'" -a "'.$meta['Author'].'" -m "'.$meta['ModelName'].'" -s "'.$meta['SampleName'].'" -r "'.$meta['Resolution'].'" -t "'.GetRearrTable().'" -W "'.$meta['WT'].'" -M "'.$meta['MUT'].'" -d "'.GetMetrics().'" -c "'.GetCool().'" -l "'.GetLogs().'/'.$meta['ID'].'.log"; \'; '; 
	} elseif ($DataType == 's') {
	$cmd .= 'CMD=\'\'$CMD\'python3 "'.GetBenchmarkPipelineWG().'" -i "'.$meta['ID'].'" -a "'.$meta['Author'].'" -m "'.$meta['ModelName'].'" -s "'.$meta['SampleName'].'" -r "'.$meta['Resolution'].'" -t "'.GetWGTable().'" -P "'.$meta['WT'].'" -d "'.GetMetrics().'" -c "'.GetCool().'" -l "'.GetLogs().'/'.$meta['ID'].'.log"; \'; '; }
	
	$cmd .= 'CMD=\'\'$CMD\'if [ $? -ne 0 ]; then { echo "\'; ';
	$cmd .= 'CMD=""$CMD"update '.$TableName.' set Status=\'2\' where ID=\''.$meta['ID'].'\';";';
	$cmd .= 'CMD=\'\'$CMD\'" | sqlite3 "'.GetMetrics().'"; } fi; \'; ';
	$cmd .= 'tsp bash -c "${CMD}";';
$units_list .= '[<a href="'.GetMetricsPage().'?id='.$meta['ID'].'" target="blank">'.$meta['ID'].'</a>] ';
}
// echo $cmd;
shell_exec($cmd);

echo Message('Unit(s) added to queue: '.$units_list, false);

?>
