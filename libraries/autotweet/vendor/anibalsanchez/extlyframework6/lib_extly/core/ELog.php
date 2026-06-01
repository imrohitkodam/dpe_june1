<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

// No direct access
defined('_JEXEC') || exit('Restricted access');

/**
 * Logger for debugging info.
 *
 * @since       11.1
 */
class ELog
{
    public const LOG_LEVEL_OFF = 0;

    public const LOG_MODE_LOGFILE = 0;
    public const LOG_MODE_LOGFILE_SCREEN = 1;
    public const LOG_MODE_SCREEN = 2;

    public const LOG_FILE = 'xt-logging.log';

    // 0 = off, 1 = errors only, 2 = errors and warnings, 3 = all
    protected $level = 0;

    // 0 = to logfile only, 1 = to logfile and on screen
    protected $mode = 0;

    // Joomla JLog
    protected $logger;

    /**
     * ELog.
     *
     * @param int $log_level Param
     * @param int $log_mode  Param
     */
    public function __construct($log_level = self::LOG_LEVEL_OFF, $log_mode = self::LOG_MODE_LOGFILE)
    {
        $this->level = (int) $log_level;
        $this->mode = (int) $log_mode;

        if (($log_level) && ($this->isFileMode())) {
            $config = [
                'text_file' => self::LOG_FILE,
            ];

            $this->logger = new JLogLoggerFormattedtext($config);
        }
    }

    /**
     * getInstance - JUST FOR COMPATIBILITY.
     *
     * @param int $log_level Param
     * @param int $log_mode  Param
     *
     * @return object
     */
    public static function getInstance($log_level = self::LOG_LEVEL_OFF, $log_mode = self::LOG_MODE_LOGFILE)
    {
        return new self($log_level, $log_mode);
    }

    /**
     * log.
     *
     * @param string $status  Param
     * @param string $comment Param
     * @param object &$data   Param
     *
     * @return object
     */
    public function log($status, $comment, &$data = null)
    {
        $log_result = false;

        if ($this->logThisStatus($status)) {
            if ($data) {
                $comment .= ' - '.print_r($data, true);
            }

            if ($this->isFileMode()) {
                if (empty($this->logger)) {
                    JFactory::getApplication()->enqueueMessage('ELog: Logger not initialized, entry not written to logfile.', 'error');
                } else {
                    $entry = new JLogEntry($comment, $status);
                    $this->logger->addEntry($entry);
                }
            }

            if ($this->isScreenMode()) {
                $message = 'Extly Log: '.htmlspecialchars($comment);
                $this->showMessage($message, $status);
            }
        }

        return $log_result;
    }

    /**
     * isLogging.
     *
     * @return bool
     */
    public function isLogging()
    {
        return $this->level;
    }

    /**
     * isFileMode().
     *
     * @return bool
     */
    public function isFileMode()
    {
        return (self::LOG_MODE_LOGFILE === (int) $this->mode)
                || (self::LOG_MODE_LOGFILE_SCREEN === (int) $this->mode);
    }

    /**
     * isScreenMode().
     *
     * @return bool
     */
    public function isScreenMode()
    {
        return (self::LOG_MODE_SCREEN === (int) $this->mode)
                || (self::LOG_MODE_LOGFILE_SCREEN === (int) $this->mode);
    }

    /**
     * getLoggedFile.
     *
     * @return bool
     */
    public static function getLoggedFile()
    {
        $log_path = JFactory::getConfig()->get('log_path');

        // Build the full path to the log file.
        $path = $log_path.'/'.self::LOG_FILE;

        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * getLoggedUrl.
     *
     * @return string
     */
    public static function getLoggedUrl()
    {
        if ($path = self::getLoggedFile()) {
            return str_replace(JPATH_ROOT.'/', JUri::root(), $path);
        }

        return null;
    }

    /**
     * showMessage.
     *
     * @param string $message Param
     * @param int    $class   Param
     *
     * @return string
     */
    public static function showMessage($message, $class = JLog::ERROR)
    {
        $class = self::getLogClass($class);

        if (defined('EXTLY_CRONJOB_RUNNING')) {
            fwrite(\STDOUT, $class.' - '.$message."\n");
        } else {
            JFactory::getApplication()->enqueueMessage(JText::_($message), $class);
        }
    }

    /**
     * logThisStatus.
     *
     * @param int $status Param
     *
     * @return bool
     */
    protected function logThisStatus($status)
    {
        return ($this->level) && ($status <= (int) $this->level);
    }

    /**
     * getLogClass.
     *
     * @param int $status Param
     *
     * @return string
     */
    protected static function getLogClass($status)
    {
        switch ($status) {
            case JLog::INFO:
                return 'notice';
            case JLog::WARNING:
                return 'warning';
            case JLog::ERROR:
                return 'error';
            default:
                return 'message';
        }
    }
}
