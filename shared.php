<?php

// CONST

$GLOBALS['bmRearrTable'] = __DIR__.'/rearrangements_table.tsv';
$GLOBALS['bmWGTable'] = __DIR__.'/whole_genome_regions.txt';
$GLOBALS['bmPipelineScript'] = __DIR__.'/benchmark_pipeline.py';
$GLOBALS['bmPipelineScriptWG'] = __DIR__.'/benchmark_whole_genome_pipeline.py';
$GLOBALS['bmJsonConfig'] = __DIR__.'/config.json';
$GLOBALS['bmCondaEnv'] = __DIR__.'/.pyenv';
$GLOBALS['bmExpDatasets'] = __DIR__.'/exp_datasets';
$GLOBALS['bmUploads'] = __DIR__.'/upload';
$GLOBALS['bmMetrics'] = __DIR__.'/benchmark_db.sqlite3';
$GLOBALS['bmLogs'] = __DIR__.'/logs';
$GLOBALS['bmCool'] = __DIR__.'/cool';
$GLOBALS['bmSubmission'] = 'submission.php';
$GLOBALS['bmHiGlass'] = 'higlass.php';
$GLOBALS['bmMetricsPage'] = 'metrics_view.php';

// GET CONST FUNC
function GetHeader($Header) { return '
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
<title>3DGenBench | Benchmarking Predictive Models of 3D Genome Organization</title>
<meta name="description" content="Compare the performance of predictive models of chromatin organization and understand which chromatin features define 3D-genome architecture in normal and mutated genomes" /> 
<meta name="keywords" content="Hi-C, chromatin, genetics, DNA, 3D, benchmarking, ML, prediction" /> 
<meta name="copyright" lang="en" content="International Nucleome Consortium, European Cooperation in Science and Technology (INC COST)" />
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#da532c">
<meta name="theme-color" content="#ffffff">
<link rel="stylesheet" href="https://purecss.io/css/main.css">
<link rel="stylesheet" href="https://unpkg.com/purecss@2.0.6/build/pure-min.css" integrity="sha384-Uu6IeWbM+gzNVXJcM9XV3SohHtmWE+3VGi496jvgX1jyvDTXfdK+rfZc8C1Aehk5" crossorigin="anonymous">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway:300">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.css" />
<script src="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js"></script>
</head>
<body>

<div id="cookieconsent"></div>
<script>window.cookieconsent.initialise({
    container: document.getElementById("cookieconsent"),
    palette:{
     popup: {background: "#42b8dd"},
     button: {background: "#e0e0e0"},
    },
    revokable: true,
    onStatusChange: function(status) {
     console.log(this.hasConsented() ?
      \'enable cookies\' : \'disable cookies\');
    },
    type:"opt-out",
    "position": "bottom-right",
    "theme": "classic",
    "domain": "http://alena-spn.cytogen.ru/",
    "content": {
      "header": \'Cookies used on the website!\',
      "message": \'This website uses cookies to improve your experience.\',
      "dismiss": \'Got it!\',
      "allow": \'Allow cookies\',
      "deny": \'Decline\',
      "link": \'Learn more\',
      "href": \'https://www.cookiesandyou.com\',
      "close": \'&#x274c;\',
      "policy": \'Cookie Policy\',
      "target": \'_blank\',
      }
   });</script>
<div id="layout" style="width: 100%; height: 100%;">
<div id="menu"><div class="pure-menu"><a class="pure-menu-heading" href="index.php">About</a><ul class="pure-menu-list"><li class=""><a class="pure-menu-link" href="tutorial.php">Tutorial</a></li><li class=""><a class="pure-menu-link" href="datasets.php">Datasets</a></li><li class=""><a class="pure-menu-link" href="submission_form.php">Compute Metrics</a></li><li class=""><a class="pure-menu-link" href="metrics.php">Submissions List</a></li></ul></div></div>
<div><div id="main">'.((basename(__DIR__) == '3DGenBench_sandbox' ? '<div><aside style="background: rgb(202, 60, 60);"><p style="text-align: center;"><b>TEST BRANCH</b></p></aside></div>' : '')).'<div class="header"><h1><span style="display: inline-block; color: rgb(223, 117, 20); font-weight: bold; font-size:118%;">3D</span>GenBench</h1><h2>'.$Header.'</h2></div><div style="max-width: 1200px; min-height: 550px;" class="content">
<style scoped="">
        .button-success,
        .button-error,
        .button-warning,
        .button-secondary {
            color: white;
            border-radius: 4px;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
        }

        .button-success {
            background: rgb(28, 184, 65);
            /* this is a green */
        }

        .button-error {
            background: rgb(202, 60, 60);
            /* this is a maroon */
        }

        .button-warning {
            background: rgb(223, 117, 20);
            /* this is an orange */
        }

        .button-secondary {
            background: rgb(66, 184, 221);
            /* this is a light blue */
        }
    </style>
    
'; }

function GetFooter() { return '</div><div class="footer"><div class="legal pure-g"><div class="pure-u-1 u-sm-1-2"><p class="legal-license">This site is built with ❤️ using Pure v<!-- -->2.0.6<br>Licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License</a>.</p></div><div class="pure-u-1 u-sm-1-2"><ul class="legal-links"><li><a href="https://github.com/regnveig/3DGenBench">GitHub Project</a></li></ul><p class="legal-copyright">2022 - Present INC COST</p></div></div></div></div></div></div></body></html>'; }
function GetWPUser() { return 'guest'; }
function GetCondaActivate() { return $GLOBALS['bmCondaEnv'].'/bin/activate base'; }
function GetBenchmarkPipeline() { return $GLOBALS['bmPipelineScript']; }
function GetBenchmarkPipelineWG() { return $GLOBALS['bmPipelineScriptWG']; }
function GetRearrTable() { return $GLOBALS['bmRearrTable']; }
function GetWGTable() { return $GLOBALS['bmWGTable']; }
function GetMetrics() { return $GLOBALS['bmMetrics']; }
function GetLogs() { return $GLOBALS['bmLogs']; }
function GetCool() { return $GLOBALS['bmCool']; }
function GetSubmissionScript() { return $GLOBALS['bmSubmission']; }
function GetHiGlass() { return $GLOBALS['bmHiGlass']; }
function GetMetricsPage() {  return $GLOBALS['bmMetricsPage']; }

// FUNC

// Error/Success Message

function Message($Message, $IsError) {
	if ($IsError) { return '<html><head><link rel="stylesheet" href="https://purecss.io/css/main.css"></head><body><aside style="background: rgb(202, 60, 60);"><p>'.$Message.'</p></aside></body><html>'; }
	else { return '<html><head><link rel="stylesheet" href="https://purecss.io/css/main.css"></head><body><aside style="background: rgb(28, 184, 65);"><p>'.$Message.'</p></aside></body><html>'; }
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

function GetSamplesWG() {
	$SamplesList = array('0' => '(none)');
	foreach (TsvToArray($GLOBALS['bmWGTable']) as $Row) $SamplesList[$Row['genome_locus_name']] = $Row['genome_locus_name'];
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
