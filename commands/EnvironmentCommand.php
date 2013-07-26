<?php
/**
 * EnvironmentCommand class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-deploymenttools.commands
 */

/**
 * Console command for deploying environments.
 */
class EnvironmentCommand extends CConsoleCommand
{
    /**
     * @var string the name of the environments directory.
     */
    public $environmentsDir = 'environments';
    /**
     * @var array list of directories that should be flushed.
     */
    public $flushPaths = array(
        'protected/runtime',
        'assets',
    );
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
     * Provides the command description.
     * @return string the command description.
     */
    public function getHelp()
    {
        return <<<EOD
USAGE
  yiic environment <id>

DESCRIPTION
  Activates a specific environment by flushing the necessary directories and copying the environment specific files into the application.

EXAMPLES
  * yiic environment dev
    Activates the "dev" environment.
EOD;
    }

    /**
     * Changes the current environment.
     * @param array $args the command-line arguments.
     * @throws CException if the environment path does not exist.
     */
    public function run($args)
    {
        if (!isset($args[0])) {
            $this->usageError('The environment id is not specified.');
        }
        $id = $args[0];

        echo "\nFlushing directories... ";
        foreach ($this->flushPaths as $dir) {
            $path = $this->basePath . '/' . $dir;
            if (file_exists($path)) {
                $this->flushDirectory($path);
            }
            $this->ensureDirectory($path);
        }
        echo "done\n";

        echo "\nCopying environment files... \n";
        $environmentPath = $this->basePath . '/' . $this->environmentsDir . '/' . $id;
        if (!file_exists($environmentPath)) {
            throw new CException(sprintf("Failed to change environment. Unknown environment '%s'!", $id));
        }
        $fileList = $this->buildFileList($environmentPath, $this->basePath);
        $this->copyFiles($fileList);

        echo "\nEnvironment successfully changed to '{$id}'.\n";
    }

    /**
     * Flushes a directory recursively.
     * @param string $path the directory path.
     * @param boolean $delete whether to delete the directory.
     */
    protected function flushDirectory($path, $delete = false)
    {
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (strpos($file, '.') !== 0) {
                    $filePath = $path . '/' . $file;
                    if (is_dir($filePath)) {
                        $this->flushDirectory($filePath, true);
                    } else {
                        unlink($filePath);
                    }
                }
            }
            if ($delete) {
                rmdir($path);
            }
        }
    }
}
