<?php

require(__DIR__.'/shared.php');

$User = GetWPUser();
$Secret = GetSecret($User);
$SamplesList = GetSamples();
$WGSamplesList = GetSamplesWG();
$ResolutionsList = GetResolutions();
$UploadedFilesList = GetUploadedFiles($User);

$ModelsList = ''; foreach (GetModelsUser() as $Model) $ModelsList .= '<option value="'.htmlspecialchars($Model).'"></option>';

// CSS
echo '
<style>

	.default_cont { display: inline-block; padding: 10px; width: 20%; }
	.button_cont { display: inline-block; padding: 10px; width: 20%; }

</style>';

// JS Func
echo '
<script>
	
	function addSelect(ts, container, entries_list, tag, label) {
		var stag = tag + "_" + ts;
		var div = document.createElement("div");
		div.setAttribute("class", "default_cont");
		container.appendChild(div);
		var lbl_select = document.createElement("label");
		lbl_select.setAttribute("for", stag);
		lbl_select.innerHTML = label;
		div.appendChild(lbl_select);
		var obj_select = document.createElement("select");
		obj_select.setAttribute("name", stag);
		obj_select.setAttribute("id", stag);
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
		div.setAttribute("class", "button_cont");
		container.appendChild(div);
		var obj_button = document.createElement("a");
		obj_button.setAttribute("href", "#");
		obj_button.setAttribute("onclick", "var elem = document.getElementById(\'" + stag + "\'); elem.remove();");
		obj_button.innerHTML = "Remove";
		div.appendChild(obj_button);
		return 0;
	}

	function addBlock(TS, DataType) {
		
		var container = document.getElementById("submission_form");
		if (container.children.length > 13) { alert("Too many datasets!"); return 0; }
		if (DataType == "p") var SamplesList = '.json_encode($SamplesList).';
		if (DataType == "s") var SamplesList = '.json_encode($WGSamplesList).';
		var ResolutionsList = '.json_encode($ResolutionsList).';
		var FilesList = '.json_encode($UploadedFilesList).';
		var block_id = "block_" + TS;
		var obj_block = document.createElement("div");
		obj_block.setAttribute("id", block_id);
		container.appendChild(obj_block);
		addSelect(TS, obj_block, SamplesList, "sample", "Sample Name");
		addSelect(TS, obj_block, ResolutionsList, "resolution", "Resolution");
		addSelect(TS, obj_block, FilesList, "file_WT", "WT Contacts File");
		addSelect(TS, obj_block, FilesList, "file_MUT", "MUT Contacts File");
		if (DataType == "s") document.getElementById("file_MUT_" + TS).disabled = true;
		addDeleteButton(TS, obj_block);
		return 0;
	}
	
	function SubmitOnClick() {
		var iframe = document.getElementById("formresponse");
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
		for (elem of document.querySelectorAll(\'[id^=block_]\')) elem.remove();
		var tp = document.getElementById("data_type").value;
		var ts = new Date().getTime();
		addBlock(ts, tp);
		if (tp == "p") {
			document.getElementById("sample_" + ts).value = "Bor";
			document.getElementById("resolution_" + ts).value = "5000";
			document.getElementById("file_WT_" + ts).value = "/storage/fairwind/3DGenBench/upload/guest/Bor_5kb_WT_3DPredictor.txt";
			document.getElementById("file_MUT_" + ts).value = "/storage/fairwind/3DGenBench/upload/guest/Bor_5kb_MUT_3DPredictor";
		}
		if (tp == "s") {
			document.getElementById("sample_" + ts).value = "GM12878_chr19_36to56Mb";
			document.getElementById("resolution_" + ts).value = "10000";
			document.getElementById("file_WT_" + ts).value = "/storage/fairwind/3DGenBench/upload/guest/chr19_22to42_10kb_GM12878_model30_smarterprediction.txt";
		}
	}
</script>';

// HTML

echo '
<form id="submission_form" action="'.GetSubmissionScript().'" target="formresponse" method="POST">
	
	<input type="hidden" name="submission_secret" value="'.$Secret.'">
	
	<h3>General Submission Info</h3>
	<div>
		<div class="default_cont">
			<label for="submission_user">Username:</label>
			<input type="text" value="'.$User.'" disabled>
			<input type="hidden" name="submission_user" value="'.$User.'">
		</div>
		
		<div class="default_cont">
			<label for="submission_model">Model Name:</label>
			<input type="text" name="submission_model" id="models_list" list="models_list">
			<datalist id="models_list">'.$ModelsList.'</datalist>
		</div>
		
		<div class="default_cont">
			<label for="data_type">Type:</label>
			<select name="data_type" id="data_type" onchange="for (elem of document.querySelectorAll(\'[id^=block_]\')) elem.remove(); addSampleBlock();"><option value="p">Paired [WT/MUT]</option><option value="s">Single</option></select>
		</div>
		
		<div class="default_cont">
			<input type="submit" value="Submit" onclick="SubmitOnClick()">
		</div>
	</div>
	<iframe name="formresponse" id="formresponse" height="50" width="100%"></iframe>
	<br>
	<h3>Datasets &nbsp; &nbsp; &nbsp;<button type="button" class="button btn-default color2" onclick="window.open(\'index.php/tutorial#uploading\', \'_blank\');">Datasets Upload Howto</button> </h3>
</form>

<script>addSampleBlock();</script>

<div class="button_cont">
	<a id="add_sample" href="#" onclick="addSampleBlock();">Add Unit</a>
</div><br>
<div class="button_cont">
	<a id="add_tetsaaa" href="#" onclick="addTest();">Add Test Unit</a>
</div>
';

?> 
