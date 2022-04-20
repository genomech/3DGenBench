<?php
  $file_name =  $_FILES['file']['name'];
  $tmp_name = $_FILES['file']['tmp_name'];
  $file_up_name = $file_name.'_'.time();
  move_uploaded_file($tmp_name, "upload/guest/".$file_up_name);
?>