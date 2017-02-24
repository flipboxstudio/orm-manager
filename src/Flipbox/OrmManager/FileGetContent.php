<?php 

namespace Flipbox\OrmManager;

class FileGetContent
{
    /**
     * file
     *
     * @var string
     */
    protected $file;

    /**
     * Create a new FileGetContent instance.
     *
     * @param string $filePath
     * @return void
     */
    public function __construct($filePath=null)
    {
        $this->file = $filePath;
    }

    /**
     * set file
     *
     * @param string $filePath
     * @return $this
     */
    public function setFile($filePath)
    {
        $this->file = $filePath;

        return $this;
    }

    /**
     * get classes of file
     *
     * @param string $filePath
     * @return array
     */
    public function getClasses($filePath=null)
    {
        $phpCode = file_get_contents($filePath ?: $this->file);
        $tokens = token_get_all($phpCode);
        $count = count($tokens);
        $classes = [];

        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS
                AND $tokens[$i - 1][0] == T_WHITESPACE
                AND $tokens[$i][0] == T_STRING) {
                    $classes[] = $tokens[$i][1];
            }
        }

        return $classes;
    }

    /**
     * get classes of file
     *
     * @param string $filePath
     * @return mixed
     */
    public function getNamespace($filePath=null)
    {
        $phpCode = file_get_contents($filePath ?: $this->file);
        $tokens = token_get_all($phpCode);
        $count = count($tokens);
        
        for ($i = 1; $i < $count; $i++) {
            if ($tokens[$i - 1][0] == T_NAMESPACE
                AND $tokens[$i][0] == T_WHITESPACE) {
                $namespaces = [];
                for ($j=($i+1); $j < $count; $j++) {
                    if (! is_array($tokens[$j])) break;
                    if ($tokens[$j][0] == T_STRING) {
                        $namespaces[] = $tokens[$j][1];
                    }
                }

                return implode('\\', $namespaces);
            }
        }

        return null;
    }
}
