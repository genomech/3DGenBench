<?php

require(__DIR__.'/shared.php');

$User = GetWPUser();
$Secret = GetSecret($User);
$SamplesList = GetSamples();
$WGSamplesList = GetSamplesWG();
$ResolutionsList = GetResolutions();
$UploadedFilesList = GetUploadedFiles($User);

$ModelsList = ''; foreach (GetModelsUser() as $Model) $ModelsList .= '<option value="'.htmlspecialchars($Model).'"></option>';

echo GetHeader('Compute Metrics');

?>

<script>
	
	function addSelect(ts, container, entries_list, tag, label) {
		var stag = tag + "_" + ts;
		var div = document.createElement("div");
		div.setAttribute("class", "pure-u-1-5");
		container.appendChild(div);
		var lbl_select = document.createElement("label");
		lbl_select.setAttribute("for", stag);
		lbl_select.innerHTML = label;
		div.appendChild(lbl_select);
		var obj_select = document.createElement("select");
		obj_select.setAttribute("name", stag);
		obj_select.setAttribute("id", stag);
		obj_select.setAttribute("class", "pure-u-23-24");
		div.appendChild(obj_select);
		for (const [key, value] of Object.entries(entries_list)) {
			var obj_option = document.createElement("option");
			obj_option.setAttribute("value", key);
			obj_option.innerHTML = value;
			obj_select.appendChild(obj_option);
		}
		return 0;
	}
	
	function addDeleteButton(TS, container) {
		var stag = "block_" + TS;
		var div = document.createElement("div");
		div.setAttribute("class", "pure-u-1-8");
		container.appendChild(div);
		var lbl_select = document.createElement("label");
		lbl_select.setAttribute("for", stag);
		lbl_select.innerHTML = "&nbsp";
		div.appendChild(lbl_select);
		var obj_button = document.createElement("button");
		obj_button.setAttribute("onclick", "var elem = document.getElementById('" + stag + "'); elem.remove();");
		obj_button.setAttribute("class", "pure-button pure-u-23-24");
		obj_button.innerHTML = "Remove";
		div.appendChild(obj_button);
		return 0;
	}

	function addBlock(TS, DataType) {
		var container = document.getElementById("submission_form");
		if (container.children.length > 13) { alert("Too many datasets!"); return 0; }
		if (DataType == "p") var SamplesList = <?php echo json_encode($SamplesList); ?>;
		if (DataType == "insp") var SamplesList = <?php echo json_encode($SamplesList); ?>;
		if (DataType == "s") var SamplesList = <?php echo json_encode($WGSamplesList); ?>;
		if (DataType == "inss") var SamplesList = <?php echo json_encode($WGSamplesList); ?>;
		var ResolutionsList = <?php echo json_encode($ResolutionsList); ?>;
		var FilesList = <?php echo json_encode($UploadedFilesList); ?>;
		var block_id = "block_" + TS;
		var obj_block = document.createElement("div");
		obj_block.setAttribute("id", block_id);
		obj_block.setAttribute("style", "padding: 0 0 10px 0;");
		container.appendChild(obj_block);
		addSelect(TS, obj_block, SamplesList, "sample", "Sample Name");
		addSelect(TS, obj_block, ResolutionsList, "resolution", "Resolution");
		if (DataType == "p") addSelect(TS, obj_block, FilesList, "file_WT", "WT Contacts File");
		if (DataType == "s") addSelect(TS, obj_block, FilesList, "file_WT", "WT Contacts File");
		if (DataType == "insp") addSelect(TS, obj_block, FilesList, "file_WT", "WT Insulatory Score File");
		if (DataType == "inss") addSelect(TS, obj_block, FilesList, "file_WT", "WT Insulatory Score File");
		if (DataType == "p") addSelect(TS, obj_block, FilesList, "file_MUT", "MUT Contacts File");
		if (DataType == "s") addSelect(TS, obj_block, FilesList, "file_MUT", "MUT Contacts File");
		if (DataType == "insp") addSelect(TS, obj_block, FilesList, "file_MUT", "MUT Insulatory Score File");
		if (DataType == "inss") addSelect(TS, obj_block, FilesList, "file_MUT", "MUT Insulatory Score File");
		if (DataType == "s") document.getElementById("file_MUT_" + TS).disabled = true;
		if (DataType == "inss") document.getElementById("file_MUT_" + TS).disabled = true;
		addDeleteButton(TS, obj_block);
		return 0;
	}
	
	function SubmitOnClick() {
		var iframe = document.getElementById("formresponse");
		iframe.hidden = false;
		iframe.contentWindow.document.open();
		iframe.contentWindow.document.write("");
		iframe.contentWindow.document.close();
	}
	
	function addSampleBlock() {
		var tp = document.getElementById("data_type").value;
		var ts = new Date().getTime();
		addBlock(ts, tp);
	}
	
	function addTest() {
		for (elem of document.querySelectorAll('[id^=block_]')) elem.remove();
		var tp = document.getElementById("data_type").value;
		var ts = new Date().getTime();
		addBlock(ts, tp);
		if (tp == "p") {
			document.getElementById("models_list").value = "TestModel_Paired";
			document.getElementById("sample_" + ts).value = "Bor";
			document.getElementById("resolution_" + ts).value = "5000";
			document.getElementById("file_WT_" + ts).value = "/storage/fairwind/3DGenBench_upload/guest/Bor_5kb_WT_3DPredictor.txt";
			document.getElementById("file_MUT_" + ts).value = "/storage/fairwind/3DGenBench_upload/guest/Bor_5kb_MUT_3DPredictor";
		}
		if (tp == "s") {
			document.getElementById("models_list").value = "TestModel_Single";
			document.getElementById("sample_" + ts).value = "GM12878_chr19_36to56Mb";
			document.getElementById("resolution_" + ts).value = "10000";
			document.getElementById("file_WT_" + ts).value = "/storage/fairwind/3DGenBench_upload/guest/chr19_22to42_10kb_GM12878_model30_smarterprediction.txt";
		}
		if (tp == "inss") {
			document.getElementById("models_list").value = "TestModel_SingleInsOnly";
			document.getElementById("sample_" + ts).value = "GM12878_chr1_22to42Mb";
			document.getElementById("resolution_" + ts).value = "10000";
			document.getElementById("file_WT_" + ts).value = "/storage/fairwind/3DGenBench_upload/guest/GM12878_chr1_22to42Mb_10Kb.bedgraph";
		}
		if (tp == "insp") {
			document.getElementById("models_list").value = "TestModel_PairedInsOnly";
			document.getElementById("sample_" + ts).value = "Bor";
			document.getElementById("resolution_" + ts).value = "5000";
			document.getElementById("file_WT_" + ts).value = "/storage/fairwind/3DGenBench_upload/guest/wt_Bor_predicted_ins_score_5Kb.bedgraph";
			document.getElementById("file_MUT_" + ts).value = "/storage/fairwind/3DGenBench_upload/guest/mut_Bor_predicted_ins_score_5Kb.bedgraph";
		}
	}
	
