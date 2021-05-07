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
        $zip = new ZipArchive();
        $this->input_dir = $this->setPath($input_dir);
        $this->output_dir = $this->setPath($output_dir);
        $this->archive_name = $archive_name;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->input_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    public function zip()
    {
        try {
            echo $this->input_dir . '<br />';
            echo $this->output_dir . '<br />';
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
