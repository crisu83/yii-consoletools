<?php
/**
 * MaintainCommand class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-consoletools.commands
 */

/**
 * Console command for enabling and disabling maintenance mode.
 */
class MaintainCommand extends CConsoleCommand
{
    /**
     * @var string the default action.
     */
    public $defaultAction = 'down';

    /**
     * Provides the command description.
     * @return string the command description.
     */
    public function getHelp()
    {
        return <<<EOD
USAGE
  yiic maintain <action>

DESCRIPTION
  Console command for enabling and disabling maintenance mode.

EXAMPLES
 * yiic maintain down
   Enables the maintenance mode.
 * yiic maintain up
   Disabled the maintenance mode.
EOD;
    }

    /**
     * Disables the maintenance mode.
     */
    public function actionUp()
    {
        echo "\nDisabling maintenance mode... ";
        $file = $this->resolveFile();
        if (file_exists($file)) {
            unlink($file);
            echo "done\n";
        } else {
            echo "failed\n";
            echo "Application is not in maintenance mode.\n";
        }
    }

    /**
     * Enables the maintenance mode.
     */
    public function actionDown()
    {
        echo "\nEnabling maintenance mode... ";
        $file = $this->resolveFile();
        if (!file_exists($file)) {
            file_put_contents($file, time());
            echo "done\n";
        } else {
            echo "failed\n";
            echo "Application is already in maintenance mode.\n";
        }
    }

    /**
     * Returns the path to the 'maintain' file.
     * @return string the path.
     */
    protected function resolveFile()
    {
        return Yii::app()->getRuntimePath() . DIRECTORY_SEPARATOR . 'maintain';
    }
} 