</script>

<form id="submission_form" class="pure-form pure-form-stacked" action="<?php echo GetSubmissionScript(); ?>" target="formresponse" method="POST">
	
	<input type="hidden" name="submission_secret" value="<?php echo $Secret; ?>">
	<input type="hidden" name="submission_user" value="<?php echo $User; ?>">
	
	<h2>General Submission Info</h2>
	
	<div class="pure-g">
	
		<div class="pure-u-1-5">
			<label for="submission_user1">Username:</label>
			<input id="submission_user1" class="pure-u-23-24" type="text" value="<?php echo $User; ?>" disabled>
		</div>
		
		<div class="pure-u-1-5">
			<label for="models_list">Model Name:</label>
			<input type="text" class="pure-u-23-24" name="submission_model" id="models_list" list="models_list">
			<datalist id="models_list"><?php echo $ModelsList; ?></datalist>
		</div>
		
		<div class="pure-u-1-5">
			<label for="data_type">Type:</label>
			<select name="data_type" class="pure-u-23-24" id="data_type" onchange="for (elem of document.querySelectorAll('[id^=block_]')) elem.remove(); addSampleBlock();">
				<option value="p">Paired [WT/MUT]</option>
				<option value="insp">Paired [Ins Score Only]</option>
				<option value="s">Single</option>
				<option value="inss">Single [Ins Score Only]</option>
				
			</select>
		</div>
		
		<div class="pure-u-1-8">
			<label for="_">&nbsp;</label>
			<input type="submit" class="pure-button pure-u-23-24 button-success" value="Submit" onclick="SubmitOnClick()">
		</div>
		
		<div class="pure-u-1-5">
			<label for="_">&nbsp;</label>
			<button type="button" class="pure-button pure-u-23-24 button-secondary" onclick="window.open('tutorial.php#uploading', 'blank');">Datasets Upload Howto</button>
		</div>
	
	</div>
	
	<iframe hidden name="formresponse" class="pure-u-3-5" id="formresponse" frameBorder="0" scrolling="no" margin="0" height="90" width="100%"></iframe>
	
	<h2>Datasets</h2>
	
	<script>addSampleBlock();</script>
</form>

<div class="pure-u-1-8"><button id="add_sample" class="pure-button pure-u-23-24" onclick="addSampleBlock();">Add Unit</button></div>
<div class="pure-u-1-8"><button id="add_tetsaaa" class="pure-button pure-u-23-24" onclick="addTest();">Add Test Unit</button>

<?php echo GetFooter(); ?> 
