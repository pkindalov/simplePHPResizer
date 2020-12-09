<?php
// File and new size
$filename = 'input/test.jpg';
$percent = 0.5;


// Content type
header('Content-Type: image/jpeg');

// Get new sizes
list($width, $height) = getimagesize($filename);
$newwidth = 800;
$newheight = 600;
$extension = '.jpg';
$resultName = 'resized' . $newwidth . 'x' . $newheight . $extension;

// Load
$thumb = imagecreatetruecolor($newwidth, $newheight);
$source = imagecreatefromjpeg($filename);

// Resize
imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

// Output
imagejpeg($thumb, 'output/' . $resultName, 100);
?>
