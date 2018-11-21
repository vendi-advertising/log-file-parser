<?php

declare(strict_types=1);

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
            ->addOption('file',     null, InputOption::VALUE_REQUIRED, 'What file would you like to parse?')
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

        $output_file_path = $file_path . '.csv';
        $w = new worker();
        $w->do_work($file_path, $output_file_path);

        $io->text('Well, that\'s about it!');
    }
}
