<?php

require_once(__DIR__.'/shared.php');

// Get ID
$UnitID = htmlspecialchars($_GET['id']);

// GET DATA FROM DB


$DBPairedData = DBSelect('SELECT * FROM bm_metrics WHERE ID="'.$UnitID.'";');
$DBSingleData = DBSelect('SELECT * FROM bm_metrics_wg WHERE ID="'.$UnitID.'";');

// PARSE DATA
$TableArray = array();
while ($Row = $DBPairedData->fetch()) { $Row['Metadata.Type'] = 'p'; array_push($TableArray, $Row); }
while ($Row = $DBSingleData->fetch()) { $Row['Metadata.Type'] = 's'; array_push($TableArray, $Row); }

// CHECK EXISTENCE AND UNIQUE
$RecordExists = count($TableArray);
if ($RecordExists) $RecordStatus = $TableArray[0]['Status']; else $RecordStatus = -1;
if (count($TableArray) > 1) die(Message('Database error: "'.$UnitID.'" is duplicated', true));
// print_r($TableArray);
$DataType = $TableArray[0]['Metadata.Type'];

if ($DataType == 'p') { 
	$DataArray = TsvToArray(GetRearrTable());
	foreach ($DataArray as $DataRow) { if ($DataRow['rearrangement_ID'] == $TableArray[0]['Metadata.SampleName']) { $LocusAssembly = $DataRow['genome_assembly']; $LocusChr = $DataRow['chr']; $LocusStart = $DataRow['start_prediction']; $LocusEnd = $DataRow['end_prediction']; } }
}
if ($DataType == 's') {
	$DataArray = TsvToArray(GetWGTable());
	foreach ($DataArray as $DataRow) { if ($DataRow['genome_locus_name'] == $TableArray[0]['Metadata.SampleName']) { $LocusAssembly = $DataRow['genome_assembly']; $LocusChr = $DataRow['locus_chr']; $LocusStart = $DataRow['locus_start']; $LocusEnd = $DataRow['locus_end']; } }
}

// print_r($DataArray);

// LOAD metrics_list.php IF THERE ARE NO RESULTS
if ((!$RecordExists) or ($RecordStatus != 0) or (!in_array($DataType, array('p', 's')))) { include(__DIR__.'/metrics_list.php'); }

