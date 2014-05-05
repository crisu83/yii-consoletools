<?php
/**
 * MysqldumpCommand class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-consoletools.commands
 */

/**
 * Command for running mysqldump and save the output into a file for later use.
 */
class MysqldumpCommand extends ProcessCommand
{
    /**
     * @var string the path to the mysqldump binary.
     */
    public $binPath;
    /**
     * @var string the path to the directory where the dump-file should be created.
     */
    public $dumpPath = 'protected/data';
    /**
     * @var string the name of the dump-file.
     */
    public $dumpFile = 'dump.sql';
    /**
     * @var array the options for mysqldump.
     * @see http://dev.mysql.com/doc/refman/5.1/en/mysqldump.html
     */
    public $options = array();
    /**
     * @var string the component ID for the database connection to use.
     */
    public $connectionID = 'db';

    private $_db;

    /**
     * Initializes the command.
     */
    public function init()
    {
        parent::init();
        $db = $this->getDb();
        if (!isset($this->options['user'])) {
            $this->options['user'] = $db->username;
        }
        if (!isset($this->options['password'])) {
            $this->options['password'] = $db->password;
        }
        if (!isset($this->options['host'])) {
            $parsed = $this->parseConnectionString($db->connectionString);
            $this->options['host'] = $parsed['hostName'];
        }
    }

    /**
     * Reversing $this->connectionString = $this->driverName.':host='.$this->hostName.';dbname='.$this->dbName;
     * Ugly but will have to do in short of better options (http://www.yiiframework.com/forum/index.php/topic/7984-where-to-get-the-name-of-the-database/)
     * @param $connectionString
     */
    protected function parseConnectionString($connectionString)
    {
        $parsed = array();
        $_ = explode(":", $connectionString, 2);
        $parsed["driverName"] = $_[0];
        $__ = explode(";", $_[1]);
        foreach ($__ as $v) {
            $___ = explode("=", $v);
            $parsed[$___[0]] = $___[1];
        }
        // For staying true to the original variable names
        $parsed["hostName"] = $parsed["host"];
        $parsed["dbName"] = $parsed["dbname"];
        return $parsed;
    }

    /**
     * Runs the command.
     * @param array $args the command-line arguments.
     * @return integer the return code.
     * @throws CException if the mysqldump binary cannot be located or if the actual dump fails.
     */
    public function run($args)
    {
        list($action, $options, $args) = $this->resolveRequest($args);

        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        $binPath = $this->resolveBinPath();
        $options = $this->normalizeOptions($this->options);
        $database = $this->resolveDatabaseName();
        $dumpPath = $this->resolveDumpPath();

        return $this->process(
            "$binPath $options $database",
            array(
                self::DESCRIPTOR_STDIN  => array('pipe', 'r'),
                self::DESCRIPTOR_STDOUT => array('file', $dumpPath, 'w'),
                self::DESCRIPTOR_STDERR => array('pipe', 'w'),
            )
        );
    }

    /**
     * Returns the path to the mysqldump binary file.
     * @return string the path.
     */
    protected function resolveBinPath()
    {
        return isset($this->binPath) ? $this->binPath : 'mysqldump';
    }

    /**
     * Returns the name of the database.
     * @return string the name.
     */
    protected function resolveDatabaseName()
    {
        return $this->getDb()->createCommand('SELECT DATABASE();')->queryScalar();
    }

    /**
     * Returns the path to the dump-file.
     * @return string the path.
     */
    protected function resolveDumpPath()
    {
        $path = $this->basePath . '/' . $this->dumpPath;
        $this->ensureDirectory($path);
        return realpath($path) . '/' . $this->dumpFile;
    }

    /**
     * Normalizes the given options to a string
     * @param array $options the options.
     * @return string the options.
     */
    protected function normalizeOptions($options)
    {
        $result = array();
        foreach ($options as $name => $value) {
            $result[] = "--$name=\"$value\"";
        }
        return implode(' ', $result);
    }

    /**
     * Returns the database connection component.
     * @return CDbConnection the component.
     * @throws CException if the component is not found.
     */
    protected function getDb()
    {
        if (isset($this->_db)) {
            return $this->_db;
        } else {
            if (($db = Yii::app()->getComponent($this->connectionID)) === null) {
                throw new CException(sprintf(
                    'Failed to get database connection. Component %s not found.',
                    $this->connectionID
                ));
            }
            return $this->_db = $db;
        }
    }
}
