<?php

define( 'VENDI_LOG_FILE_PARSER_FILE', __FILE__ );
define( 'VENDI_LOG_FILE_PARSER_PATH', __DIR__ );

require VENDI_LOG_FILE_PARSER_PATH . '/includes/autoload.php';

use Vendi\LogParser\worker;
use Vendi\Shared\utils;

if(! class_exists('ZipArchive')){
    throw new \Exception('Install PHP Zip');
}

if(! function_exists('gzopen')){
    throw new \Exception('Install gzip supports for PHP');
}

function uncompress_gz_file(string $input_file_name) : string
{
    // Raising this value may increase performance
    $buffer_size = 4096; // read 4kb at a time
    $out_file_name = tempnam(sys_get_temp_dir(), 'CLICK_FRAUD_');

    // Open our files (in binary mode)
    $file = gzopen($input_file_name, 'rb');
    $out_file = fopen($out_file_name, 'wb');

    // Keep repeating until the end of the input file
    while (!gzeof($file)) {
        // Read buffer-size bytes
        // Both fwrite and gzread and binary-safe
        fwrite($out_file, gzread($file, $buffer_size));
    }

    // Files are done, close files
    fclose($out_file);
    gzclose($file);

    return $out_file_name;
}

function send_file(string $filename)
{
    //Get file type and set it as Content Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    header('Content-Type: ' . finfo_file($finfo, $filename));
    finfo_close($finfo);

    //Use Content-Disposition: attachment to specify the filename
    header('Content-Disposition: attachment; filename='.basename($filename));

    //No cache
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    //Define file size
    header('Content-Length: ' . filesize($filename));

    ob_clean();
    flush();
    readfile($filename);
}

if(utils::is_post()){
    $file = isset($_FILES) && array_key_exists('log_file', $_FILES) ? $_FILES['log_file'] : null;
    if(!$file){
        throw new \Exception('No file');
    }

    $tmp_name = $file['tmp_name'];
    $mime = mime_content_type($tmp_name);

    $file_on_disk = null;

    switch($mime){
        case 'application/x-gzip':
            $file_on_disk = uncompress_gz_file($file['tmp_name']);
            break;

        // case 'application/zip':
        //     $zip = new ZipArchive();
        //     $zip->open()
        //     break;
    }

    if(!$file_on_disk){
        throw new \Exception('Could not read log file, currently you must gzip it first');
    }

    if(!is_readable($file_on_disk)){
        throw new \Exception('File not readable');
    }

    $output_file_path = tempnam(sys_get_temp_dir(), 'CLICK_FRAUD_OUTPUT');

    $w = new worker();
    $w->do_work($file_on_disk, $output_file_path);

    send_file($output_file_path);
    @unlink($output_file_path);
    @unlink($file_on_disk);
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Log File Parser</title>
</head>
<body>
    <form method="post" enctype="multipart/form-data">
        <label>
            <span>Log File</span>
            <input type="file" name="log_file" />
        </label>
        <input type="submit" value="Submit" />
    </form>
</body>
</html>
