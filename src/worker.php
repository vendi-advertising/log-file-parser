<?php

declare(strict_types=1);

namespace Vendi\LogParser;

use League\Csv\Writer;
use Webmozart\PathUtil\Path;

class worker
{
    public function do_work(string $input_file_path, string $output_file_path)
    {
        $handle = fopen($input_file_path, 'r');
        if (!$handle) {
            throw new \Exception('Could not open file, maybe check permissions?');
        }

        $export_file = $input_file_path . '.csv';

        $writer = Writer::createFromPath($export_file . '.csv', 'w+');
        $headers_written = false;
        while (true) {
            $line = fgets($handle);
            if (false===$line) {
                break;
            }
            $data = line_data::from_string($line);
            if (!$data) {
                continue;
            }

            if ('GET'!==$data['http_method']) {
                continue;
            }

            $skip_paths = [
                            '/wp-content/plugins/',
                            '/wp-json/',
                            '/wp-login.php',
                            '/wp-includes/',
                        ];

            $skip = false;
            foreach ($skip_paths as $path) {
                if (0===mb_strpos($data['http_request'], $path)) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            $only_paths_parts = [
                            'utm_source'
            ];

            $skip = true;
            foreach ($only_paths_parts as $part) {
                if (false !== mb_strpos($data['http_request'], $part)) {
                    $skip = false;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            //Get the Google codes into dedicated fields
            $query_string = parse_url($data['http_request'], \PHP_URL_QUERY);
            if($query_string){
                parse_str($query_string, $qs_parts);
                foreach(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'gclid'] as $key){
                    if(array_key_exists($key, $qs_parts)){
                        $data[$key] = $qs_parts[$key];
                    }
                }
            }

            if (!$headers_written) {
                $writer->insertOne(array_keys($data));
                $headers_written = true;
            }
            $writer->insertOne($data);
        }

        fclose($handle);
    }
}
