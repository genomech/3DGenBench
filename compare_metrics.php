<?php
$RootDir = "/var/www/html/datasets/metrics/";
$ScanDir = array_diff(scandir($RootDir), array('.', '..')); 
$Datasets = array();
foreach ($ScanDir as $f) { $Datasets[htmlspecialchars($f)] = json_decode(file_get_contents($RootDir.htmlspecialchars($f)."/metadata.json"), true); }


// GET PARAMETERS

function GetList($Datasets, $Parameter) {
$Result = array();
foreach ($Datasets as $id => $data) { array_push($Result, $data["Metadata"][$Parameter]); }
return array_unique($Result);
}

function CreateSelectAxis($List, $Name, $Label) {
echo "<div class=\"default_cont\">
<label for=\"".$Name."\">".$Label."</label><select name=\"".$Name."\" id=\"".$Name."\">\n";
foreach ($List as $item) { echo "<option value=\"".$item."\">".$item."</option>\n"; }
echo "</select></div>\n";
}

function CreateSelect($List, $Name, $Label) {
echo "<div class=\"default_cont\">
<label for=\"".$Name."\">".$Label."</label><select size=\"10\" name=\"".$Name."\" id=\"".$Name."\" multiple>\n";
foreach ($List as $item) { echo "<option value=\"".$item."\">".$item."</option>\n"; }
echo "</select></div>\n";
}

$Samples = GetList($Datasets, "SampleName");
$Models = GetList($Datasets, "ModelName");
$Resolutions = GetList($Datasets, "Resolution");

echo "
<style>
.default_cont {
	display: inline-block;
	padding: 10px;
	width: 30%;
}

.button_cont {
	display: inline-block;
	padding: 10px;
	width: 10%;
}

</style>
<iframe name=\"formresponse\" id=\"formresponse\" height=\"500\" width=\"1200\"></iframe>
<form action=\"/datasets/drawgraph.php\" target=\"formresponse\" method=\"POST\">
<h2>Graph Composer</h2>
<div>
";
CreateSelectAxis(array("Sample", "Resolution", "Model"), "axis_x", "X Axis");
CreateSelectAxis(array("Pearson", "SCC", "InsulationScorePearson", "InsulationScoreMutVsWtPearson", "EctopicInsulationAUC", "EctopicInteractionsAUC"), "axis_y", "Y Axis");
CreateSelectAxis(array("Sample", "Resolution", "Model"), "color_axis", "Color");
echo "<h2>Slice</h2>
<div>\n";
CreateSelect($Samples, "sample_name[]", "Sample Name");
CreateSelect($Resolutions, "resolution[]", "Resolution");
CreateSelect($Models, "model_name[]", "Model Name");
echo "</div>
<input type=\"submit\" value=\"Apply\"></input>
</form>";
?> 
