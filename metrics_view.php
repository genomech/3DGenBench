<?php

require_once('shared.php');

// Get ID
$UnitID = htmlspecialchars($_GET['id']);

// GET DATA FROM DB

$DBPairedData = DBSelect('SELECT * FROM bm_metrics WHERE ID="'.$UnitID.'";');
$DBInsOnlyPairedData = DBSelect('SELECT * FROM bm_metrics_insp WHERE ID="'.$UnitID.'";');
$DBSingleData = DBSelect('SELECT * FROM bm_metrics_wg WHERE ID="'.$UnitID.'";');

// PARSE DATA
$TableArray = array();
while ($Row = $DBPairedData->fetch()) { $Row['Metadata.Type'] = 'p'; array_push($TableArray, $Row); }
while ($Row = $DBInsOnlyPairedData->fetch()) { $Row['Metadata.Type'] = 'insp'; array_push($TableArray, $Row); }
while ($Row = $DBSingleData->fetch()) { $Row['Metadata.Type'] = 's'; array_push($TableArray, $Row); }

// CHECK EXISTENCE AND UNIQUE
$RecordExists = count($TableArray);
if ($RecordExists) $RecordStatus = $TableArray[0]['Status']; else $RecordStatus = -1;
if (count($TableArray) > 1) die(Message('Database error: "'.$UnitID.'" is duplicated', true));
// print_r($TableArray);
$DataType = $TableArray[0]['Metadata.Type'];

if (($DataType == 'p') or ($DataType == 'insp')) { 
	$DataArray = TsvToArray(GetRearrTable());
	foreach ($DataArray as $DataRow) { if ($DataRow['rearrangement_ID'] == $TableArray[0]['Metadata.SampleName']) { $LocusAssembly = $DataRow['genome_assembly']; $LocusChr = $DataRow['chr']; $LocusStart = $DataRow['start_prediction']; $LocusEnd = $DataRow['end_prediction']; } }
}
if ($DataType == 's') {
	$DataArray = TsvToArray(GetWGTable());
	foreach ($DataArray as $DataRow) { if ($DataRow['genome_locus_name'] == $TableArray[0]['Metadata.SampleName']) { $LocusAssembly = $DataRow['genome_assembly']; $LocusChr = $DataRow['locus_chr']; $LocusStart = $DataRow['locus_start']; $LocusEnd = $DataRow['locus_end']; } }
}

// print_r($DataArray);

