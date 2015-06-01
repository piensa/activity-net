<?php

include('classes/videos.class.php');

if(!isset($_POST['page']))
{
	$page = 1;

}
else
{
	$page = $_POST['page'];
}

$nodeId = $_POST['nodeId'];

// Connect to database
$videos = videos::singleton();

// Retrieve all children nodes
$videos->getArray($nodeId);

// Get total amount of videos
$videos->getTotalVideos($videos->condition);

// Get all Videos
$videos->getVideos($videos->condition, $page);

// Adding number into array
$videos->videosArray[] = $videos->size;

echo json_encode($videos->videosArray);

?>