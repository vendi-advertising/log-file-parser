<?php declare(strict_types=1);
namespace Vendi\LogParser;

use League\Csv\Writer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\PathUtil\Path;

class parser_command extends Command
{
    protected function configure()
    {
        $this
            ->setName('parser')
            ->setDescription('Parse a log file into CSV')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'What file would you like to parse?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->write(sprintf("\033\143"));
        $io->title('Vendi Log File Parser');
        $io->text(
                    [
                        'Welcome to the Vendi Log File Parser!!',
                        'This parser assumes that you are using the vhosts pattern with TLS info for your access logs. If you don\'t know what that is, ask Chris.',
                    ]
            );

        $file_path = $input->getOption('file');
        if ($file_path) {
            $file_path = Path::makeAbsolute($file_path, VENDI_LOG_FILE_PARSER_PATH);
        }
        while (! $file_path || ! is_file($file_path)) {
            $file_path = $io->ask('What file would you like to parse?');
            $file_path = Path::makeAbsolute($file_path, VENDI_LOG_FILE_PARSER_PATH);
        }



        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $io->error('Could not open file, maybe check permissions?');
            return 1;
        }

        $writer = Writer::createFromPath($file_path . '.csv', 'w+');
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

            if (!$headers_written) {
                $writer->insertOne(array_keys($data));
                $headers_written = true;
            }
            $writer->insertOne($data);
        }

        fclose($handle);

        $io->text('Well, that\'s about it!');
    }
}
