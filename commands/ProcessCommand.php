<?php
/**
 * ProcessCommand class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-consoletools.commands
 */

/**
 * Console command for running processes.
 */
abstract class ProcessCommand extends CConsoleCommand
{
    // Default descriptors.
    const DESCRIPTOR_STDIN = 0;
    const DESCRIPTOR_STDOUT = 1;
    const DESCRIPTOR_STDERR = 2;

    /**
     * @var string the base path.
     */
    public $basePath;

    private $_process;
    private $_pipes = array();

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
     * Runs a process.
     * @param string $cmd the command to run.
     * @param array $descriptors the descriptor specification.
     * @param string $cwd the working directory.
     * @param array $env list of environment variables.
     * @param array $options additional options.
     * @throws CException if the process cannot be started.
     */
    public function process($cmd, $descriptors, $cwd = null, $env = null, $options = null)
    {
        $this->beginProcess($cmd, $descriptors, $cwd, $env, $options);
        return $this->endProcess();
    }

    /**
     * Opens a process.
     * @param string $cmd the command to run.
     * @param array $descriptors the descriptor specification.
     * @param string $cwd the working directory.
     * @param array $env list of environment variables.
     * @param array $options additional options.
     * @throws CException if the process cannot be started.
     */
    public function beginProcess($cmd, $descriptors, $cwd = null, $env = null, $options = null)
    {
        if (isset($this->_process)) {
            throw new CException('Failed to start process. Process is already running.');
        }
        echo "Running command: $cmd ... ";
        $this->_process = proc_open($cmd, $descriptors, $this->_pipes, $cwd, $env, $options);
        if (!is_resource($this->_process)) {
            throw new CException('Failed to start process. Process could not be opened.');
        }
    }

    /**
     * Closes all open pipes and ends the current process.
     * @return integer the exit code.
     * @throws CException if the process is not running of if it failed.
     */
    public function endProcess()
    {
        if (isset($this->_process)) {
            $error = $this->getError();
            foreach (array_keys($this->_pipes) as $descriptor) {
                $this->closeResource($descriptor);
            }
            $return = proc_close($this->_process);
            if ($return !== 0) {
                throw new CException(sprintf('Process failed with error "%s"', $error), $return);
            }
            $this->_process = null;
            echo "done\n";
            return $return;
        }
        return 0;
    }

    /**
     * Writes to a specific resource.
     * @param integer $index the resource index in the descriptor specification.
     * @param string $string the string to write.
     * @param integer $length the maximum length.
     * @return boolean
     */
    public function writeToResource($index, $string, $length = null)
    {
        return isset($this->_pipes[$index]) ? fwrite($this->_pipes[$index], $string, $length) : false;
    }

    /**
     * Reads from a specific resource.
     * @param integer $index the resource index in the descriptor specification.
     * @param boolean $close whether the pointer should also be closed.
     * @return string the contents.
     */
    public function readFromResource($descriptor, $close = true)
    {
        if (isset($this->_pipes[$descriptor])) {
            $contents = stream_get_contents($this->_pipes[$descriptor]);
            if ($close) {
                $this->closeResource($descriptor);
            }
            return $contents;
        }
        return false;
    }

    /**
     * Closes a specific resource.
     * @param integer $index the resource index in the descriptor specification.
     * @return boolean whether the pointer was closed successfully.
     */
    public function closeResource($descriptor)
    {
        if (isset($this->_pipes[$descriptor])) {
            $success = fclose($this->_pipes[$descriptor]);
            unset($this->_pipes[$descriptor]);
            return $success;
        }
        
        return false;
    }

    /**
     * Returns the contents from stdout.
     * @return string the contents.
     */
    public function getOutput()
    {
        return $this->readFromResource(self::DESCRIPTOR_STDOUT);
    }

    /**
     * Returns the contents from stderr.
     * @return string the contents.
     */
    public function getError()
    {
        return $this->readFromResource(self::DESCRIPTOR_STDERR);
    }

    /**
     * Returns a specific resource.
     * @param integer $index the resource index in the descriptor specification.
     * @return resource the resource.
     */
    public function getResource($descriptor)
    {
        return isset($this->_pipes[$descriptor]) ? $this->_pipes[$descriptor] : null;
    }
}