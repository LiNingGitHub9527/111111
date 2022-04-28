<?php

namespace App\Support\Log\Driver;

use Psr\Log\LoggerInterface;
use Monolog\Logger as Monolog;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Illuminate\Log\ParsesLogConfiguration;
use App\Support\Log\Formatter\ApiFormatter;
use App\Support\Log\Formatter\DefaultFormatter;

class ClientLogger implements LoggerInterface
{
    use ParsesLogConfiguration;

    protected $subPath;

    protected $levelName;

    protected $formatterName;

    protected $driver;

    public function __construct($subPath = '', $levelName = 'info', $formatterName = 'default')
    {
        $this->subPath = $subPath;
        $this->levelName = $levelName;
        $this->formatterName = $formatterName;
    }

    /**
     * Create a custom logger instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function prepareDriver(array $config)
    {
        if ($this->driver) {
            return $this->driver;
        }

        $path = $config['path'];
        if (!empty($this->subPath)) {
            $path .= '/' . $this->subPath;
        }
        $path .= '/' . $this->levelName . '.log';

        $this->driver = new Monolog($this->parseChannel($config), [
            $this->prepareHandler(new RotatingFileHandler(
                $path, $config['days'] ?? 30, $this->level(['level' => $this->levelName]),
                $config['bubble'] ?? true, $config['permission'] ?? null, $config['locking'] ?? false
            ), $config),
        ]);

        return $this->driver;
    }

    public function __invoke(array $config)
    {
        return $this->prepareDriver($config);
    }

    /**
     * Get fallback log channel name.
     *
     * @return string
     */
    protected function getFallbackChannelName()
    {
        return 'client';
    }

    /**
     * Prepare the handler for usage by Monolog.
     *
     * @param  \Monolog\Handler\HandlerInterface  $handler
     * @param  array  $config
     * @return \Monolog\Handler\HandlerInterface
     */
    protected function prepareHandler(HandlerInterface $handler, array $config = [])
    {
        $hasFormatter = false;
        $formatterName = '';
        $formatterWith = [];
        if (isset($config['formatters']) && !empty($config['formatters'])) {
            if (isset($config['formatters'][$this->formatterName])) {
                $formatter = $config['formatters'][$this->formatterName];
                if (isset($formatter['formatter'])) {
                    $hasFormatter = true;
                    $formatterName = $formatter['formatter'];
                }
                if (isset($formatter['with'])) {
                    $formatterWith = $formatter['with'] ?? [];
                }
            }
        }

        if ($hasFormatter) {
            $handler->setFormatter(app()->make($formatterName, $formatterWith));
        } else {
            $handler->setFormatter($this->formatter());
        }

        return $handler;
    }

    /**
     * Get a Monolog formatter instance.
     *
     * @return \Monolog\Formatter\FormatterInterface
     */
    protected function formatter()
    {
        return tap(new DefaultFormatter(null, null, false, true), function ($formatter) {
            $formatter->includeStacktraces();
        });
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
        $this->log('emergency', $message, $context);
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
        $this->log('alert', $message, $context);
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
        $this->log('critical', $message, $context);
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
        $this->log('error', $message, $context);
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
        $this->log('warning', $message, $context);
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
        $this->log('notice', $message, $context);
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
        $this->log('info', $message, $context);
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
        $this->log('debug', $message, $context);
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
        if (!$this->driver) {
            return;
        }
        if (is_array($message)) {
            $message = json_encode($message);
        }
        $this->driver->log($level, $message, $context);
    }
}
