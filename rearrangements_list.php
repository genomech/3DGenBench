<?php

require(__DIR__.'/shared.php');

$data = json_encode(TsvToArray(__DIR__.'/rearrangements_table.tsv'));

echo '
<link href="https://unpkg.com/tabulator-tables/dist/css/tabulator.min.css" rel="stylesheet">
<script type="text/javascript" src="https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js"></script>
<script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>

<div>
Download:
<a href="#" id="download-csv">CSV</a> | <a href="#" id="download-json">JSON</a> | <a href="#" id="download-xlsx">XLSX</a>
</div>

<div id="example-table"></div>

<script>

var tabledata = '.$data.';

var table = new Tabulator("#example-table", {
	data:tabledata,
	pagination:"local",
	paginationSize:15,
	clipboard:true,
	columns: [
		{"title": "ID", "field": "rearrangement_ID", formatter:function(cell, formatterParams) { return "<span style=\'font-weight:bold;\'>" + cell.getValue() + "</span>"; } },
		{"title": "Rearrangement Type", "field": "rearrangement_type"},
		{"title": "Cell Type", "field": "cell_type"},
		{"title": "WT Archived Data", "field": "capture_WT_data_archive", formatter:"link", formatterParams: { label: "Download", target:"_blank" } },
		{"title": "MUT Archived Data", "field": "capture_Mut_data_archive", formatter:"link", formatterParams: { label: "Download", target:"_blank" } },
		{"title": "WT FTP Folder", "field": "capture_WT_data", formatter:"link", formatterParams: { label: "Explore", target:"_blank" } },
		{"title": "MUT FTP Folder", "field": "capture_Mut_data", formatter:"link", formatterParams: { label: "Explore", target:"_blank" } },
		{"title": "Citation", "field": "cite"},
		{"title": "Genome Assembly", "field": "genome_assembly"},
		{"title": "Chrom", "field": "chr"},
		{"title": "Prediction Start", "field": "start_prediction", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Prediction End", "field": "end_prediction", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Capture Start", "field": "start_capture", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Capture End", "field": "end_capture", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #1 Start", "field": "start1", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #1 End", "field": "end1", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #2 Start", "field": "start2", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #2 End", "field": "end2", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #3 Start", "field": "start3", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #3 End", "field": "end3", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #4 Start", "field": "start4", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #4 End", "field": "end4", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #5 Start", "field": "start5", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #5 End", "field": "end5", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #6 Start", "field": "start6", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "Rearr #6 End", "field": "end6", formatter:"money", width: 150, hozAlign:"right", formatterParams: { decimal:".", thousand:",", symbol:"", symbolAfter:"", precision:false } },
		{"title": "CTCF Data", "field": "CTCF_data", formatter:"link", formatterParams: { label: "Download", target:"_blank" } },
	],
});

document.getElementById("download-csv").addEventListener("click", function() { table.download("csv", "rearrangements_table.csv"); });
document.getElementById("download-json").addEventListener("click", function() { table.download("json", "rearrangements_table.json"); });
document.getElementById("download-xlsx").addEventListener("click", function() { table.download("xlsx", "rearrangements_table.xlsx", {sheetName:"My Data"}); });

</script>';
?>
