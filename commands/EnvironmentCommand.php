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
     * @var string the default action.
     */
    public $defaultAction = 'change';
    /**
     * @var string the base path.
     */
    public $basePath;
    /**
     * @var array list of permission configurations (path => config).
     */
    public $permissions = array(
        'protected/runtime' => array('mode' => 0777),
        'protected/yiic' => array('mode' => 0755),
        'assets' => array('mode' => 0777),
    );
    /**
     * @var array list of directories that should be flushed.
     */
    public $flushPaths = array(
        'protected/runtime',
        'assets',
    );

    /**
     * Provides the command description.
     * @return string the command description.
     */
    public function getHelp()
    {
        return <<<EOD
USAGE
  yiic environment <action> <options>

DESCRIPTION
  Activates a specific environment by flushing the necessary directories, copying the environment specific files into the application and changing file permissions if necessary.

EXAMPLES
  * yiic environment [change] prod
    Activates the "prod" environment.
  * yiic environment create prod
    Creates a "prod" environment.
EOD;
    }

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
     * Creates an environment.
     * @param array $args the command-line arguments.
     */
    public function actionCreate($args)
    {
        // todo: write this...
    }

    /**
     * Changes the current environment.
     * @param array $args the command-line arguments.
     * @throws CException if the environment path does not exist.
     */
    public function actionChange($args)
    {
        if (!isset($args[0])) {
            $this->usageError('The environment id is not specified.');
        }

        $id = $args[0];
        $environmentPath = $this->basePath . '/environments/' . $id;

        echo "\nFlushing directories... ";
        foreach ($this->flushPaths as $dir) {
            $path = realpath($this->basePath . '/' . $dir);
            if (file_exists($path)) {
                $this->deleteDirectory($path, true);
            }
            $this->createDirectory($path);
        }
        echo "done\n";

        echo "Copying environment files... ";
        if (!file_exists($environmentPath)) {
            throw new CException(sprintf("Failed to change environment. Unknown environment '%s'!", $id));
        }
        $this->copyDirectory($environmentPath, $this->basePath);
        echo "done\n";

        foreach ($this->permissions as $dir => $config) {
            $path = realpath($this->basePath . '/' . $dir);
            if (file_exists($path)) {
                if (isset($config['user'])) {
                    $this->changeOwner($path, $config['user']);
                }
                if (isset($config['group'])) {
                    $this->changeGroup($path, $config['group']);
                }
                if (isset($config['mode'])) {
                    $this->changePermission($path, $config['mode']);
                }
            } else {
                echo sprintf("Failed to change permissions for %s. File does not exist!", $path);
            }
        }

        echo "Environment successfully changed to '{$id}'.\n";
    }

    /**
     * Creates a directory recursively.
     * @param string $path the directory path.
     * @param integer $mode the permission mode (default to 0777).
     * @param boolean $recursive whether to create the directory recursively.
     * @return boolean the result.
     */
    protected function createDirectory($path, $mode = 0777, $recursive = true)
    {
        return !file_exists($path) ? mkdir($path, $mode, $recursive) : true;
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

    /**
     * Changes the owner for a directory.
     * @param string $path the directory path.
     * @param string $newOwner the name of the new owner.
     */
    protected function changeOwner($path, $newOwner)
    {
        $ownerUid = fileowner($path);
        $ownerData = posix_getpwuid($ownerUid);
        $oldOwner = $ownerData['name'];
        if ($oldOwner !== $this->user) {
            echo sprintf("Changing owner for %s (%s => %s)... ", $path, $oldOwner, $newOwner);
            chown($path, $newOwner);
            echo "done\n";
        }
    }

    /**
     * Changes the group for a directory.
     * @param string $path the directory path.
     * @param string $newGroup the name of the new group.
     */
    protected function changeGroup($path, $newGroup)
    {
        $groupGid = filegroup($path);
        $groupData = posix_getgrgid($groupGid);
        $oldGroup = $groupData['name'];
        if ($oldGroup !== $newGroup) {
            echo sprintf("Changing group for %s (%s => %s)... ", $path, $oldGroup, $newGroup);
            chgrp($path, $newGroup);
            echo "done\n";
        }
    }

    /**
     * Changes the permissions for a directory.
     * @param string $path the directory path.
     * @param integer $mode the permission.
     */
    protected function changePermission($path, $mode)
    {
        $oldPermission = substr(sprintf('%o', fileperms($path)), -4);
        $newPermission = sprintf('%04o', $mode);
        if ($oldPermission !== $newPermission) {
            echo sprintf("Changing mode for %s (%s => %s)... ", $path, $oldPermission, $newPermission);
            chmod($path, $mode);
            echo "done\n";
        }
    }
}
