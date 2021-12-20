<?php

require(__DIR__.'/shared.php');

// Get ID
$unit_id = htmlspecialchars($_GET['id']);

$dbpath = 'sqlite:'.GetMetrics();
try { $dbh = new PDO($dbpath); } catch(Exception $e) { die(Message('Cannot open the database: '.$e, true)); }
$query = 'SELECT * FROM bm_metrics WHERE ID="'.$unit_id.'";';
$results = $dbh->query($query); $lst = array(); while ($row = $results->fetch()) array_push($lst, $row);
$exists = count($lst);
if ($exists) $status = $lst[0]['Status'];
else $status = -1;
if (count($lst) > 1) die(Message('Database error: "'.$unit_id.'" is duplicated', true));

if ((!$exists) or ($status != 0)) {
	
	$query = 'SELECT * FROM bm_metrics;';
	$results = $dbh->query($query); $lst2 = array(); 
	while ($row = $results->fetch()) {
		array_push($lst2, array(
			'ID' => $row['ID'],
			'Status' => intval($row['Status']),
			'Author' => $row['Metadata.Author'],
			'ModelName' => $row['Metadata.ModelName'],
			'SampleName' => $row['Metadata.SampleName'],
			'Resolution' => intval($row['Metadata.Resolution']),
			'SubmissionDate' => $row['Metadata.SubmissionDate']
			)); }
	
	$data = json_encode($lst2);
	
	echo '
<link href="https://unpkg.com/tabulator-tables/dist/css/tabulator.min.css" rel="stylesheet">
<script type="text/javascript" src="https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js"></script>
<script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>

<div id="example-table"></div>

<div>
<br>
<span style="background-color: red; display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span> Failed 
<span style="background-color: orange; display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span> Processing 
<span style="background-color: green; display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span> Success 
</div>

<script>

var tabledata = '.$data.';

var table = new Tabulator("#example-table", {
	layout:"fitColumns",
	data:tabledata,
	pagination:"local",
	paginationSize:15,
	clipboard:true,
	initialSort: [
        {column: "SubmissionDate", dir: "desc"}
    ],
	columns: [
		{"title": "ID", "field": "ID", formatter:function(cell, formatterParams) { var status = cell.getRow().getCell("Status").getValue(); return (status == 0) ? "<a href=\'/index.php/metrics?id=" + cell.getValue() + "\'>" + cell.getValue() + "</a>" : cell.getValue(); } },
		{"title": "Status", "field": "Status", formatter: "traffic", formatterParams: { min: 0, max: 2, color: ["green", "orange", "red"] } },
		{"title": "Author", "field": "Author"},
		{"title": "Model Name", "field": "ModelName" },
		{"title": "Sample Name", "field": "SampleName" },
		{"title": "Resolution", "field": "Resolution", formatter:function(cell, formatterParams) { return Math.round(Number(cell.getValue()) / 1000).toString() + " kb"; } },
		{"title": "Submission Date", "field": "SubmissionDate"}
		],
});
</script>';
	
} else {

	function DrawRandom($Data, $Name, $Caption) {
		$Random = json_decode($Data["Random"], true);
		$NewRandom = array(); foreach ($Random as $index => $value) { array_push($NewRandom, array($value, intval($Data["Real"]))); }
		$NewRandom = json_encode($NewRandom);
		$Buckets = 40;
		
		echo "<script type=\"text/javascript\">
		function draw".$Name."() {
			var data = new google.visualization.DataTable();
			data.addColumn('number', 'Random');
			data.addColumn('number', 'Real');
			data.addRows(".$NewRandom.");
			var options = {
				width: 1000,
				height: 500,
				title: '".$Caption."',
				histogram: {
					minNumBuckets: ".$Buckets.",
					maxNumBuckets: ".$Buckets."
					},
				vAxis: {
					title: 'Count'
					},
				hAxis: {
					title: ''
					},
				legend: {
					position: 'bottom'
					},
				lineWidth: 4,
				pointSize: 0
				};
			var chart = new google.visualization.Histogram(document.getElementById('obj".$Name."'));
			chart.draw(data, options);
		}
		google.charts.setOnLoadCallback(draw".$Name.");
		</script>
		<div style=\"display: inline-block;\" id=\"obj".$Name."\"></div>";
	}

	function DrawPR($PRData, $Name, $Caption) {
		$AUC = $PRData["AUC"];
		$Precision = json_decode($PRData["Precision"], true);
		$Recall = json_decode($PRData["Recall"], true);
		$Combo = array(); for ($i = 0; $i < count($Precision); $i++) { array_push($Combo, array($Recall[$i], $Precision[$i])); }
		$Combo = json_encode($Combo);
		
		echo "<script type=\"text/javascript\">
		function draw".$Name."() {
			var data = new google.visualization.DataTable();
			data.addColumn('number', 'Recall');
			data.addColumn('number', 'Precision');
			data.addRows(".$Combo.");
			var options = {
				width: 500,
				height: 500,
				title: '".$Caption."\\nAUC: ".number_format($AUC, 7, '.', '')."',
				vAxis: {
					title: 'Precision',
					minValue: 0,
					maxValue: 1 
					},
				hAxis: {
					title: 'Recall',
					minValue: 0,
					maxValue: 1
					},
				legend: {
					position: 'none'
					},
				lineWidth: 4,
				pointSize: 0
				};
			var chart = new google.visualization.ScatterChart(document.getElementById('obj".$Name."'));
			chart.draw(data, google.charts.Scatter.convertOptions(options));
			}
		google.charts.setOnLoadCallback(draw".$Name.");
		</script>
		<div style=\"display: inline-block;\" id=\"obj".$Name."\"></div>";
	}

	echo "<script src=\"https://www.gstatic.com/charts/loader.js\"></script>
	<script type=\"text/javascript\">google.charts.load('current', {packages: ['corechart', 'scatter']});</script>
	<div style=\"display: inline-block; width: 50%; \">
	<h2>Unit Data</h2>
	<table>
	<tr><td><b>Author ID:</b></td><td>".$lst[0]["Metadata.Author"]."</td></tr>
	<tr><td><b>Model Name:</b></td><td>".$lst[0]["Metadata.ModelName"]."</td></tr>
	<tr><td><b>Resolution:</b></td><td>".strval(intval($lst[0]["Metadata.Resolution"] / 1000))." kb</td></tr>
	<tr><td><b>Submission Date:</b></td><td>".date('d M Y, H:i:s', strtotime($lst[0]["Metadata.SubmissionDate"]))."</td></tr>
	</table>
	</div><div style=\"display: inline-block; width: 50%; \">
	<h2>Sample Data</h2>
	<table>
	<tr><td><b>Sample Name:</b></td><td>".$lst[0]["Metadata.SampleName"]."</td></tr>
	</table>
	</div>
	
	<h2>Metrics</h2>
	<table>
	<tr><th>&nbsp;</th><th>WT</th><th>Mut</th></tr>
	<tr><td><b>Pearson:</b></td><td>".$lst[0]["Metrics.Pearson.WT"]."</td><td>".$lst[0]["Metrics.Pearson.MUT"]."</td></tr>
	<tr><td><b>SCC:</b></td><td>".$lst[0]["Metrics.SCC.WT"]."</td><td>".$lst[0]["Metrics.SCC.MUT"]."</td></tr>
	<tr><td><b>Insulation Score Pearson:</b></td><td>".$lst[0]["Metrics.InsulationScorePearson.WT"]."</td><td>".$lst[0]["Metrics.InsulationScorePearson.MUT"]."</td></tr>
	</table>
	
	<h2>Metrics</h2>
	<table>
	<tr><td><b>Insulation Score Mut/Wt Pearson:</b></td><td>".$lst[0]["Metrics.InsulationScoreMutVsWtPearson"]."</td></tr>
	</table>";

	DrawPR(array("AUC" => $lst[0]["Metrics.EctopicInteractions.AUC"], "Precision" => $lst[0]["Metrics.EctopicInteractions.Precision"], "Recall" => $lst[0]["Metrics.EctopicInteractions.Recall"]), "EctopicInteractions", "Ectopic Interactions");
	DrawPR(array("AUC" => $lst[0]["Metrics.EctopicInsulation.AUC"], "Precision" => $lst[0]["Metrics.EctopicInsulation.Precision"], "Recall" => $lst[0]["Metrics.EctopicInsulation.Recall"]), "EctopicInsulationPR", "Ectopic Insulation PR");
	DrawRandom(array("Random" => $lst[0]["Metrics.RandomInteractions.Random"], "Real" => $lst[0]["Metrics.RandomInteractions.Real"]), "RandomInteractions", "Random Interactions"); }

?> 
 
