<?php

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

// CONST

$GLOBALS['bmRearrTable'] = realpath(__DIR__.'/rearrangements_table.tsv');
$GLOBALS['bmWGTable'] = realpath(__DIR__.'/whole_genome_regions.txt');
$GLOBALS['bmPipelineScript'] = realpath(__DIR__.'/benchmark_pipeline.py');
$GLOBALS['bmPipelineScriptWG'] = realpath(__DIR__.'/benchmark_whole_genome_pipeline.py');
$GLOBALS['bmJsonConfig'] = realpath(__DIR__.'/config.json');
$GLOBALS['bmCondaEnv'] = realpath(__DIR__.'/.pyenv');
$GLOBALS['bmExpDatasets'] = realpath(__DIR__.'/exp_datasets');
$GLOBALS['bmUploads'] = realpath(__DIR__.'/upload');
$GLOBALS['bmMetrics'] = realpath(__DIR__.'/benchmark_db.sqlite3');
$GLOBALS['bmLogs'] = realpath(__DIR__.'/logs');
$GLOBALS['bmCool'] = realpath(__DIR__.'/cool');
$GLOBALS['bmPipelineScriptInsOnlyPaired'] = realpath(__DIR__.'/insulatory_score_only_paired_benchmark.py');
$GLOBALS['bmPipelineScriptInsOnlySingle'] = realpath(__DIR__.'/insulatory_score_only_single_benchmark.py');
$GLOBALS['bmSubmission'] = 'submission.php';
$GLOBALS['bmHiGlass'] = 'higlass.php';
$GLOBALS['bmMetricsPage'] = 'metrics_view.php';
$GLOBALS['bmPetTable'] = realpath(__DIR__.'/chia-pet_table.txt');
$GLOBALS['bmPetScript'] = realpath(__DIR__.'/ChIA-PET-benchmark.py');


