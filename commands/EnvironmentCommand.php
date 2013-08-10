<?php
/**
 * EnvironmentCommand class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-consoletools.commands
 */

Yii::import('vendor.crisu83.yii-consoletools.commands.FlushCommand');

/**
 * Console command for deploying environments.
 */
class EnvironmentCommand extends FlushCommand
{
    /**
     * @var string the name of the environments directory.
     */
    public $environmentsDir = 'environments';

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

        $this->flush();

        echo "\nCopying environment files... \n";
        $environmentPath = $this->basePath . '/' . $this->environmentsDir . '/' . $id;
        if (!file_exists($environmentPath)) {
            throw new CException(sprintf("Failed to change environment. Unknown environment '%s'!", $id));
        }
        $fileList = $this->buildFileList($environmentPath, $this->basePath);
        $this->copyFiles($fileList);

        echo "\nEnvironment successfully changed to '{$id}'.\n";
    }
}
