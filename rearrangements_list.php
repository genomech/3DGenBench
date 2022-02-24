<?php

require(__DIR__.'/shared.php');

// Get Data
$DataArray = TsvToArray(GetRearrTable());
$DataArrayWG = TsvToArray(GetWGTable());

// Tabulator load (http://tabulator.info/)
echo '
<link href="https://unpkg.com/tabulator-tables/dist/css/tabulator.min.css" rel="stylesheet">
<script type="text/javascript" src="https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js"></script>
<script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>
';

// Draw table
echo '

<div id="tabs">
<h2>Rearrangements Dataset (Paired)</h2>
  <div id="fragment-1">
    <div>
Download:
<a href="#" id="download-csv">CSV</a> | <a href="#" id="download-json">JSON</a> | <a href="#" id="download-xlsx">XLSX</a>
</div>
<div id="rearr-table"></div>
  </div>
  <br><br>
  <h2>Genome Regions Dataset (Single)</h2>
  <div id="fragment-2">
    <div>
Download:
<a href="#" id="download-csv-wg">CSV</a> | <a href="#" id="download-json-wg">JSON</a> | <a href="#" id="download-xlsx-wg">XLSX</a>
</div>
<div id="wg-table"></div>
  </div>
</div>
 
<script>

function DownloadFormatter() { return { "formatter": function(cell, formatterParams) { return (cell.getValue() != "_") ? "<a href=\'" + cell.getValue() + "\' target=\'blank\'>Download</a>" : "—"; } } }
function ExploreFormatter() { return { "formatter": function(cell, formatterParams) { return (cell.getValue() != "_") ? "<a href=\'" + cell.getValue() + "\' target=\'blank\'>Explore</a>" : "—"; } } }
function ExploreWGFormatter() { return { "formatter": function(cell, formatterParams) { return (cell.getValue() != "_") ? "<a href=\'http://alena-spn.cytogen.ru:8230" + cell.getValue().slice(8) + "\' target=\'blank\'>Explore</a>" : "—"; } } }
function CoordFormatter() { return { "width": 150, "hozAlign": "right", "formatter": function(cell, formatterParams) { return (!isNaN(parseInt(cell.getValue()))) ? parseInt(cell.getValue()).toLocaleString("en") : "—"; } } }
function IDFormatter() { return { "formatter": function(cell, formatterParams) { return "<span style=\'font-weight:bold;\'>" + cell.getValue() + "</span>"; } } }

var tabledata = '.json_encode($DataArray).';
var wgdata = '.json_encode($DataArrayWG).';

var table = new Tabulator("#rearr-table", {
	placeholder: "No data available!",
	height: "300px",
	data: tabledata,
	pagination: "local",
	clipboard: true,
	initialSort: [
        {column: "rearrangement_ID", dir: "asc"}
    ],
	columns: [
		{ ...{ "title": "ID", "field": "rearrangement_ID" }, ...IDFormatter() },
		{"title": "Rearrangement Type", "field": "rearrangement_type"},
		{"title": "Cell Type", "field": "cell_type"},
		{ ...{ "title": "WT Archived Data", "field": "capture_WT_data_archive" }, ...DownloadFormatter() },
		{ ...{ "title": "MUT Archived Data", "field": "capture_Mut_data_archive" }, ...DownloadFormatter() },
		{ ...{ "title": "WT FTP Folder", "field": "capture_WT_data" }, ...ExploreFormatter() },
		{ ...{ "title": "MUT FTP Folder", "field": "capture_Mut_data" }, ...ExploreFormatter() },
		{"title": "Citation", "field": "cite"},
		{"title": "Genome Assembly", "field": "genome_assembly"},
		{"title": "Chrom", "field": "chr"},
		{ ...{ "title": "Prediction Start", "field": "start_prediction" }, ...CoordFormatter() },
		{ ...{ "title": "Prediction End", "field": "end_prediction" }, ...CoordFormatter() },
		{ ...{ "title": "Capture Start", "field": "start_capture" }, ...CoordFormatter() },
		{ ...{ "title": "Capture End", "field": "end_capture" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #1 Start", "field": "start1" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #1 End", "field": "end1" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #2 Start", "field": "start2" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #2 End", "field": "end2" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #3 Start", "field": "start3" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #3 End", "field": "end3" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #4 Start", "field": "start4" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #4 End", "field": "end4" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #5 Start", "field": "start5" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #5 End", "field": "end5" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #6 Start", "field": "start6" }, ...CoordFormatter() },
		{ ...{ "title": "Rearr #6 End", "field": "end6" }, ...CoordFormatter() },
		{ ...{ "title": "CTCF Data", "field": "CTCF_data" }, ...DownloadFormatter() }
	],
});

var wgtable = new Tabulator("#wg-table", {
	placeholder: "No data available!",
	height: "300px",
	data: wgdata,
	pagination: "local",
	clipboard: true,
	layout: "fitColumns",
	initialSort: [
        {column: "genome_locus_name", dir: "asc"}
    ],
	columns: [
		{ ...{ "title": "ID", "field": "genome_locus_name" }, ...IDFormatter() },
		{"title": "Cell Type", "field": "cell_type"},
		{ ...{ "title": "FTP Folder", "field": "path_to_processed_hic_data" }, ...ExploreWGFormatter() },
		{"title": "Genome Assembly", "field": "genome_assembly"},
		{"title": "Chrom", "field": "locus_chr"},
		{ ...{ "title": "Prediction Start", "field": "locus_start" }, ...CoordFormatter() },
		{ ...{ "title": "Prediction End", "field": "locus_end" }, ...CoordFormatter() }
	],
});

document.getElementById("download-csv").addEventListener("click", function() { table.download("csv", "rearrangements_table.csv"); });
document.getElementById("download-json").addEventListener("click", function() { table.download("json", "rearrangements_table.json"); });
document.getElementById("download-xlsx").addEventListener("click", function() { table.download("xlsx", "rearrangements_table.xlsx", {sheetName:"My Data"}); });

document.getElementById("download-csv-wg").addEventListener("click", function() { wgtable.download("csv", "wg_table.csv"); });
document.getElementById("download-json-wg").addEventListener("click", function() { wgtable.download("json", "wg_table.json"); });
document.getElementById("download-xlsx-wg").addEventListener("click", function() { wgtable.download("xlsx", "wg_table.xlsx", {sheetName:"My Data"}); });
</script>
';

?>
