<?php

define( 'VENDI_LOG_FILE_PARSER_FILE', __FILE__ );
define( 'VENDI_LOG_FILE_PARSER_PATH', __DIR__ );
define( 'VENDI_LOG_FILE_PARSER_APP_VERSION', '1.0.0' );

require VENDI_LOG_FILE_PARSER_PATH . '/includes/autoload.php';

$parser_command = new Vendi\LogParser\parser_command();

$application = new Symfony\Component\Console\Application( 'Vendi Log File Parser', '1.0.0' );
$application->add( $parser_command );
$application->setDefaultCommand( $parser_command->getName() );
$application->run();
