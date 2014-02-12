<?php
/**
 * FlushCommand class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-consoletools.commands
 */

/**
 * Console command for emptying a set of pre-configured directories.
 */
class FlushCommand extends CConsoleCommand
{
    /**
     * @var array list of directories that should be flushed.
     */
    public $flushPaths = array(
        'protected/runtime',
        'assets',
    );

    /**
     * @var array files to exclude when flushing directories.
     */
    public $exclude = array();

    /**
     * @var string the base path.
     */
    public $basePath;

    /**
     * Initializes the command.
     */
    public function init()
    {
        if (!isset($this->basePath)) {
            $this->basePath = Yii::getPathOfAlias('webroot');
        }
        $this->basePath = rtrim($this->basePath, '/');
    }

    /**
     * Flushes directories.
     */
    protected function flush()
    {
        echo "\nFlushing directories... ";
        foreach ($this->flushPaths as $dir) {
            $path = $this->basePath . '/' . $dir;
            if (file_exists($path)) {
                $this->flushDirectory($path);
            }
            $this->ensureDirectory($path);
        }
        echo "done\n";
    }

    /**
     * Flushes a directory recursively.
     * @param string $path the directory path.
     * @param boolean $delete whether to delete the directory.
     */
    protected function flushDirectory($path, $delete = false)
    {
        if (is_dir($path)) {
            $entries = scandir($path);
            foreach ($entries as $entry) {
                $exclude = array_merge(array('.', '..'), $this->exclude);
                if (in_array($entry, $exclude)) {
                    continue;
                }
                $entryPath = $path . '/' . $entry;
                if (is_dir($entryPath)) {
                    $this->flushDirectory($entryPath, true);
                } else {
                    unlink($entryPath);
                }
            }
            if ($delete) {
                rmdir($path);
            }
        }
    }
} 