// GET CONST FUNC
function GetHeader($Header) { 
$Pride = 'ua-flag';
if (date('F') == "June") { $Pride = 'lgbtq-flag'; }
if ((date('F') == "March") and (date('d') == "31")) { $Pride = 'trans-flag'; }
return '
<html lang="en">

<head>

<!--- meta --->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="description" content="Compare the performance of predictive models of chromatin organization and understand which chromatin features define 3D-genome architecture in normal and mutated genomes"> 
<meta name="keywords" content="Hi-C, chromatin, genetics, DNA, 3D, benchmarking, ML, prediction"> 
<meta name="copyright" lang="en" content="International Nucleome Consortium, European Cooperation in Science and Technology (INC COST)">
<!--- meta --->

<title>3DGenBench | Benchmarking Predictive Models of 3D Genome Organization</title>

<!--- favicon --->
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#da532c">
<meta name="theme-color" content="#ffffff">
<!--- favicon --->

<!--- purecss --->
<link rel="stylesheet" href="css/pure-main.css">
<link rel="stylesheet" href="css/pure-min.css">
<link rel="stylesheet" href="css/styles.213c7dc2.css">
<!--- purecss --->

<!--- cookie consent --->
<link rel="stylesheet" type="text/css" href="css/cookieconsent.min.css">
<script src="js/cookieconsent.min.js"></script>
<!--- cookie consent --->

<!--- tabulator and xlsx --->
<link href="css/tabulator.min.css" rel="stylesheet">
<script type="text/javascript" src="js/tabulator.min.js"></script>
<script type="text/javascript" src="js/xlsx.full.min.js"></script>
<!--- tabulator and xlsx --->

<!--- gcharts --->
<script src="https://www.gstatic.com/charts/loader.js"></script>
<!--- gcharts --->

<style>
	.button-success,
	.button-error,
	.button-warning,
	.button-secondary {
		color: white;
		border-radius: 4px;
		text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
	}

	.button-success { background: rgb(28, 184, 65); }
	.button-error { background: rgb(202, 60, 60); }
	.button-warning { background: rgb(223, 117, 20); }
	.button-secondary { background: rgb(66, 184, 221); }

	.ua-flag {
		background: linear-gradient(to bottom, rgba(0, 87, 184, 1) 50%, rgba(254, 221, 0, 1) 50%);
		-webkit-text-stroke: 1px rgba(0, 0, 0, 0.7);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		font-weight: bold;
	}
	
	.lgbtq-flag {
		background: linear-gradient(to right, rgba(255, 0, 24, 0.8) 16%, rgba(255, 165, 44, 0.8) 16% 33%, rgba(255, 255, 65, 0.8) 33% 50%, rgba(0, 128, 24, 0.8) 50% 66%, rgba(0, 0, 249, 0.8) 66% 83%, rgba(134, 0, 125, 0.8) 83%);
		-webkit-text-stroke: 1px rgba(0, 0, 0, 0.7);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		font-weight: bold;
	}

	.trans-flag {
		background: linear-gradient(to right, rgba(85, 205, 252, 0.8) 20%, rgba(247, 168, 184, 0.8) 20% 40%, rgba(255, 255, 255, 0.8) 40% 60%, rgba(247, 168, 184, 0.8) 60% 80%, rgba(85, 205, 252, 0.8) 80%);
		-webkit-text-stroke: 1px rgba(0,0,0,0.7);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		font-weight: bold;
	}
	
	.casual {
		background: black;
		-webkit-text-stroke: 1px rgba(0,0,0,0.7);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		font-weight: bold;
	}
</style>

</head>

<body>

<!--- cookie consent --->
<div id="cookieconsent"></div>
<script>
window.cookieconsent.initialise({
	container: document.getElementById("cookieconsent"),
	palette: {
		popup: { background: "#42b8dd" },
		button: { background: "#e0e0e0" },
	},
	revokable: true,
	onStatusChange: function(status) { console.log(this.hasConsented() ? \'enable cookies\' : \'disable cookies\'); },
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
	});
</script>
<!--- cookie consent --->

<div id="layout" style="width: 100%; height: 100%;">
	
	<!--- menu --->
	<div id="menu">
		<div class="pure-menu">
			<a class="pure-menu-heading" href="index.php">About</a>
			<ul class="pure-menu-list">
				<li class=""><a class="pure-menu-link" href="tutorial.php">Tutorial</a></li>
				<li class=""><a class="pure-menu-link" href="datasets.php">Datasets</a></li>
				<li class=""><a class="pure-menu-link" href="submission_form.php">Compute Metrics</a></li>
				<li class=""><a class="pure-menu-link" href="metrics.php">Submissions List</a></li>
				<li class=""><a class="pure-menu-link" href="upload.php">Upload</a></li>
			</ul>
		</div>
	</div>
	<!--- menu --->
	
	<div>
		<div id="main">
			'.((basename(__DIR__) == '3DGenBench_sandbox' ? '<div><aside style="background: rgb(202, 60, 60);"><p style="text-align: center;"><b>TEST BRANCH</b></p></aside></div>' : '')).'
			
			<!--- header --->
			<div class="header">
			
				<h1><span class="'.$Pride.' pride">3DGenBench</span></h1>
				<h2>'.$Header.'</h2>
				
			</div>
			<!--- header --->
			
			<!--- content --->
			<div style="max-width: 1200px; min-height: 550px;" class="content">
			
<!--- CONTENT BLOCK START --->
'; }

function GetFooter() { return '
<!--- CONTENT BLOCK END --->
			
			</div>
			<!--- content --->
			</div>
			<div class="footer">
				<div class="legal pure-g">
					<div class="pure-u-1 u-sm-1-2">
						<p class="legal-license">
							This site is built using Pure v2.0.6<br>
							We ❤️ Tor as well! Out Onion V3 Address: <a href="http://3dgenbbmw2a4vpn7bprgmyd4hrkq2h63qpjvcav6wnnzygi6po3uzkqd.onion" target="_blank">3dgenbbmw2a4vpn7bprgmyd4hrkq2h63qpjvcav6wnnzygi6po3uzkqd.onion</a><br>
							Licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License</a>.<br>
						</p>
					</div>
					<div class="pure-u-1 u-sm-1-2">
						<ul class="legal-links">
							<li><a href="https://github.com/regnveig/3DGenBench">GitHub Project</a></li>
						</ul>
						<p class="legal-copyright">2022 - Present INC COST</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

</body>
</html>'; }

function GetWPUser() { return 'guest'; }
function GetCondaActivate() { return $GLOBALS['bmCondaEnv'].'/bin/activate base'; }
function GetBenchmarkPipeline() { return $GLOBALS['bmPipelineScript']; }
function GetBenchmarkPipelineWG() { return $GLOBALS['bmPipelineScriptWG']; }
function GetBenchmarkPipelineInsOnlyPaired() { return $GLOBALS['bmPipelineScriptInsOnlyPaired']; }
function GetBenchmarkPipelineInsOnlySingle() { return $GLOBALS['bmPipelineScriptInsOnlySingle']; }
function GetRearrTable() { return $GLOBALS['bmRearrTable']; }
function GetWGTable() { return $GLOBALS['bmWGTable']; }
function GetPetTable() { return $GLOBALS['bmPetTable']; }
function GetMetrics() { return $GLOBALS['bmMetrics']; }
function GetLogs() { return $GLOBALS['bmLogs']; }
function GetCool() { return $GLOBALS['bmCool']; }
function GetSubmissionScript() { return $GLOBALS['bmSubmission']; }
function GetHiGlass() { return $GLOBALS['bmHiGlass']; }
function GetMetricsPage() {  return $GLOBALS['bmMetricsPage']; }
function GetChIAPETBenchmarkPipeline() { return $GLOBALS['bmPetScript']; }
// FUNC

// Error/Success Message

function Message($Message, $IsError) {
	if ($IsError) { return '<html><head><link rel="stylesheet" href="css/pure-main.css"></head><body><aside style="background: rgb(202, 60, 60);"><p>'.$Message.'</p></aside><script src="js/clipboard.min.js"></script> <script>new ClipboardJS(".btn");</script></body><html>'; }
	else { return '<html><head><link rel="stylesheet" href="css/pure-main.css"></head><body><aside style="background: rgb(28, 184, 65);"><p>'.$Message.'</p></aside><script src="js/clipboard.min.js"></script> <script>new ClipboardJS(".btn");</script></body><html>'; }
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

function GetSamplesPET() { 
	$SamplesList = array('0' => '(none)');
	foreach (TsvToArray($GLOBALS['bmPetTable']) as $Row) $SamplesList[$Row['genome_locus_name']] = $Row['genome_locus_name'];
	return $SamplesList;
	}

function GetUploadedFiles($Username) {
	$UserDir = $GLOBALS['bmUploads'].'/'.$Username;
	shell_exec('mkdir -p "'.$UserDir.'"');
	$ScanDir = array();
	foreach (scandir($UserDir) as $File) { if (!is_dir(realpath($UserDir."/".$File))) array_push($ScanDir, $File); }
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
