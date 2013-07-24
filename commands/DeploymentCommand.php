<?php
/**
 * DeploymentCommand class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-deploymenttools.commands
 */

/**
 * Base class for all deployment commands.
 */
abstract class DeploymentCommand extends CConsoleCommand
{
    /**
     * @var string the base path.
     */
    public $basePath;

    /**
     * Initializes the command.
     */
    public function init()
    {
        parent::init();
        if (!isset($this->basePath)) {
            $this->basePath = Yii::getPathOfAlias('webroot');
        }
        $this->basePath = rtrim($this->basePath, '/');
    }

    /**
     * Creates a directory recursively.
     * @param string $path the directory path.
     * @param integer $mode the permission mode (default to 0777).
     * @param boolean $recursive whether to create the directory recursively.
     */
    protected function createDirectory($path, $mode = 0777, $recursive = true)
    {
        if (!is_dir($path)) {
            mkdir($path, $mode, $recursive);
        }
    }

    /**
     * Deletes a directory recursively.
     * @param string $path the directory path.
     * @param boolean $flushOnly whether to keep the root directory.
     */
    protected function deleteDirectory($path, $flushOnly = false)
    {
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (strpos($file, '.') !== 0) {
                    $filePath = $path . '/' . $file;
                    if (is_dir($filePath)) {
                        $this->deleteDirectory($filePath);
                    } else {
                        unlink($filePath);
                    }
                }
            }
            if (!$flushOnly) {
                rmdir($path);
            }
        }
    }

    /**
     * Copies one directory to another recursively.
     * @param string $source the source directory path.
     * @param string $destination the destination directory path.
     */
    protected function copyDirectory($source, $destination)
    {
        if (is_dir($source)) {
            $handle = opendir($source);
            while (($file = readdir($handle)) !== false) {
                if (strpos($file, '.') !== 0) {
                    $sourcePath = $source . '/' . $file;
                    $destinationPath = $destination . '/' . $file;
                    if (is_dir($sourcePath)) {
                        $this->createDirectory($destinationPath);
                        $this->copyDirectory($sourcePath, $destinationPath);
                    } else {
                        copy($sourcePath, $destinationPath);
                    }
                }
            }
            closedir($handle);
        } else {
            copy($source, $destination);
        }
    }
}