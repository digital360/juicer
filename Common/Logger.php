<?php

namespace Keboola\Juicer\Common;

use	Keboola\Juicer\Exception\ApplicationException as Exception;
use	Monolog\Logger as Monolog,
	Monolog\Handler\StreamHandler,
	Monolog\Formatter\LineFormatter;

/**
 * Wrapper for Monolog\Logger
 * @see \Monolog\Logger
 */
class Logger
{
	/**
	 * @var Monolog
	 */
	private static $logger = null;
	/**
	 * @var bool
	 */
	private static $strict = true;

	/**
	 *	Ensure the class doesn't get instantinated
	 */
	private function __construct() {}

	public static function setLogger(Monolog $logger)
	{
		self::$logger = $logger;
	}

	/**
	 * @return Monolog
	 */
	public static function getLogger()
	{
		return self::$logger;
	}

	/**
	 * @param string $name
	 * @param bool $debug true|false|null where null uses the default behavior (--debug cli parameter)
	 */
	public static function initLogger($name = '', $debug = null)
	{
		$options = getopt("", ['debug']);
		if (isset($options['debug'])) {
			// Default format with all the info for dev debug
			$formatter = new LineFormatter();
			$debug = true;
		} elseif (!empty($debug)) {
			// Set user debug mode
			$formatter = new LineFormatter("%level_name%: %message% %context% %extra%\n");
		} else {
			// Simple message (TODO add user readable $context)
			$formatter = new LineFormatter("%message%\n");
		}

		$level = $debug ? Monolog::DEBUG : Monolog::INFO;

		$handler = new StreamHandler('php://stdout', $level);
		$handler->setFormatter($formatter);

		self::$logger = new Monolog($name, [$handler]);
	}

	/**
	 * Set whether to fail or ignore the logging when no logger is set.
	 * @param bool $bool
	 */
	public static function setStrict($bool)
	{
		self::$strict = (bool) $bool;
	}

	/**
	 * @see \Monolog\Logger::log()
	 *
	 * @param string $level [debug,info,notice,warning,error,critical,alert,emergency]
	 * @param string $message
	 * @param array $context
	 * @return bool
	 */
	public static function log($level, $message, array $context = array())
	{
		if (self::$logger == null) {
			if (self::$strict) {
				$e = new Exception("Logger has not been set!");
				$e->setData(array(
					"level" => $level,
					"message" => $message,
					"context" => $context
				));
				throw $e;
			} else {
				return false;
			}
		}

		return self::$logger->log($level, $message, $context);
	}
}