// LOAD metrics_list.php IF THERE ARE NO RESULTS
if ((!$RecordExists) or (!in_array($DataType, array('p', 's', 'insp')))) { include(__DIR__.'/metrics.php'); }
if ($RecordStatus != 0) { 
	echo GetHeader('ID: '.$UnitID);
	$Record = $TableArray[0];
	$Logs = file_get_contents(GetLogs().'/'.$UnitID.'.log');
	echo '<h2>Unit Data</h2>
		
		<table class="pure-table pure-table-bordered pure-table-striped">
		<tr><td style="width: 400px;"><b>Author ID:</b></td><td style="width: 800px;">'.$Record['Metadata.Author'].'</td></tr>
		<tr><td><b>Model Name:</b></td><td>'.$Record['Metadata.ModelName'].'</td></tr>
		<tr><td><b>Resolution:</b></td><td>'.strval(intval($Record['Metadata.Resolution'] / 1000)).' kb</td></tr>
		<tr><td><b>Submission Date:</b></td><td>'.date('d M Y, H:i:s', strtotime($Record['Metadata.SubmissionDate'])).'</td></tr>
		</table>
		
		<h2>Sample Data</h2>
		<table class="pure-table pure-table-bordered pure-table-striped">
		<tr><td style="width: 400px;"><b>Sample Name:</b></td><td style="width: 800px;">'.$Record['Metadata.SampleName'].'</td></tr>
		<tr><td><b>Coordinates ['.$LocusAssembly.']:</b></td><td>'.$LocusChr.':'.number_format($LocusStart).'-'.number_format($LocusEnd).'</td></tr>
		</table><h2>Processing Info</h2>';
		
		if ($RecordStatus == 2) { echo '<aside class="button-error"><p>Job failed. See logs below and contact us</p></aside><details><summary>Click here to see logs</summary><div class="code code-wrap"><pre id="logpre" style="display: block; height: 500px; overflow-x: auto; padding: 0.5em; color: rgb(0, 0, 0); background: rgb(248, 248, 255) none repeat scroll 0% 0%;"><code class="language-html" style="white-space: pre;">'.$Logs.'</code></pre></div></details>
		'; }
		
		if (($RecordStatus == 1) or ($RecordStatus == 3)) { 
		if ($RecordStatus == 1) { echo '<aside class="button-warning"><p>Job is processing now. This page will reload in <span id="cnt">10</span> sec</p></aside>'; }
		if ($RecordStatus == 3) { echo '<aside class="button-secondary"><p>Job is queued. This page will reload in <span id="cnt">10</span> sec</p></aside>'; }
		echo '<script>
    var counter = 10;

    // The countdown method.
    window.setInterval(function () {
        counter--;
        if (counter >= 0) {
            var span;
            span = document.getElementById("cnt");
            span.innerHTML = counter;
        }
        if (counter === 0) {
            clearInterval(counter);
        }

    }, 1000);

    window.setInterval("refresh()", 10000);

    // Refresh or reload page.
    function refresh() {
        window  .location.reload();
    }
</script>'; }
	echo GetFooter();
}

// RENDER RESULTS
else {

function DrawRandom($Data, $Name, $Caption) {
			$Random = json_decode($Data['Random'], true);
			$NewRandom = array();
			foreach ($Random as $Index => $Value) array_push($NewRandom, array($Value, intval($Data['Real'])));
			$NewRandom = json_encode($NewRandom);
			$Buckets = 40;
			
			echo '
			<script type="text/javascript">
			
			function draw'.$Name.'() {
				var data = new google.visualization.DataTable();
				data.addColumn("number", "Random");
				data.addColumn("number", "Real");
				data.addRows('.$NewRandom.');
				var options = {
					width: 550,
					height: 550,
					title: "'.$Caption.'",
					histogram: { minNumBuckets: '.$Buckets.', maxNumBuckets: '.$Buckets.' },
					vAxis: { title: "Count" },
					hAxis: { title: "" },
					legend: { position: "bottom" },
					lineWidth: 4,
					pointSize: 0
				};
				var chart = new google.visualization.Histogram(document.getElementById("obj'.$Name.'"));
				chart.draw(data, options);
			}
			
			google.charts.setOnLoadCallback(draw'.$Name.');
			
			</script>
			
			<div style="display: inline-block;" id="obj'.$Name.'"></div>
			';
			
		}
		
		// draw PR curve
		
		function DrawPR($PRData, $Name, $Caption) {
			$AUC = $PRData['AUC'];
			$Precision = json_decode($PRData['Precision'], true);
			$Recall = json_decode($PRData['Recall'], true);
			$DataArray = array();
			for ($Index = 0; $Index < count($Precision); $Index++) array_push($DataArray, array($Recall[$Index], $Precision[$Index]));
			
			echo '
			<script type="text/javascript">
			
			function draw'.$Name.'() {
				var data = new google.visualization.DataTable();
				data.addColumn("number", "Recall");
				data.addColumn("number", "Precision");
				data.addRows('.json_encode($DataArray).');
				var options = {
					width: 550,
					height: 550,
					title: "'.$Caption.'\\nAUC: '.number_format($AUC, 7, '.', '').'",
					vAxis: { title: "Precision", minValue: 0, maxValue: 1 },
					hAxis: { title: "Recall", minValue: 0, maxValue: 1 },
					legend: { position: "none" },
					lineWidth: 4,
					pointSize: 0
					};
				var chart = new google.visualization.ScatterChart(document.getElementById("obj'.$Name.'"));
				chart.draw(data, google.charts.Scatter.convertOptions(options));
				}
			
			google.charts.setOnLoadCallback(draw'.$Name.');
			
			</script>
			
			<div style="display: inline-block;" id="obj'.$Name.'"></div>
			';
		}
		
		function DrawBaseline($BaselineData, $ModelValue, $Name, $Caption) {
			
			echo '
			
			<script type="text/javascript">
			function draw'.$Name.'() {
  var data = google.visualization.arrayToDataTable([
     ["Baseline", "Value", "Model"],';
foreach ($BaselineData as $key => $value) { echo '["'.$key.'", '.($value ? $value : 0).', '.$ModelValue.'],'; }
  echo ']);

  var chartDiv = document.getElementById("obj'.$Name.'");
  var chart = new google.visualization.ColumnChart(chartDiv);

  // use colors to find chart elements
  var colorMagenta = "#dc3912";
  var colorLime = "#3366cc";

  var xBeg;    // save first x coord
  var xWidth;  // save width of column

  var rowIndex = -1;  // find first column

  google.visualization.events.addListener(chart, "ready", function () {
    // columns
    Array.prototype.forEach.call(chartDiv.getElementsByTagName("rect"), function(rect, index) {
      if (rect.getAttribute("fill") === colorLime) {
        rowIndex++;
        xWidth = parseFloat(rect.getAttribute("width")) / 2;
        if (rowIndex === 0) {
          xBeg = parseFloat(rect.getAttribute("x"));
        }
      }
    });

    // reference line
    Array.prototype.forEach.call(chartDiv.getElementsByTagName("path"), function(path, index) {
      if (path.getAttribute("stroke") === colorMagenta) {
        // change line coords
        var refCoords = path.getAttribute("d").split(",");
        refCoords[0] = "M" + xBeg;
        var refWidth = refCoords[2].split("L");
        refWidth[1] = parseFloat(refWidth[1]) + xWidth;
        refCoords[2] = refWidth.join("L");
        path.setAttribute("d", refCoords.join(","));
      }
    });
  });

  chart.draw(data, {
    colors: [colorLime, colorMagenta],
    width: 550,
					height: 550,
    legend: "none",
    vAxis: { title: "Value", viewWindow: {
        min: 0,
        max: 1
      } },
    series: {
      1: {
        type: "line"
      }
    },
    title: "'.$Caption.'\nModel Value: '.$ModelValue.'"
  });
}
google.charts.setOnLoadCallback(draw'.$Name.');
			
			</script>

<div style="display: inline-block;" id="obj'.$Name.'"></div>
			
			';
		}
		
	$Record = $TableArray[0];
	
	$PairedBaseline = TsvToArray(__DIR__.'/rearr_benchmark_baseline.txt');
	$FilteredPaired = array();
	foreach ($PairedBaseline as $value) { 
		if (($value['resolution'] == $Record['Metadata.Resolution']) and ($value['sample'] == $Record['Metadata.SampleName'])) {
			$metric = $value['metric'];
			unset($value['resolution'], $value['sample'], $value['metric']);
			$FilteredPaired[$metric] = $value;
		}
	}
	
	$SingleBaseline = TsvToArray(__DIR__.'/wg_benchmark_baseline.txt');
	$FilteredSingle = array();
	foreach ($SingleBaseline as $value) { 
		if (($value['resolution'] == $Record['Metadata.Resolution']) and ($value['sample'] == $Record['Metadata.SampleName'])) {
			$metric = $value['metric'];
			unset($value['resolution'], $value['sample'], $value['metric']);
			$FilteredSingle[$metric] = $value;
		}
	}
	
	echo GetHeader('ID: '.$UnitID);
	
	if ($Record['Metadata.Type'] == 'insp') {
		
		
		
		// draw page
		
		echo '
		
		<script type="text/javascript">
		google.charts.load("current", { packages: ["corechart", "scatter"]});
		</script>
		
		<h2>Unit Data</h2>
		
		<table class="pure-table pure-table-bordered pure-table-striped">
		<tr><td style="width: 400px;"><b>Author ID:</b></td><td style="width: 800px;">'.$Record['Metadata.Author'].'</td></tr>
		<tr><td><b>Model Name:</b></td><td>'.$Record['Metadata.ModelName'].'</td></tr>
		<tr><td><b>Resolution:</b></td><td>'.strval(intval($Record['Metadata.Resolution'] / 1000)).' kb</td></tr>
		<tr><td><b>Submission Date:</b></td><td>'.date('d M Y, H:i:s', strtotime($Record['Metadata.SubmissionDate'])).'</td></tr>
		</table>
		
		<h2>Sample Data</h2>
		<table class="pure-table pure-table-bordered pure-table-striped">
		<tr><td style="width: 400px;"><b>Sample Name:</b></td><td style="width: 800px;">'.$Record['Metadata.SampleName'].'</td></tr>
		<tr><td><b>Coordinates ['.$LocusAssembly.']:</b></td><td>'.$LocusChr.':'.number_format($LocusStart).'-'.number_format($LocusEnd).'</td></tr>
		</table>
		
		<h2>Metrics</h2>';
		echo '<h3>Insulation Score Spearman</h3>';
		DrawBaseline($FilteredPaired['Wt Insulation Score Spearman'], $Record['Metrics.InsulationScorePearson.WT'], "InsulationScorePearsonWT", "Insulation Score Spearman WT");
		DrawBaseline($FilteredPaired['Mut Insulation Score Spearman'], $Record['Metrics.InsulationScorePearson.MUT'], "InsulationScorePearsonMUT", "Insulation Score Spearman MUT");
		echo '<h3>Insulation Score Mut/Wt Spearman</h3>';
		DrawBaseline($FilteredPaired['Insulation Score Mut/Wt Spearman'], $Record['Metrics.InsulationScoreMutVsWtPearson'], "InsulationScoreMutVsWtPearson", "Insulation Score Mut/Wt Spearman");
		echo '<h2>HiGlass View</h2><iframe width="1200" height="600" frameBorder="0" scrolling="no" margin="0" src="'.GetHiGlass().'?id='.$UnitID.'&type=insp&pos='.$LocusStart.'&end='.$LocusEnd.'"></iframe>';
	}
	
	if ($Record['Metadata.Type'] == 'p') {
		
		
		
		// draw page
		
		echo '
		
		<script type="text/javascript">
		google.charts.load("current", { packages: ["corechart", "scatter"]});
		</script>
		
		<h2>Unit Data</h2>
		
		<table class="pure-table pure-table-bordered pure-table-striped">
		<tr><td style="width: 400px;"><b>Author ID:</b></td><td style="width: 800px;">'.$Record['Metadata.Author'].'</td></tr>
		<tr><td><b>Model Name:</b></td><td>'.$Record['Metadata.ModelName'].'</td></tr>
		<tr><td><b>Resolution:</b></td><td>'.strval(intval($Record['Metadata.Resolution'] / 1000)).' kb</td></tr>
		<tr><td><b>Submission Date:</b></td><td>'.date('d M Y, H:i:s', strtotime($Record['Metadata.SubmissionDate'])).'</td></tr>
		</table>
		
		<h2>Sample Data</h2>
		<table class="pure-table pure-table-bordered pure-table-striped">
		<tr><td style="width: 400px;"><b>Sample Name:</b></td><td style="width: 800px;">'.$Record['Metadata.SampleName'].'</td></tr>
		<tr><td><b>Coordinates ['.$LocusAssembly.']:</b></td><td>'.$LocusChr.':'.number_format($LocusStart).'-'.number_format($LocusEnd).'</td></tr>
		</table>
		
		<h2>Metrics</h2>';
		echo '<h3>All contacts Spearman</h3>';
		DrawBaseline($FilteredPaired['Wt Spearman'], $Record['Metrics.Pearson.WT'], "SpearmanWT", "All contacts Spearman WT");
		DrawBaseline($FilteredPaired['Mut Spearman'], $Record['Metrics.Pearson.MUT'], "SpearmanMUT", "All contacts Spearman MUT");
		echo '<h3>SCC</h3>';
		DrawBaseline($FilteredPaired['Wt SCC'], $Record['Metrics.SCC.WT'], "SCCWT", "SCC WT");
		DrawBaseline($FilteredPaired['Mut SCC'], $Record['Metrics.SCC.MUT'], "SCCMUT", "SCC MUT");
		echo '<h3>Insulation Score Spearman</h3>';
		DrawBaseline($FilteredPaired['Wt Insulation Score Spearman'], $Record['Metrics.InsulationScorePearson.WT'], "InsulationScorePearsonWT", "Insulation Score Spearman WT");
		DrawBaseline($FilteredPaired['Mut Insulation Score Spearman'], $Record['Metrics.InsulationScorePearson.MUT'], "InsulationScorePearsonMUT", "Insulation Score Spearman MUT");
		echo '<h3>Insulation Score Mut/Wt Spearman</h3>';
		DrawBaseline($FilteredPaired['Insulation Score Mut/Wt Spearman'], $Record['Metrics.InsulationScoreMutVsWtPearson'], "InsulationScoreMutVsWtPearson", "Insulation Score Mut/Wt Spearman");
		echo '<h3>Ectopic Interactions</h3>';
		DrawPR(array(
			'AUC' => $Record['Metrics.EctopicInteractions.AUC'],
			'Precision' => $Record['Metrics.EctopicInteractions.Precision'],
			'Recall' => $Record['Metrics.EctopicInteractions.Recall']
			), 'EctopicInteractions', 'Ectopic Interactions');
			echo '<h3>Random Interactions</h3>';
		DrawRandom(array(
			'Random' => $Record['Metrics.RandomInteractions.Random'],
			'Real' => $Record['Metrics.RandomInteractions.Real']
			), 'RandomInteractions', 'Random Interactions'); 
		
		echo '<h2>HiGlass View</h2><iframe width="1200" height="600" frameBorder="0" scrolling="no" margin="0" src="'.GetHiGlass().'?id='.$UnitID.'&type=p&pos='.$LocusStart.'&end='.$LocusEnd.'"></iframe>';
	}
	
	if ($Record['Metadata.Type'] == 's') {
		
		
		
		$MetricsData = json_decode($Record['Data.JSON'], true);
		
		echo '<script src="https://www.gstatic.com/charts/loader.js"></script>
		
		<script type="text/javascript">
		google.charts.load("current", { packages: ["corechart", "scatter"]});
		</script>
		
		<h2>Unit Data</h2>
		
		<table class="pure-table pure-table-bordered pure-table-striped">
		<tr><td style="width: 400px;"><b>Author ID:</b></td><td style="width: 800px;">'.$Record['Metadata.Author'].'</td></tr>
		<tr><td><b>Model Name:</b></td><td>'.$Record['Metadata.ModelName'].'</td></tr>
		<tr><td><b>Resolution:</b></td><td>'.strval(intval($Record['Metadata.Resolution'] / 1000)).' kb</td></tr>
		<tr><td><b>Submission Date:</b></td><td>'.date('d M Y, H:i:s', strtotime($Record['Metadata.SubmissionDate'])).'</td></tr>
		</table>
		
		<h2>Sample Data</h2>
		<table class="pure-table pure-table-bordered pure-table-striped">
		<tr><td style="width: 400px;"><b>Sample Name:</b></td><td style="width: 800px;">'.$Record['Metadata.SampleName'].'</td></tr>
		<tr><td><b>Coordinates ['.$LocusAssembly.']:</b></td><td>'.$LocusChr.':'.number_format($LocusStart).'-'.number_format($LocusEnd).'</td></tr>
		</table>
		
		<h2>Metrics</h2>';
		echo '<h3>All contacts Spearman</h3>';
		DrawBaseline($FilteredSingle['Spearman'], $MetricsData['Metrics.Pearson'], "Spearman", "All contacts Spearman");
		echo '<h3>SCC</h3>';
		DrawBaseline($FilteredSingle['SCC'], $MetricsData['Metrics.SCC'], "SCC", "SCC");
		echo '<h3>Insulation Score Spearman</h3>';
		DrawBaseline($FilteredSingle['Ins_score_Spearman'], $MetricsData['Metrics.InsulationScorePearson'], "InsulationScorePearson", "Insulation Score Spearman");
		echo '<h3>Compartment Strength Spearman</h3>';
		DrawBaseline($FilteredSingle['Comp_strength Spearman'], $MetricsData['Metrics.CompartmentStrengthPearson'], "CompartmentStrengthPearson", "Compartment Strength Spearman");
		echo '<h3>P(s) Spearman</h3>';
		DrawBaseline($FilteredSingle['Ps Spearman'], $MetricsData['Metrics.PsPearson'], "PsPearson", "P(s) Spearman");
		
		echo '</table> <h2>HiGlass View</h2>';
	
	echo '<iframe width="1200" height="600" frameBorder="0" scrolling="no" margin="0" src="'.GetHiGlass().'?id='.$UnitID.'&type=s&pos='.$LocusStart.'&end='.$LocusEnd.'"></iframe>';
	}
echo GetFooter();
}
?> 
 
