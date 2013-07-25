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
            } else {
                if (!is_dir($path)) {
                    $this->createDirectory($path);
                }
            }
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
}
