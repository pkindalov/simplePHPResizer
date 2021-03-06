<?php

class SimpleResizer
{
    private $input_directory;
    private $output_directory;
    private $file_names;
    private $current_file_name;
    private $file_extension;
    private $dimension;
    private $autoscale;
    private $autoscale_factor;
    private $quality;
    private $png_quality;
    private $width;
    private $height;
    private $errorMsg;
    private $dimension_separator;
    private $dpi;
    private $rotation;
    public function __construct($input_dir = 'input/', $output_dir = 'output/', $dimension = null, $dpi = 72, $rotation = 0)
    {
        $this->input_directory = $input_dir;
        $this->output_directory = $output_dir;
        $this->file_names = $this->getFileNames();
        $this->current_file_name = null;
        $this->file_extension = 'jpg';
        $this->dimension = $dimension;
        $this->dpi = $dpi;
        $this->autoscale = $this->dimension === null ? true : false;
        $this->autoscale_factor = 0.5; //percent
        $this->quality = 100;
        $this->png_quality_compress_lvl = 0; //from 0 - no compress, to max 9 - most compressed
        $this->width = 0;
        $this->height = 0;
        $this->errorMsg = '';
        $this->dimension_separator = 'x';
        $this->dpi = $dpi;
        $this->rotation = $rotation;
    }

    public function resizeAll()
    {
        try {
            if (!$this->checkAvailableFiles()) {
                $this->errorMsg = 'No files found in input directory';
                $this->throwError();
            }

            foreach ($this->file_names as $file_name) {
                $this->current_file_name = $file_name;
                $this->file_extension = $this->getFileExtension($this->current_file_name);
                switch (mb_strtolower($this->file_extension)) {
                    case 'jpg':
                        $this->processJpgFile();
                        break;
                    case 'png':
                        $this->processPngFile();
                        break;
                    default:
                        $this->processJpgFile();
                        break;
                }
            }
            $this->setTextHeader();
            echo $this->current_file_name . ' resized successfull' . '<br />';
        } catch (Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->throwError();
        }
    }

    public function resizeJpgByPercent($percent_num)
    {
        if (!$this->checkAvailableFiles()) {
            $this->errorMsg = 'No files found in input directory';
            $this->throwError();
        }

        $percent_num = $percent_num / 100;
        // echo $percent_num;
        // exit;
        $this->autoscale_factor = $percent_num;

        foreach ($this->file_names as $file_name) {
            $this->current_file_name = $file_name;
            $this->file_extension = $this->getFileExtension($this->current_file_name);
            if (mb_strtolower($this->getFileExtension($this->current_file_name)) !== 'jpg') {
                continue;
            }
            $this->processJpgFileByScale();
        }
            $this->setTextHeader();
            echo $this->current_file_name . ' resized successfull' . '<br />';
    }

    private function processJpgFile()
    {
        try {
            if ($this->current_file_name === null || empty($this->current_file_name)) {
                $this->errorMsg = 'Invalid file name';
                $this->throwError();
            }
            $this->setJpgHeader();
            $file = $this->input_directory . $this->current_file_name;
            $exif = exif_read_data($file);
            $imageSizeInfo = getimagesize($file);
            $width = $imageSizeInfo[0];
            $height = $imageSizeInfo[1];
            if(isset($exif['Orientation'])){
                switch ($exif['Orientation']) {
                    case 1:
                        $width = $imageSizeInfo[0];
                        $height = $imageSizeInfo[1];
                        break;
                    case 6:
                        $width = $imageSizeInfo[1];
                        $height = $imageSizeInfo[0];
                        break;
                    default:
                        $width = $imageSizeInfo[0];
                        $height = $imageSizeInfo[1];
                        break;
                }
            }
            $this->width = $this->getDimWidth();
            $this->width = empty($this->width) ? $this->autoscale_factor * $width : $this->width;
            $this->height = $this->getDimHeight();
            $this->height = empty($this->height) ? $this->autoscale_factor * $height : $this->height;

            //check if the photo is vertical(heigth > width)
            if ($height > $width) {
                $onlyName = $this->getFileName($this->current_file_name);
                $tmp = $this->height;
                $this->height = $this->width;
                $this->width = $tmp;
                $resultLabel = $onlyName . '_size_' . $this->width . $this->dimension_separator . $this->height . '.' . $this->file_extension;
                $thumb = imagecreatetruecolor($this->width, $this->height);
                imageresolution($thumb, $this->dpi, $this->dpi);
                $source = imagecreatefromjpeg($file);
                $source = imagerotate($source, $this->rotation, 0);
                // Resize
                imagecopyresized($thumb, $source, 0, 0, 0, 0, $this->width, $this->height, $width, $height);

                // Output
                imagejpeg($thumb, $this->output_directory . $resultLabel, $this->quality);
                return;
            }

            $onlyName = $this->getFileName($this->current_file_name);
            $resultLabel = $onlyName . '_size_' . $this->width . $this->dimension_separator . $this->height . '.' . $this->file_extension;
            // Load
            $thumb = imagecreatetruecolor($this->width, $this->height);
            imageresolution($thumb, $this->dpi, $this->dpi);
            $source = imagecreatefromjpeg($file);
            $source = imagerotate($source, $this->rotation, 0);
            // Resize
            imagecopyresized($thumb, $source, 0, 0, 0, 0, $this->width, $this->height, $width, $height);

            // Output
            imagejpeg($thumb, $this->output_directory . $resultLabel, $this->quality);
        } catch (Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->throwError();
        }
    }

