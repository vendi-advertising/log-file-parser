<?php declare(strict_types=1);
namespace Vendi\LogParser;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        //(?<domain>[^\s]+) (?<client_ip>[\d\.]+) \- (?<remote_user>[^\s]+) \[(?<datetime>.*?)\] "(?<method>GET|HEAD|POST) (?<path>.*?) (?<http_version>HTTP/[\d\.]+)" (?<http_status_code>\d+) (?<http_bytes>\d+) "(?<http_referer>[^"]+)" "(?<http_user_agent>[^"]+)" (?<tls_version>[^\s]+) (?<tls_cipher>[^\s]+) (?<connecting_ip>[\d\.]+)

        $io = new SymfonyStyle($input, $output);
        $io->write(sprintf("\033\143"));
        $io->title('Vendi Log File Parser');
        $io->text(
                    [
                        'Welcome to the Vendi Log File Parser!!',
                        'This parser assumes that you are using the vhosts pattern with TLS info for your access logs. If you don\'t know what that is, ask Chris.',
                    ]
            );

        $file_path = null;
        while (null === $file_path || ! is_file($file_path)) {
            $file_path = $io->ask('What file would you like to parse?');
            $file_path = Path::makeAbsolute($file_path, VENDI_LOG_FILE_PARSER_PATH);
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $io->error('Could not open file, maybe check permissions?');
            return 1;
        }

        while (($line = fgets($handle)) !== false) {
            // process the line read.
        }

        fclose($handle);

        $io->text('Well, that\'s about it!');
    }
}
