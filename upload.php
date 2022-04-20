 <?php

@ini_set( 'upload_max_size' , '1G' );
@ini_set( 'post_max_size', '1G');

require_once('shared.php');

echo GetHeader('Upload data.');

?>
<!-- Upload adapted from CodingNepal - www.codingnepalweb.com -->
<link rel="stylesheet" href="css/uploader.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>

<div class="wrapper">
    <header>Upload file</header>
    <form action="#">
      <input class="file-input" type="file" name="file" hidden>
      <i class="fas fa-cloud-upload-alt"></i>
      <p>Browse File to Upload</p>
    </form>
    <section class="progress-area"></section>
    <section class="uploaded-area"></section>
  </div>
  <script src="js/uploader.js"></script>

<?php echo GetFooter(); ?> 
 