    private function processPngFile()
    {
        try {
            if ($this->current_file_name === null || empty($this->current_file_name)) {
                $this->errorMsg = 'Invalid file name';
                $this->throwError();
            }
            $this->setPngHeader();
            $file = $this->input_directory . $this->current_file_name;
            list($width, $height) = getimagesize($file);
            $this->width = $this->getDimWidth();
            $this->width = empty($this->width) ? $this->autoscale_factor * $width : $this->width;
            $this->height = $this->getDimHeight();
            $this->height = empty($this->height) ? $this->autoscale_factor * $height : $this->height;

            //check if the photo is vertical(heigth > width)
            if ($height > $width) {
                $onlyName = $this->getFileName($this->current_file_name);
                $tmp = $this->height;
                $this->height = $this->width;
                $this->width = $tmp;
                $resultLabel = $onlyName . '_size_' . $this->width . $this->dimension_separator . $this->height . '.' . $this->file_extension;
                $thumb = imagecreatetruecolor($this->width, $this->height);
                imageresolution($thumb, $this->dpi, $this->dpi);
                $source = imagecreatefrompng($file);
                $source = imagerotate($source, $this->rotation, 0);
                // Resize
                imagecopyresized($thumb, $source, 0, 0, 0, 0, $this->width, $this->height, $width, $height);

                // Output
                imagepng($thumb, $this->output_directory . $resultLabel, $this->png_quality_compress_lvl);
                return;
            }

            $onlyName = $this->getFileName($this->current_file_name);
            $resultLabel = $onlyName . '_size_' . $this->width . $this->dimension_separator . $this->height . '.' . $this->file_extension;

            // Load
            $thumb = imagecreatetruecolor($this->width, $this->height);
            imageresolution($thumb, $this->dpi, $this->dpi);
            $source = imagecreatefrompng($file);
            $source = imagerotate($source, $this->rotation, 0);

            // Resize
            imagecopyresized($thumb, $source, 0, 0, 0, 0, $this->width, $this->height, $width, $height);

            // Output
            imagepng($thumb, $this->output_directory . $resultLabel, $this->png_quality_compress_lvl);
        } catch (Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->throwError();
        }
    }

    private function getDimWidth()
    {
        try {
            if ($this->dimension === null) {
                return false;
            }
            if (mb_strpos($this->dimension, $this->dimension_separator) < 0) {
                return false;
            }

            if (gettype($this->dimension) !== 'string') {
                $this->errorMsg = 'Dimension must be of type string';
                $this->throwError();
            }

            $widthStr = explode($this->dimension_separator, $this->dimension)[0];
            return intval($widthStr);
        } catch (Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->throwError();
        }
    }

    private function getDimHeight()
    {
        try {
            if ($this->dimension === null) {
                return false;
            }
            if (mb_strpos($this->dimension, $this->dimension_separator) < 0) {
                return false;
            }

            if (gettype($this->dimension) !== 'string') {
                return false;
            }

            $heightStr = explode($this->dimension_separator, $this->dimension)[1];
            return intval($heightStr);
        } catch (Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->throwError();
        }
    }

    public function setDimension($dimension)
    {
        try {
            if ($dimension === null) {
                $this->dimension = '800x600';
            }
            $this->dimension = $dimension;
        } catch (Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->throwError();
        }
    }

    public function printFileNames()
    {
        echo "<pre>";
        print_r($this->file_names);
        echo "</pre>";
    }

    private function getFileNames()
    {
        try {
            $fileNames = [];
            foreach (new DirectoryIterator('input/') as $file) {
                if ($file->isFile()) {
                    $fileNames[] = $file->getFilename();
                }
            }
            return $fileNames;
        } catch (Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->throwError();
        }
    }


    private function getFileExtension($file)
    {
        try {
            if (gettype($file) !== 'string') {
                $this->errorMsg = 'Name of the file must be a string';
                $this->throwError();
            }
            return explode('.', $file)[1];
        } catch (Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->throwError();
        }
    }

    private function getFileName($file)
    {
        try {
            if (gettype($file) !== 'string') {
                $this->errorMsg = 'Name of the file must be a string';
                $this->throwError();
            }
            return explode('.', $file)[0];
        } catch (Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->throwError();
        }
    }

