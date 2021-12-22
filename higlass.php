<?php

$IsView = htmlspecialchars($_GET['isview']);

if ($IsView == '') {
echo '<iframe width="1000" height="1000" src="../../../datasets/higlass.php?isview=true"></iframe>';
} else {

echo '
<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <title>Minimal Working Example &middot; HiGlass</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://unpkg.com/higlass@1.6.6/dist/hglib.css">

  <style type="text/css">
    html, body {
      width: 100vw;
      height: 100vh;
      overflow: hidden;
    }
  </style>

  <script crossorigin src="https://unpkg.com/react@16/umd/react.production.min.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@16/umd/react-dom.production.min.js"></script>
  <script crossorigin src="https://unpkg.com/pixi.js@5/dist/pixi.min.js"></script>
  <script crossorigin src="https://unpkg.com/react-bootstrap@0.32.1/dist/react-bootstrap.min.js"></script>
  <script crossorigin src="https://unpkg.com/higlass@1.6.6/dist/hglib.min.js"></script>
</head>
<body></body>
<script>
const hgApi = window.hglib.viewer(
  document.body,
  "http://alena-spn.icgbio.ru:8888/api/v1/viewconfs/?d=default",
  {
  "viewconf": {
    "editable": true,
    "zoomFixed": false,
    "trackSourceServers": [
      "http://higlass.io/api/v1"
    ],
    "exportViewUrl": "http://alena-spn.icgbio.ru:8888/api/v1/viewconfs/",
    "views": [
      {
        "tracks": {
          "top": [],
          "left": [],
          "center": [],
          "right": [],
          "bottom": []
        },
        "initialXDomain": [
          243883495.14563107,
          2956116504.854369
        ],
        "initialYDomain": [
          804660194.1747572,
          2395339805.825243
        ],
        "layout": {
          "w": 12,
          "h": 12,
          "x": 0,
          "y": 0,
          "i": "EwiSznw8ST2HF3CjHx-tCg",
          "moved": false,
          "static": false
        },
        "uid": "EwiSznw8ST2HF3CjHx-tCg"
      }
    ],
    "zoomLocks": {
      "locksByViewUid": {},
      "locksDict": {}
    },
    "locationLocks": {
      "locksByViewUid": {},
      "locksDict": {}
    },
    "valueScaleLocks": {
      "locksByViewUid": {},
      "locksDict": {}
    }
  }
}
);
</script>
</html>
';
}

?>
