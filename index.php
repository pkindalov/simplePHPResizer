<?php
require_once('SimpleResizer.php');
require_once('SimpleArchiver.php');

    $small = new SimpleResizer('input/', 'output/', '815x614', 300, 0);
    $small->resizeAll();

    $medium = new SimpleResizer('input/', 'output/', '1630x1227', 300, 0);
    $medium->resizeAll();

    $large = new SimpleResizer('input/', 'output/', '2751x2072', 300, 0);
    $large->resizeAll();

    $xLarge = new SimpleResizer('input/', 'output/', '4256x2832', 300, 0);
    $xLarge->resizeAll();

$archiver = new SimpleArchiver('output/', 'output/', 'bundle');
$archiver->zip();