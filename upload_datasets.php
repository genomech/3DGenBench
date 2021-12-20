<?php

require(__DIR__.'/shared.php');

$User = GetWPUser();
$Secret = GetSecret($User);
$SamplesList = GetSamples();
$ResolutionsList = GetResolutions();
$UploadedFilesList = GetUploadedFiles($User);

// CSS
echo '
<style>

	.default_cont { display: inline-block; padding: 10px; width: 20%; }
	.button_cont { display: inline-block; padding: 10px; width: 10%; }

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

	function addBlock(first) {
		
		var container = document.getElementById("submission_form");
		if (container.children.length > 13) { alert("Too many datasets!"); return 0; }
		var SamplesList = '.json_encode($SamplesList).';
		var ResolutionsList = '.json_encode($ResolutionsList).';
		var FilesList = '.json_encode($UploadedFilesList).';
		var TS = new Date().getTime();
		var block_id = "block_" + TS;
		var obj_block = document.createElement("div");
		obj_block.setAttribute("id", block_id);
		container.appendChild(obj_block);
		addSelect(TS, obj_block, SamplesList, "sample", "Sample Name");
		addSelect(TS, obj_block, ResolutionsList, "resolution", "Resolution");
		addSelect(TS, obj_block, FilesList, "file_WT", "WT Contacts File");
		addSelect(TS, obj_block, FilesList, "file_MUT", "MUT Contacts File");
		if (!first) addDeleteButton(TS, obj_block);
		return 0;
	}
	
	function SubmitOnClick() {
		var iframe = document.getElementById("formresponse");
		iframe.contentWindow.document.open();
		iframe.contentWindow.document.write("");
		iframe.contentWindow.document.close();
	}
	
</script>';

// HTML

echo '
<form id="submission_form" action="/datasets/submission.php" target="formresponse" method="POST">
	
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
			<input type="text" name="submission_model" value="">
		</div>
		
		<div class="default_cont">
			<input type="submit" value="Submit" onclick="SubmitOnClick()">
		</div>
		
		<div class="default_cont">
			<iframe name="formresponse" id="formresponse" height="75" width="400"></iframe>
		</div>
	</div>
	
	<h3>Datasets</h3>
</form>

<script>addBlock(true);</script>

<div class="button_cont">
	<a href="#" onclick="addBlock(false);">Add</a>
</div>';

?> 
