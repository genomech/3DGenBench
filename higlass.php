<?php

$UID = htmlspecialchars($_GET['id']);

$Width = 1200;
$Height = 600;

$GenomicCoordinatesTilesetUID = 'NyITQvZsS_mOFNlz5C2LJg';
$GenomicCoordinatesWidth = 30;

echo '
<!DOCTYPE html>
	<head>
		<meta charset="utf-8">
		
		<link rel="stylesheet" href="https://unpkg.com/higlass@1.5.7/dist/hglib.css" type="text/css">
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" type="text/css">
		
		<script crossorigin src="https://unpkg.com/react@16.6/umd/react.production.min.js"></script>
		<script crossorigin src="https://unpkg.com/react-dom@16.6/umd/react-dom.production.min.js"></script>
		<script crossorigin src="https://unpkg.com/pixi.js@5/dist/pixi.min.js"></script>
		<!-- To render HiGlass with the Canvas API include the pixi.js-legacy instead of pixi.js -->
		<!-- <script crossorigin src="https://unpkg.com/pixi.js-legacy@5/dist/pixi-legacy.min.js"></script> -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/react-bootstrap/0.32.1/react-bootstrap.min.js"></script>
		
		<script src="https://unpkg.com/higlass@1.6/dist/hglib.min.js"></script>
	</head>
	
	<body>
		<div id="higlass-container" style="width: '.$Width.'px; height: '.$Height.'px; background-color: white;"></div>
	</body>
	
	<script>
		
		function MakeSessionID() {
			var Length = 16;
			var Result = "";
			var Characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
			for ( var i = 0; i < Length; i++ ) Result += Characters.charAt(Math.floor(Math.random() * Characters.length));
			return Result;
		}
		
		function GenerateGenomeTrack(Label, SessionID, Position) { return { 
			"server": "http://higlass.io/api/v1",
			"tilesetUid": "'.$GenomicCoordinatesTilesetUID.'",
			"uid": Label + "_" + SessionID,
			"type": Position + "-chromosome-labels",
			"options": { "color": "#606060", "stroke": "#ffffff", "fontSize": 12},
			"width": '.$GenomicCoordinatesWidth.',
			"height": '.$GenomicCoordinatesWidth.'
			}; }
		
		function GenerateHeatmapTrack(Label, TileSetID, SessionID, Name, Position) { return {
			"filetype": "cooler",
			"server": "http://alena-spn.icgbio.ru:8888/api/v1",
			"tilesetUid": TileSetID,
			"uid": Label + "_" + SessionID,
			"type": "heatmap",
			"options": {
				"backgroundColor": "transparent",
				"labelPosition": { "top": "topRight", "bottom": "bottomLeft" }[Position],
				"labelLeftMargin": 0,
				"labelRightMargin": 0,
				"labelTopMargin": 0,
				"labelBottomMargin": 0,
				"labelShowResolution": true,
				"labelShowAssembly": true,
				"colorRange": [
					"white",
					"rgba(245,166,35,1.0)",
					"rgba(208,2,27,1.0)",
					"black"
				],
				"colorbarBackgroundColor": "#ffffff",
				"maxZoom": null,
				"minWidth": 500,
				"minHeight": 500,
				"colorbarPosition": {"top": "topRight", "bottom": "bottomLeft"}[Position],
				"trackBorderWidth": 0,
				"trackBorderColor": "black",
				"heatmapValueScaling": "log",
				"showMousePosition": false,
				"mousePositionColor": "#000000",
				"showTooltip": false,
				"extent": {"top": "upper-right", "bottom": "lower-left"}[Position],
				"zeroValueColor": null,
				"name": Name,
				"scaleStartPercent": "0.00000",
				"scaleEndPercent": "1.00000"
			},
			"transforms": [
				{
					"name": "ICE",
					"value": "weight"
				}
			],
			"width": 355,
			"height": 478
		}; }
		
		function GenerateView(ViewID, SessionID, TopTilesetID, TopName, BottomTilesetID, BottomName, X, Y) { return JSON.parse(JSON.stringify({
			"tracks": {
				"top": [ GenerateGenomeTrack(ViewID + "_GenomeTrackH", SessionID, "horizontal") ],
				"left": [ GenerateGenomeTrack(ViewID + "_GenomeTrackV", SessionID, "vertical") ],
				"center": [
					{
						"uid": ViewID + "_TracksContainer_" + SessionID,
						"type": "combined",
						"contents": [
							GenerateHeatmapTrack(ViewID + "_TrackTopRight", TopTilesetID, SessionID, TopName, "top"),
							GenerateHeatmapTrack(ViewID + "_TrackBottomLeft", BottomTilesetID, SessionID, BottomName, "bottom")
						],
						"width": 355,
						"height": 478,
						"options": {}
					}
				],
				"right": [],
				"bottom": [],
				"whole": [],
				"gallery": []
			},
			"initialXDomain": [
				0,
				1000000
			],
			"initialYDomain": [
				0,
				1000000
			],
			"layout": {
				"w": 6,
				"h": 6,
				"x": 6 * X,
				"y": 6 * Y,
				"moved": false,
				"static": false
			},
			"uid": "View_" + ViewID + "_" + SessionID
		})); }
		
		 function GenerateLock(LockID, SessionID, View1, View2) { 
			var VSID1 = "View_" + View1 + "_" + SessionID;
			var VSID2 = "View_" + View2 + "_" + SessionID;
			var LID = LockID + "_" + SessionID;
			return JSON.parse("{ \"locksByViewUid\": { \"" + VSID2 + "\": \"" + LID + "\", \"" + VSID1 + "\": \"" + LID + "\" }, \"locksDict\": { \"" + LID + "\": { \"" + VSID2 + "\": [ 1090752478.8873124, 1099371687.717565, 200779.88244223595 ], \"" + VSID1 + "\": [ 1092559497.8292904, 1099170907.835121, 200779.88244223595 ], \"uid\": \"" + LID + "\" } } }"); 
		}
		 
		const SessionID = MakeSessionID();
		
		const WTView = GenerateView("WT", SessionID, "'.$UID.'-WtExp", "ID: '.$UID.', WT [Control]", "'.$UID.'-WtPred", "ID: '.$UID.', WT [Prediction]", 0, 0);
		const MUTView = GenerateView("MUT", SessionID, "'.$UID.'-MutExp", "ID: '.$UID.', MUT [Control]", "'.$UID.'-MutPred", "ID: '.$UID.', MUT [Prediction]", 1, 0);
		const objZoomLock = GenerateLock("ZoomLock", SessionID, "WT", "MUT");
		const objLocationLock = GenerateLock("LocationLock", SessionID, "WT", "MUT");
		
		const ViewConfig = {
			
			"editable": false,
			"zoomFixed": false,
			"trackSourceServers": [ "/api/v1", "http://higlass.io/api/v1" ],
			"exportViewUrl": "/api/v1/viewconfs/",
			
			"views": [ WTView, MUTView ],
			"zoomLocks": objZoomLock,
			"locationLocks": objLocationLock
		};
		
		const hgApi = hglib.viewer(
			document.getElementById("higlass-container"),
			ViewConfig,
			{ bounded: true }
		);
		
	</script>
</html>

';

?>
