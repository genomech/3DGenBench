<?php

// SHARED
require_once('shared.php');

$AuthorID = htmlspecialchars($_GET['bm_author']);

// GET DB DATA
$DBPairedMetrics = DBSelect((($AuthorID != '') ? 'SELECT * FROM bm_metrics WHERE "Metadata.Author"="'.$AuthorID.'";' : 'SELECT * FROM bm_metrics;'));
$DBInsOnlyPairedMetrics = DBSelect((($AuthorID != '') ? 'SELECT * FROM bm_metrics_insp WHERE "Metadata.Author"="'.$AuthorID.'";' : 'SELECT * FROM bm_metrics_insp;'));
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
while ($Row = $DBInsOnlyPairedMetrics->fetch()) {
	array_push($TableArray, array(
		'ID' => $Row['ID'],
		'Status' => intval($Row['Status']),
		'Type' => 'Paired [Ins Score Only]',
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

?>

<div style="padding: 30px 0 0 0;"><div id="metrics-table"></div></div>

<script>
	var tabledata = <?php echo json_encode($TableArray); ?>;
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
			{"title": "ID", "field": "ID", formatter:function(cell, formatterParams) { return "<a href='<?php echo GetMetricsPage(); ?>?id=" + cell.getValue() + "'>" + cell.getValue() + "</a>"; } },
			{"title": "Status", "field": "Status", formatter:function(cell, formatterParams) { return '<span class="' + { 0: 'button-success', 1: 'button-warning', 2: 'button-error', 3: "button-secondary" }[cell.getValue()] + '" style="display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span>'; } },
			{"title": "Type", "field": "Type"},
			{"title": "Author", "field": "Author"},
			{"title": "Model Name", "field": "ModelName" },
			{"title": "Sample Name", "field": "SampleName" },
			{"title": "Resolution", "field": "Resolution", formatter:function(cell, formatterParams) { return Math.round(Number(cell.getValue()) / 1000).toString() + " kb"; } },
			{"title": "Submission Date", "field": "SubmissionDate"}
			],
	});
</script>

<div style="padding: 10px 0 0 0;">
	<span class="button-secondary" style="display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span><span style="padding: 0 10px 0 5px;">Queued</span>
	<span class="button-success" style="display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span><span style="padding: 0 10px 0 5px;">Success</span>
	<span class="button-warning" style="display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span><span style="padding: 0 10px 0 5px;">Processing</span>
	<span class="button-error" style="display: inline-block; height: 14px; width: 14px; border-radius: 14px;"></span><span style="padding: 0 10px 0 5px;">Failed</span>
</div>

<?php echo GetFooter(); ?>
