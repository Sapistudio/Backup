<?php
namespace SapiStudio\Backup\Console;

use Illuminate\Support\Str;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class Base
{
    protected static $lineLength    = 100;
    protected static $defaultName   = 'base';
    protected static $input;
    protected static $output;
    protected static $progressBar;
    protected static $formatter;
    protected static $helperSet     = null;
    
    /** Base::createConsole()*/
    public static function createConsole(){
        self::$input        = new ArgvInput();
        self::$output       = new ConsoleOutput();
        self::$helperSet    = new HelperSet([new FormatterHelper(),new DebugFormatterHelper(),new ProcessHelper(),new QuestionHelper(),]);
        self::$formatter    = self::$helperSet->get('formatter');
        return new static();
    }
    
    /** Base::startProgressBar() */
    public function startProgressBar($total = 100){
        self::$progressBar = new ProgressBar(self::$output, $total);
        self::$progressBar->setFormat("%message%\n");
        self::$progressBar->start();
    }
    
    /** Base::updateProgressBar()*/
    public function updateProgressBar($message = null){
        self::$progressBar->advance(1);
        $this->setMessageProgressBar($message);
    }
    
    /** Base::setMessageProgressBar()*/
    public function setMessageProgressBar($message = null){
        if($message)
            self::$progressBar->setMessage(self::$formatter->truncate($message, self::$lineLength));
    }
    
    /** Base::outputTable()*/
    public function outputTable($rows)
    {
        return (!$rows) ? false : (new Table(self::$output))->setHeaders(array_keys($rows[0]))->setRows($rows)->render();
    }
    
    /** Base::warn()*/
    public function warn($string, $verbosity = null)
    {
        if (!self::$output->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');
            self::$output->getFormatter()->setStyle('warning', $style);
        }
        $this->line($string, 'warning', $verbosity);
    }

    /** Base::alert() */
    public function alert($string)
    {
        $length     = Str::length(strip_tags($string)) + 12;
        $this->comment(str_repeat('*', $length));
        $this->comment('*     '.$string.'     *');
        $this->comment(str_repeat('*', $length));
        self::$output->newLine();
    }
    
    /** Base::error()*/
    public function error($string, $verbosity = null)
    {
        $this->line($string, 'error', $verbosity);
    }
    
    /** Base::question() */
    public function question($string, $verbosity = null)
    {
        $this->line($string, 'question', $verbosity);
    }
    
    /** Base::comment()*/
    public function comment($string, $verbosity = null)
    {
        $this->line($string, 'comment', $verbosity);
    }
    
    /** Base::info()*/
    public function info($string, $verbosity = null)
    {
        $this->line($string, 'info', $verbosity);
    }
    
    /** Base::line()*/
    public function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;
        self::$output->writeln($styled);
    }
}
