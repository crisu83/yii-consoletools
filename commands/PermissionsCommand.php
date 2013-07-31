<?php
/**
 * PermissionsCommand class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-consoletools.commands
 */

/**
 * Console command for changing directory permissions and ownership.
 */
class PermissionsCommand extends CConsoleCommand
{
    /**
     * @var array list of permission configurations (path => config).
     */
    public $permissions = array(
        'protected/runtime' => array('mode' => 0777),
        'protected/yiic' => array('mode' => 0755),
        'assets' => array('mode' => 0777),
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
  yiic permissions

DESCRIPTION
  Sets the correct permissions for files and directories.

EXAMPLES
  * yiic permissions
    Sets the permissions.
EOD;
    }

    /**
     * Runs the command.
     * @param array $args the command-line arguments.
     * @return integer the return code.
     */
    public function run($args)
    {
        foreach ($this->permissions as $dir => $config) {
            $path = $this->basePath . '/' . $dir;
            if (file_exists($path)) {
                if (isset($config['user'])) {
                    $this->changeOwner($path, $config['user']);
                }
                if (isset($config['group'])) {
                    $this->changeGroup($path, $config['group']);
                }
                if (isset($config['mode'])) {
                    $this->changeMode($path, $config['mode']);
                }
            } else {
                echo sprintf("Failed to change permissions for %s. File does not exist!\n", $path);
            }
        }
        echo "Permissions successfully changed.\n";
        return 0;
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
     * Changes the mode for a directory.
     * @param string $path the directory path.
     * @param integer $mode the mode.
     */
    protected function changeMode($path, $mode)
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
