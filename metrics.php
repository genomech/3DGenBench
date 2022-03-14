<?php

// SHARED
require_once('shared.php');

$AuthorID = htmlspecialchars($_GET['bm_author']);

// GET DB DATA
$DBPairedMetrics = DBSelect((($AuthorID != '') ? 'SELECT * FROM bm_metrics WHERE "Metadata.Author"="'.$AuthorID.'";' : 'SELECT * FROM bm_metrics;'));
$DBSingleMetrics = DBSelect((($AuthorID != '') ? 'SELECT * FROM bm_metrics_wg WHERE "Metadata.Author"="'.$AuthorID.'";' : 'SELECT * FROM bm_metrics_wg;'));

// MAKE TABLE ARRAY
$TableArray = array(); 
while ($Row = $DBPairedMetrics->fetch()) {
	array_push($TableArray, array(
		'ID' => $Row['ID'],
		'Status' => intval($Row['Status']),
		'Type' => 'Paired',
		'Author' => $Row['Metadata.Author'],
		'ModelName' => $Row['Metadata.ModelName'],
		'SampleName' => $Row['Metadata.SampleName'],
		'Resolution' => intval($Row['Metadata.Resolution']),
		'SubmissionDate' => $Row['Metadata.SubmissionDate']
		)); }
while ($Row = $DBSingleMetrics->fetch()) {
	array_push($TableArray, array(
		'ID' => $Row['ID'],
		'Status' => intval($Row['Status']),
		'Type' => 'Single',
		'Author' => $Row['Metadata.Author'],
		'ModelName' => $Row['Metadata.ModelName'],
		'SampleName' => $Row['Metadata.SampleName'],
		'Resolution' => intval($Row['Metadata.Resolution']),
		'SubmissionDate' => $Row['Metadata.SubmissionDate']
		)); }

// HTML + JS

echo GetHeader('Submissions List');

// Tabulator load (http://tabulator.info/)
echo '
<link href="https://unpkg.com/tabulator-tables/dist/css/tabulator.min.css" rel="stylesheet">
<script type="text/javascript" src="https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js"></script>
<script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>
';

// Make Table
echo '
<div style="padding: 30px 0 0 0;">
<div id="metrics-table"></div>
</div>
<script>
var tabledata = '.json_encode($TableArray).';
var table = new Tabulator("#metrics-table", {
	placeholder: "No data available!",
	height: "488px",
	layout: "fitColumns",
	data: tabledata,
	pagination: "local",
	clipboard: true,
	initialSort: [
        {column: "SubmissionDate", dir: "desc"}
    ],
	columns: [
		{"title": "ID", "field": "ID", formatter:function(cell, formatterParams) {
			var status = cell.getRow().getCell("Status").getValue();
			return (status == 0) ? "<a href=\''.GetMetricsPage().'?id=" + cell.getValue() + "\'>" + cell.getValue() + "</a>" : cell.getValue(); 
			} 
		},
		{"title": "Status", "field": "Status", formatter: "traffic", formatterParams: { min: 0, max: 2, color: ["green", "orange", "red"] } },
		{"title": "Type", "field": "Type"},
		{"title": "Author", "field": "Author"},
		{"title": "Model Name", "field": "ModelName" },
		{"title": "Sample Name", "field": "SampleName" },
		{"title": "Resolution", "field": "Resolution", formatter:function(cell, formatterParams) { return Math.round(Number(cell.getValue()) / 1000).toString() + " kb"; } },
		{"title": "Submission Date", "field": "SubmissionDate"}
		],
});
</script>
';

// Add Legend
echo '
<div style="padding: 10px 0 0 0;">
<span style="background-color: red; display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span> Failed 
<span style="background-color: orange; display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span> Processing 
<span style="background-color: green; display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span> Success 
</div>
';
echo GetFooter();
?> 
 
 