// RENDER RESULTS
else {
	
	$Record = $TableArray[0];
	
	if ($Record['Metadata.Type'] == 'p') {
	
		// draw random
		
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
					width: 1000,
					height: 500,
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
					width: 500,
					height: 500,
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
		
		// draw page
		
		echo '<script src="https://www.gstatic.com/charts/loader.js"></script>
		
		<script type="text/javascript">
		google.charts.load("current", { packages: ["corechart", "scatter"]});
		</script>
		
		<div style="display: inline-block; width: 50%;">
		
		<h2>Unit Data</h2>
		
		<table>
		<tr><td><b>Author ID:</b></td><td>'.$Record['Metadata.Author'].'</td></tr>
		<tr><td><b>Model Name:</b></td><td>'.$Record['Metadata.ModelName'].'</td></tr>
		<tr><td><b>Resolution:</b></td><td>'.strval(intval($Record['Metadata.Resolution'] / 1000)).' kb</td></tr>
		<tr><td><b>Submission Date:</b></td><td>'.date('d M Y, H:i:s', strtotime($Record['Metadata.SubmissionDate'])).'</td></tr>
		</table>
		
		</div>
		
		<div style="display: inline-block; width: 50%;">
		<h2>Sample Data</h2>
		<table>
		<tr><td><b>Sample Name:</b></td><td>'.$Record['Metadata.SampleName'].'</td></tr>
		<tr><td><b>Coordinates ['.$LocusAssembly.']:</b></td><td>'.$LocusChr.':'.number_format($LocusStart).'-'.number_format($LocusEnd).'</td></tr>
		</table>
		</div>
		
		<h2>Metrics</h2>
		
		<table>
		<tr><th>&nbsp;</th><th>WT</th><th>Mut</th></tr>
		<tr><td><b>Pearson:</b></td><td>'.$Record['Metrics.Pearson.WT'].'</td><td>'.$Record['Metrics.Pearson.MUT'].'</td></tr>
		<tr><td><b>SCC:</b></td><td>'.$Record['Metrics.SCC.WT'].'</td><td>'.$Record['Metrics.SCC.MUT'].'</td></tr>
		<tr><td><b>Insulation Score Pearson:</b></td><td>'.$Record['Metrics.InsulationScorePearson.WT'].'</td><td>'.$Record['Metrics.InsulationScorePearson.MUT'].'</td></tr>
		</table>
		
		<h2>Metrics</h2>
		
		<table>
		<tr><td><b>Insulation Score Mut/Wt Pearson:</b></td><td>'.$Record['Metrics.InsulationScoreMutVsWtPearson'].'</td></tr>
		</table>';

		DrawPR(array(
			'AUC' => $Record['Metrics.EctopicInteractions.AUC'],
			'Precision' => $Record['Metrics.EctopicInteractions.Precision'],
			'Recall' => $Record['Metrics.EctopicInteractions.Recall']
			), 'EctopicInteractions', 'Ectopic Interactions');
		
		DrawPR(array(
			'AUC' => $Record['Metrics.EctopicInsulation.AUC'],
			'Precision' => $Record['Metrics.EctopicInsulation.Precision'],
			'Recall' => $Record['Metrics.EctopicInsulation.Recall']
			), 'EctopicInsulationPR', 'Ectopic Insulation PR');
		DrawRandom(array(
			'Random' => $Record['Metrics.RandomInteractions.Random'],
			'Real' => $Record['Metrics.RandomInteractions.Real']
			), 'RandomInteractions', 'Random Interactions'); 
		
		echo '<iframe width="1200" height="600" src="'.GetHiGlass().'?id='.$UnitID.'&type=p"></iframe>';
	}
	
	if ($Record['Metadata.Type'] == 's') {
		
		$MetricsData = json_decode($Record['Data.JSON'], true);
		
		echo '<script src="https://www.gstatic.com/charts/loader.js"></script>
		
		<script type="text/javascript">
		google.charts.load("current", { packages: ["corechart", "scatter"]});
		</script>
		
		<div style="display: inline-block; width: 50%;">
		
		<h2>Unit Data</h2>
		
		<table>
		<tr><td><b>Author ID:</b></td><td>'.$Record['Metadata.Author'].'</td></tr>
		<tr><td><b>Model Name:</b></td><td>'.$Record['Metadata.ModelName'].'</td></tr>
		<tr><td><b>Resolution:</b></td><td>'.strval(intval($Record['Metadata.Resolution'] / 1000)).' kb</td></tr>
		<tr><td><b>Submission Date:</b></td><td>'.date('d M Y, H:i:s', strtotime($Record['Metadata.SubmissionDate'])).'</td></tr>
		</table>
		
		</div>
		
		<div style="display: inline-block; width: 50%;">
		<h2>Sample Data</h2>
		<table>
		<tr><td><b>Sample Name:</b></td><td>'.$Record['Metadata.SampleName'].'</td></tr>
		<tr><td><b>Coordinates ['.$LocusAssembly.']:</b></td><td>'.$LocusChr.':'.number_format($LocusStart).'-'.number_format($LocusEnd).'</td></tr>
		</table>
		</div>
		
		<h2>Metrics</h2>
		
		<table>
		<tr><td><b>Pearson:</b></td><td>'.$MetricsData['Metrics.Pearson'].'</td></tr>
		<tr><td><b>SCC:</b></td><td>'.$MetricsData['Metrics.SCC'].'</td></tr>
		<tr><td><b>Insulation Score Pearson:</b></td><td>'.$MetricsData['Metrics.InsulationScorePearson'].'</td></tr>
		<tr><td><b>Compartment Strength Pearson:</b></td><td>'.$MetricsData['Metrics.CompartmentStrengthPearson'].'</td></tr>
		<tr><td><b>Ps Pearson:</b></td><td>'.$MetricsData['Metrics.PsPearson'].'</td></tr>
		</table>';
	
	echo '<iframe width="1200" height="600" src="'.GetHiGlass().'?id='.$UnitID.'&type=s"></iframe>';
	}

}
?> 
 
