<?php
class SimpleArchiver
{
    private $zip;
    private $input_dir;
    private $output_dir;
    private $archive_name;
    private $files;

    public function __construct($input_dir = 'input/', $output_dir = 'output/', $archive_name = 'bundle')
    {
        $this->zip = new ZipArchive();
        $this->input_dir = $this->setPath($input_dir);
        $this->output_dir = $this->setPath($output_dir);
        $this->archive_name = $archive_name;
        $this->files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->input_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    public function zip()
    {
        try {
            $this->zip->open($this->output_dir . '/' . $this->archive_name . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
            foreach ($this->files as $name => $file) {
                // Skip directories (they would be added automatically)
                if (!$file->isDir()) {
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($this->output_dir) + 1);

                    // Add current file to archive
                    $this->zip->addFile($filePath, $relativePath);

                    // Zip archive will be created only after closing object
                }
            }
            $this->zip->close();

            echo 'archive created successfull';
        } catch (Exception $ex) {
            $this->errThrower($ex->getMessage());
        }
    }

    private function setPath($path)
    {
        try {
            if (!$path || empty($path)) {
                $this->errThrower('Invalid value of the path. Cannot be empty , null or undefined');
            }
            return realpath($path);
        } catch (Exception $ex) {
            $this->errThrower($ex->getMessage());
        }
    }

    private function errThrower($msg)
    {
        throw new Exception($msg);
        exit;
    }
}
