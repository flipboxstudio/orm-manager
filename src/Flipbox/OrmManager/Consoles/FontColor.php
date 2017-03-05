<?php 

namespace Flipbox\OrmManager\Consoles;

trait FontColor
{
    /**
     * foreground color
     *
     * @var array
     */
    protected $foregroundColors = [
        'black'        => '0;30', 'dark_gray'    => '1;30',
        'blue'         => '0;34', 'light_blue'   => '1;34',
        'green'        => '0;32', 'light_green'  => '1;32',
        'cyan'         => '0;36', 'light_cyan'   => '1;36',
        'red'          => '0;31', 'light_red'    => '1;31',
        'purple'       => '0;35', 'light_purple' => '1;35',
        'brown'        => '0;33', 'yellow'       => '1;33',
        'light_gray'   => '0;37', 'white'        => '1;37',
        'normal'       => '0;39',
    ];

    /**
     * background color
     *
     * @var array
     */
    protected $backgroundColors = [
        'black'        => '40',   'red'          => '41',
        'green'        => '42',   'yellow'       => '43',
        'blue'         => '44',   'magenta'      => '45',
        'cyan'         => '46',   'light_gray'   => '47',
    ];

    /**
     * options style
     *
     * @var array
     */
    protected $options = [
        'bold'         => '1',    'dim'          => '2',
        'italic'       => '3',    'underline'    => '4',
        'reverse'      => '7',
    ];

    /**
     * paint string
     *
     * @param string $string
     * @param mixed $foregroundColor
     * @param mixed $backgroundCcolor
     * @return string
     */
    public function paintString($string, $foregroundColor = null, $backgroundCcolor = null, $option=null)
    {
        $coloredString = "";

        if (isset($this->foregroundColors[$foregroundColor])) {
            $coloredString .= "\033[".$this->foregroundColors[$foregroundColor]."m";
        }

        if (isset($this->backgroundColors[$backgroundCcolor])) {
            $coloredString .= "\033[".$this->backgroundColors[$backgroundCcolor]."m";
        }

        if (isset($this->options[$option])) {
            $coloredString .= "\033[".$this->options[$option]."m";
        }

        return $coloredString.$string."\033[0m";
    }
}
