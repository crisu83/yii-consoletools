<?php
/**
 * EnvironmentCommand class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-deploymenttools.commands
 */

Yii::import('vendor.crisu83.yii-deploymenttools.commands.DeploymentCommand');

/**
 * Console command for deploying environments.
 */
class EnvironmentCommand extends DeploymentCommand
{
    /**
     * @var string the default action.
     */
    public $defaultAction = 'change';
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
  Activates a specific environment by flushing the necessary directories and copying the environment specific files into the application.

EXAMPLES
  * yiic environment [change] prod
    Activates the "prod" environment.
  * yiic environment create prod
    Creates a "prod" environment.
EOD;
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
            $path = $this->basePath . '/' . $dir;
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

        echo "Environment successfully changed to '{$id}'.\n";
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
