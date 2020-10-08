<?php
namespace SapiStudio\Backup\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class Base extends Command
{
    protected static $defaultName = 'base';
    protected static $input;
    protected static $output;
    
    public static function createApp(){
        $command            = new static();
        $application  = new Application();
        $application->add($command);
        $application->setDefaultCommand($command->getName());
        $application->setAutoExit(false);
        $application->run();
        return new static();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        self::$input    = $input;
        self::$output   = $output;
    }
    
    public function outputTable($rows)
    {
        return (!$rows) ? false : (new Table(self::$output))->setHeaders(array_keys($rows[0]))->setRows($rows)->render();
    }
    
    public function warn($string, $verbosity = null)
    {
        if (! self::$output->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');
            self::$output->getFormatter()->setStyle('warning', $style);
        }
        $this->line($string, 'warning', $verbosity);
    }

    public function alert($string)
    {
        $length = Str::length(strip_tags($string)) + 12;

        $this->comment(str_repeat('*', $length));
        $this->comment('*     '.$string.'     *');
        $this->comment(str_repeat('*', $length));

        self::$output->newLine();
    }
    
    public function error($string, $verbosity = null)
    {
        $this->line($string, 'error', $verbosity);
    }
    
    public function question($string, $verbosity = null)
    {
        $this->line($string, 'question', $verbosity);
    }
    
    public function comment($string, $verbosity = null)
    {
        $this->line($string, 'comment', $verbosity);
    }
    
    public function info($string, $verbosity = null)
    {
        $this->line($string, 'info', $verbosity);
    }
    
    public function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        self::$output->writeln($styled);
    }
}