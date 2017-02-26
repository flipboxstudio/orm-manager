<?php 

namespace Flipbox\OrmManager\Consoles;

trait Command
{
    /**
     * Prompt the user for input.
     *
     * @param  string  $question
     * @param  string  $default
     * @return string
     */
    public function ask($question, $default = null)
    {
        return parent::ask($this->setTextPrompt($question), $default);
    }

    /**
     * Confirm a question with the user.
     *
     * @param  string  $question
     * @param  bool    $default
     * @return bool
     */
    public function confirm($question, $default = false)
    {
        return parent::confirm($this->setTextPrompt($question), $default);
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * @param  string  $question
     * @param  array   $choices
     * @param  string  $default
     * @param  mixed   $attempts
     * @param  bool    $multiple
     * @return string
     */
    public function choice($question, array $choices, $default = null, $attempts = null, $multiple = null)
    {
        return parent::choice($this->setTextPrompt($question), $choices, $default, $attempts, $multiple);
    }

    /**
     * Text for Prompt
     *
     * @param  string  $text
     * @return string
     */
    public function setTextPrompt($text)
    {
        $prefix = "\033[0m";
        $suffix = "\033[0;32m";

        return "{$prefix}{$text}{$prefix}";
    }

    /**
     * Write success output.
     *
     * @param  string  $string
     * @return void
     */
    public function success($string)
    {
        $string = $this->paintString("{$string}", 'green');

        print("{$string}\n");
    }

    /**
     * Write title output.
     *
     * @param  string  $string
     * @return void
     */
    public function title($string)
    {
        $string = $this->paintString(" {$string} ", 'white', 'blue');

        print("{$string}\n");
    }

    /**
     * Write danger output.
     *
     * @param  string  $string
     * @return void
     */
    public function danger($string)
    {
        $string = $this->paintString(" Error : {$string} ", 'white', 'red', 'italic');

        print("{$string}\n");
    }
    
    /**
     * Write a string as warning output.
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function warn($string, $verbosity = null)
    {
        $string = $this->paintString(" Warning : {$string} ", 'white', 'yellow', 'italic');

        print($string)."\r\n";
    }

}