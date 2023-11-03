<?php

namespace Cooperl\IBMi;

use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use ToolkitApi\Toolkit;

class ToolkitServiceManager
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;
    /**
     * The active connection instances.
     *
     * @var array
     */
    protected array $connections = [];

    /**
     * Create a new toolkit manager instance.
     *
     * @param  Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a toolkit connection instance.
     *
     * @param string|null $name
     * @return Toolkit
     * @throws \Exception
     */
    public function connection(string $name = null): Toolkit
    {
        // If we haven't created this connection, we'll create it based on the config
        // provided in the application. Once we've created the connections we will
        // set the "fetch mode" for PDO which determines the query return types.
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Make the toolkit connection instance.
     *
     * @param string $name
     * @return Toolkit
     * @throws \Exception
     */
    protected function makeConnection(string $name): Toolkit
    {
        $config = $this->getConfig($name);

        $transportType = '';
        $database = $config["database"];
        $username = $config["username"];
        $password = $config["password"];
        $isPersistent = !array_key_exists(\PDO::ATTR_PERSISTENT, $config["options"]) ?: $config["options"][\PDO::ATTR_PERSISTENT];

        switch ($config['driver']) {
            case 'db2_ibmi_odbc':
                $transportType = 'odbc';
                $database = $this->getDsn($config);
                break;
            case 'db2_ibmi_ibm':
                $transportType = 'ibm_db2';
                break;
            case 'db2':
                $transportType = 'pdo';
                $database = DB::connection($name)->getPdo();
                break;
            default:
                break;
        }

        $toolKit = new Toolkit($database, $username, $password, $transportType, $isPersistent);
        $toolKit->setOptions($config["toolkit"]);

        return $toolKit;
    }

    /**
     * Get the configuration for a connection.
     *
     * @param string $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function getConfig(string $name): array
    {
        $name = $name ?: config('database.default');

        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = config('database.connections');

        if (is_null($config = Arr::get($connections, $name))) {
            throw new \InvalidArgumentException("Database [$name] not configured.");
        }

        return $config;
    }

    protected function getDsn(array $config): string
    {
        $dsnParts = [
            'DRIVER=%s',
            'System=%s',
            'Database=%s',
            'UserID=%s',
            'Password=%s',
        ];

        $dsnConfig = [
            $config['driverName'],
            $config['host'],
            $config['database'],
            $config['username'],
            $config['password'],
        ];

        if (array_key_exists('odbc_keywords', $config)) {
            $odbcKeywords = $config['odbc_keywords'];
            unset($odbcKeywords['CCSID']);
            $parts = array_map(function($part) {
                return $part . '=%s';
            }, array_keys($odbcKeywords));
            $config = array_values($odbcKeywords);

            $dsnParts = array_merge($dsnParts, $parts);
            $dsnConfig = array_merge($dsnConfig, $config);
        }

        return sprintf(implode(';', $dsnParts), ...$dsnConfig);
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultConnection(string $name): void
    {
        config(['database.default' => $name]);
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([
            $this->connection(),
            $method,
        ], $parameters);
    }
}
