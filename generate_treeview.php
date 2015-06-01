<?php
require ('classes/videos.class.php');

  // Connect to database
$videos = videos::singleton();

  // retrieve all nodes into array
ob_start();
$videos->getNodes();
$output = ob_get_contents(); //Grab output
ob_end_clean(); //Discard output buffer

echo $output;
?>