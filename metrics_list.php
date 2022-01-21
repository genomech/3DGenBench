<?php

// SHARED
require_once(__DIR__.'/shared.php');

$AuthorID = htmlspecialchars($_GET['bm_author']);

// GET DB DATA
$SqlQuery = (($AuthorID != '') ? 'SELECT * FROM bm_metrics WHERE "Metadata.Author"="'.$AuthorID.'";' : 'SELECT * FROM bm_metrics;');
$DBResponse = DBSelect($SqlQuery);

// MAKE TABLE ARRAY
$TableArray = array(); 
while ($Row = $DBResponse->fetch()) {
	array_push($TableArray, array(
		'ID' => $Row['ID'],
		'Status' => intval($Row['Status']),
		'Author' => $Row['Metadata.Author'],
		'ModelName' => $Row['Metadata.ModelName'],
		'SampleName' => $Row['Metadata.SampleName'],
		'Resolution' => intval($Row['Metadata.Resolution']),
		'SubmissionDate' => $Row['Metadata.SubmissionDate']
		)); }

// HTML + JS

// Tabulator load (http://tabulator.info/)
echo '
<link href="https://unpkg.com/tabulator-tables/dist/css/tabulator.min.css" rel="stylesheet">
<script type="text/javascript" src="https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js"></script>
<script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>
';

// Make Table
echo '
<div id="metrics-table"></div>

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
			return (status == 0) ? "<a href=\'/index.php/'.GetMetricsPage().'?id=" + cell.getValue() + "\'>" + cell.getValue() + "</a>" : cell.getValue(); 
			} 
		},
		{"title": "Status", "field": "Status", formatter: "traffic", formatterParams: { min: 0, max: 2, color: ["green", "orange", "red"] } },
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
<div>
<span style="background-color: red; display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span> Failed 
<span style="background-color: orange; display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span> Processing 
<span style="background-color: green; display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span> Success 
</div>
';

?> 
 
 
