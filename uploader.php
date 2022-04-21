<?php

  $file_name =  $_FILES['file']['name'];
  $tmp_name = $_FILES['file']['tmp_name'];
  $file_up_name = time().'_'.$file_name;
  move_uploaded_file($tmp_name, "upload/guest/".$file_up_name);
?>