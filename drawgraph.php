<?php
$RootDir = "/var/www/html/datasets/metrics/";
$ScanDir = array_diff(scandir($RootDir), array('.', '..')); 
$Datasets = array();
foreach ($ScanDir as $f) { $Datasets[htmlspecialchars($f)] = json_decode(file_get_contents($RootDir.htmlspecialchars($f)."/metadata.json"), true); }

$Samples = $_POST["sample_name"];
$Resolutions = $_POST["resolution"];
$Models = $_POST["model_name"];

// Filtration

$FilteredDatasets = array();
foreach ($Datasets as $key => $data) { 
	if (in_array($data["Metadata"]["SampleName"], $Samples) and in_array($data["Metadata"]["Resolution"], $Resolutions) and in_array($data["Metadata"]["ModelName"], $Models)) { $FilteredDatasets[$key] = $data; }
}

$Array = array();
$Full = array("Model" => $Models, "Sample" => $Samples, "Resolution" => $Resolutions);
$Rows = $Full[$_POST["axis_x"]];
$ColumnsFull = $Full; unset($ColumnsFull[$_POST["axis_x"]]);
$ColumnKey1 = array_keys($ColumnsFull)[0];
$ColumnKey2 = array_keys($ColumnsFull)[1];
$Columns = array();
foreach ($ColumnsFull[$ColumnKey1] as $param1) {
	foreach ($ColumnsFull[$ColumnKey2] as $param2) {
		array_push($Columns, array($ColumnKey1 => $param1, $ColumnKey2 => $param2));
	}
}

foreach ($Rows as $row) { 
	
	$newrow = array($row);
	$newrow = array_merge($newrow, array_fill(1, count($Columns), NULL));
	array_push($Array, $newrow);
}

foreach ($FilteredDatasets as $key => $data) {
	foreach ($Columns as $index => $col) {
		
	}
}

echo "
<html lang=\"en-US\">
<head>
<script src=\"https://www.gstatic.com/charts/loader.js\"></script> 
</head>
<body>
<div id=\"point\">
</div>

<script>


google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawBoxPlot);

function drawBoxPlot() {
 var array = ".json_encode($Array).";

 var data = new google.visualization.DataTable();
 data.addColumn('string', 'x');\n";
 
 foreach ($Columns as $col) { echo "data.addColumn('number', '".$ColumnKey1."".$col[$ColumnKey1]."".$ColumnKey2."".$col[$ColumnKey2]."');\n"; }

echo "data.addColumn({id:'max', type:'number', role:'interval'});
 data.addColumn({id:'min', type:'number', role:'interval'});
 data.addColumn({id:'firstQuartile', type:'number', role:'interval'});
 data.addColumn({id:'median', type:'number', role:'interval'});
 data.addColumn({id:'thirdQuartile', type:'number', role:'interval'});

 data.addRows(getBoxPlotValues(array));

/*
* Takes an array of input data and returns an
* array of the input data with the box plot
* interval data appended to each row.
*/
 
 function getBoxPlotValues(array) {
  for (var i = 0; i < array.length; i++) {
   var arr = array[i].slice(1).sort(function (a, b) {
   return a - b; 
   });

   var max = arr[arr.length - 1];
   var min = arr[0];
   var median = getMedian(arr);

// First Quartile is the median from lowest to overall median.
   var firstQuartile = getMedian(arr.slice(0, Math.floor(arr.length / 2) + 1));

// Third Quartile is the median from the overall median to the highest.
   var thirdQuartile = getMedian(arr.slice(Math.floor(arr.length / 2)));

   array[i][arr.length + 1] = max;
   array[i][arr.length + 2] = min
   array[i][arr.length + 3] = firstQuartile;
   array[i][arr.length + 4] = median;
   array[i][arr.length + 5] = thirdQuartile;
  }
  
  return array;
 
 }

function getMedian(array) {
  var length = array.length;

 if (length % 2 === 0) {
  var midUpper = length / 2;
  var midLower = midUpper - 1;
  return (array[midUpper] + array[midLower]) / 2;
 } 
 else {
  return array[Math.floor(length / 2)];
 }
 }

 var options = {
  title:'3DBenchmark Box Plot',
  height: 400,
  legend: {position: 'none'},
  vAxis: {
	title: '".$_POST["axis_y"]."',
},

  hAxis: {
  title: '".$_POST["axis_x"]."',
   gridlines: {color: '#fff'} 
  },
  lineWidth: 0,
  series: [{'color': '#D3362D'}],
  intervals: {
   barWidth: 1,
   boxWidth: 1,
   lineWidth: 2,
   style: 'boxes'
  },
  interval: {
   max: {
    style: 'bars',
    fillOpacity: 1,
    color: '#777'
   },
   min: {
    style: 'bars',
    fillOpacity: 1,
    color: '#777'
   }
  }
 };

var chart = new google.visualization.LineChart(document.getElementById('point'));
chart.draw(data, options);
}
</script>  
</body>
</html>";

?>