    private function checkAvailableFiles()
    {
        if (count($this->file_names) === 0) {
            return false;
        }
        return true;
    }

    private function processJpgFileByScale()
    {
        try {
            if ($this->current_file_name === null || empty($this->current_file_name)) {
                $this->errorMsg = 'Invalid file name';
                $this->throwError();
            }
            $this->setJpgHeader();
            $file = $this->input_directory . $this->current_file_name;
            $exif = exif_read_data($file);
            $imageSizeInfo = getimagesize($file);
            $width = $imageSizeInfo[0];
            $height = $imageSizeInfo[1];
            if(isset($exif['Orientation'])){
                switch ($exif['Orientation']) {
                    case 1:
                        $width = $imageSizeInfo[0];
                        $height = $imageSizeInfo[1];
                        break;
                    case 6:
                        $width = $imageSizeInfo[1];
                        $height = $imageSizeInfo[0];
                        break;
                    default:
                        $width = $imageSizeInfo[0];
                        $height = $imageSizeInfo[1];
                        break;
                }
            }

            $this->width = $this->autoscale_factor * $width;
            $this->height =  $this->autoscale_factor * $height;

            $onlyName = $this->getFileName($this->current_file_name);
            $resultLabel = $onlyName . '_size_' . $this->width . $this->dimension_separator . $this->height . '.' . $this->file_extension;
            // Load
            $thumb = imagecreatetruecolor($this->width, $this->height);
            imageresolution($thumb, $this->dpi, $this->dpi);
            $source = imagecreatefromjpeg($file);
            $source = imagerotate($source, $this->rotation, 0);

            // Resize
            imagecopyresized($thumb, $source, 0, 0, 0, 0, $this->width, $this->height, $width, $height);

            // Output
            imagejpeg($thumb, $this->output_directory . $resultLabel, $this->quality);
        } catch (Exception $ex) {
            $this->errorMsg = $ex->getMessage();
            $this->throwError();
        }
    }

    private function throwError()
    {
        throw new Exception($this->errorMsg);
    }

    private function setJpgHeader()
    {
        header('Content-Type: image/jpeg');
    }
    private function setPngHeader()
    {
        header('Content-Type: image/png');
    }
    private function setTextHeader()
    {
        header('Content-Type: text/html');
    }
}


// $resizerMax = new SimplePHPResizer('input/', 'output/', '6000x4000');
// $resizerExtraLarge = new SimplePHPResizer('input/', 'output/', ' 3464x2309');
// $resizerMedium = new SimplePHPResizer('input/', 'output/', ' 2121x1414');
// $resizerSmall = new SimplePHPResizer('input/', 'output/', '800x533');

// $resizerExtraLarge->resizeAll();
// $resizerMedium->resizeAll();
// $resizerSmall->resizeAll();
// $resizerMedium->resizeAll();
// $resizerMax->resizeAll();

// $largeQuality = 81.64;
// $resiserXL = new SimplePHPResizer('input/', 'output/');
// $resiserXL->resizeJpgByPercent($largeQuality);


//---------------------------------------------------------------------

// $largeQuality = 64.54;
// $resizerL = new SimplePHPResizer('input/', 'output/');
// $resizerL->resizeJpgByPercent($largeQuality);

// $smallQuality = 20;
// $resizerS = new SimplePHPResizer('input/', 'output/');
// $resizerS->resizeJpgByPercent($smallQuality);


// $small = new SimplePHPResizer('input/', 'output/', '815x614', 72);
// $small->resizeAll();

// $medium = new SimplePHPResizer('input/', 'output/', '1630x1227', 300);
// $medium->resizeAll();

// $large = new SimplePHPResizer('input/', 'output/', '2751x2072', 300);
// $large->resizeAll();

// $xLarge = new SimplePHPResizer('input/', 'output/', '4256x2832', 300);
// $xLarge->resizeAll();

//------------------------------------------------------------------------

// resizeByPercent()
// 
// 
//0.816 -- 0,8161 -from original size to extra large
//  2121x1414p
// 
// $resizer->printFileNames();
// foreach (new DirectoryIterator('input/') as $file) {
//   if ($file->isFile()) {
//       print $file->getFilename() . "\n";
//   }
// }
// File and new size
// $filename = 'input/test.jpg';
// $percent = 0.5;


// // Content type
// header('Content-Type: image/jpeg');

// // Get new sizes
// list($width, $height) = getimagesize($filename);
// $newwidth = 800;
// $newheight = 600;
// $extension = '.jpg';
// $resultName = 'resized' . $newwidth . 'x' . $newheight . $extension;

// // Load
// $thumb = imagecreatetruecolor($newwidth, $newheight);
// $source = imagecreatefromjpeg($filename);

// // Resize
// imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

// // Output
// imagejpeg($thumb, 'output/' . $resultName, 100);
