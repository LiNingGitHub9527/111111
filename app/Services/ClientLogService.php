<?php

namespace App\Services;

use Monolog\Logger as Monolog;
use App\Models\Client;
use App\Support\Log\Driver\ClientLogger;

class ClientLogService
{
    protected $levels = [
        'debug' => Monolog::DEBUG,
        'info' => Monolog::INFO,
        'notice' => Monolog::NOTICE,
        'warning' => Monolog::WARNING,
        'error' => Monolog::ERROR,
        'critical' => Monolog::CRITICAL,
        'alert' => Monolog::ALERT,
        'emergency' => Monolog::EMERGENCY,
    ];

    protected $config;

    protected $clientId;

    protected $type;

    protected $formatterName;

    protected static $instances = [];

    protected $drivers = [];

    public static function instance($clientId, $type = 'default', $formatterName = 'default')
    {
        $key = $clientId . '-' . $type . '-' . $formatterName;
        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }
        $instance = new ClientLogService($clientId, $type, $formatterName);
        self::$instances[$key] = $instance;
        return $instance;
    }

    public function __construct($clientId, $type = 'default', $formatterName = 'default')
    {
        $this->clientId = $clientId;
        $this->type = $type;
        $this->formatterName = $formatterName;
        $this->config = config('logging.channels.client');
    }

    public function driver($level = 'info')
    {
        if (!isset($this->levels[$level])) {
            return null;
        }

        if (!isset($this->drivers[$level])) {
            $subPath = $this->clientId . '/' . $this->type;
            $driver = new ClientLogger($subPath, $level, $this->formatterName);
            $driver->prepareDriver($this->config);
            $this->drivers[$level] = $driver;
        }

        return $this->drivers[$level];
    }

    /**
     * System is unusable.
     *
     * @param  string  $message
     * @param  array  $context
     *
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->driver('emergency')->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param  string  $message
     * @param  array  $context
     *
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->driver('alert')->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param  string  $message
     * @param  array  $context
     *
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->driver('critical')->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param  string  $message
     * @param  array  $context
     *
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->driver('error')->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param  string  $message
     * @param  array  $context
     *
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->driver('warning')->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param  string  $message
     * @param  array  $context
     *
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->driver('notice')->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param  string  $message
     * @param  array  $context
     *
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->driver('info')->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param  string  $message
     * @param  array  $context
     *
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->driver('debug')->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param  mixed  $level
     * @param  string  $message
     * @param  array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->driver($level)) {
            return;
        }
        $this->driver($level)->log($level, $message, $context);
    }
}
