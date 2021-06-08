<?php

/**
 * 1961-2016 BNP Paribas
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to modules@quadra-informatique.fr so we can send you a copy immediately.
 *
 *  @author    Quadra Informatique <modules@quadra-informatique.fr>
 *  @copyright 1961-2016 BNP Paribas
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
class Computop_Logger {

    const LOG_DEBUG = 0;
    const FILE_DEBUG = 'debug.log';
    const LOG_INFO = 1;
    const FILE_INFO = 'information.log';
    const LOG_WARNING = 2;
    const LOG_ERROR = 3;
    const LOG_CRASH = 4;

    protected static $enabled = null;
    protected static $saveInFile = null;
    protected static $filename = null;
    protected static $saveInSyslogng = null;
    protected static $ip = null;
    protected static $port = null;
    protected static $severityList = array();
    protected static $saveInBo = null;
    protected static $severity = null;
    protected static $loggers = array();

    public static function getSaveInFile() {
        if (is_null(self::$saveInFile))
            self::readConfig();

        return self::$saveInFile;
    }

    public static function getSaveInBo() {
        if (is_null(self::$saveInBo))
            self::readConfig();

        return self::$saveInBo;
    }

    public static function getSaveInSyslogng() {
        if (is_null(self::$saveInSyslogng))
            self::readConfig();

        return self::$saveInSyslogng;
    }

    protected static function openFile($file) {
        $dir = plugin_dir_path(__FILE__) . '../log/';
        $logFile = $dir . '/' . $file;
        if (!is_dir($dir)) {
            mkdir($dir, 0777);
        }

        if ((self::$loggers[$file] = fopen($logFile, 'a')) === false) {
            echo 'Impossible d\'ouvrir le fichier ($file)';
            die();
        }

        return;
    }

    public static function isEnabled($module_name) {
        if (is_null(self::$enabled))
            self::readConfig();

        return self::$enabled;
    }

    /*
     * Write the log into a file
     */

    protected static function saveInFile($message, $level = self::LOG_DEBUG, $file = 'system.log', $forceLog = false) {
        try {
            $logActive = self::isEnabled('computop');
            if (empty($file)) {
                $file = self::getFilename();
            }
        } catch (Exception $e) {
            $logActive = true;
        }

        if (!$logActive && !$forceLog) {
            return;
        }

        try {
            if (!isset(self::$loggers[$file])) {
                self::openFile($file);
            }
            fwrite(self::$loggers[$file], $message);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public static function getFilename() {
        if (is_null(self::$filename))
            self::readConfig();

        return self::$filename;
    }

    protected static function getSeverityLabel($severity) {
        if (empty(self::$severityList)) {
            self::$severityList[self::LOG_DEBUG] = 'Debug';
            self::$severityList[self::LOG_INFO] = 'Informative only';
            self::$severityList[self::LOG_WARNING] = 'Warning';
            self::$severityList[self::LOG_ERROR] = 'Error';
            self::$severityList[self::LOG_CRASH] = 'Major issue (crash)';
        }

        return self::$severityList[$severity];
    }

    /*
     * Read the configuration file
     */

    public static function readConfig() {
        $xmlFile = plugin_dir_path(__FILE__) . '../log/logconfig.xml';

        if (file_exists($xmlFile)) {
            $xml = simplexml_load_file($xmlFile);

            self::$enabled = (int) $xml->configuration->enabled;
            self::$severity = (int) $xml->configuration->severity;
            self::$saveInFile = (int) $xml->configuration->saveinfile;
            self::$filename = (string) $xml->configuration->filename;
            self::$saveInBo = (int) $xml->configuration->saveinbo;
            self::$saveInSyslogng = (int) $xml->configuration->saveinsyslogng;
            self::$ip = (string) $xml->configuration->ip;
            self::$port = (int) $xml->configuration->port;
        }
    }

    public static function getSeverity() {
        if (is_null(self::$severity))
            self::readConfig();

        return self::$severity;
    }

    /**
     * Log
     */
    public static function log($message, $level = self::LOG_DEBUG, $file = '', $forceLog = false, $btLvl = 1) {

        if ($level == self::LOG_DEBUG)
            self::$saveInBo = 0;

        $levelFilter = (int) self::getSeverity();

        if (!$forceLog && (int) $level < $levelFilter)
            return;

        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
            self::$saveInBo = 0;
        }

        if ($levelFilter == self::LOG_DEBUG) {
            $dBT = debug_backtrace(false);
            if (isset($dBT[$btLvl])) {
                $dBT = array_merge(array(
                    'class' => '',
                    'function' => ''), $dBT[$btLvl]);
                $message = $dBT['class'] . ' ' . $dBT['function'] . ': ' . $message;
            }
        }

        $message = $message . "\n";

        if (self::getSaveInFile()) {
            $mess = date('c') . ' ' . self::getSeverityLabel($level) . ' (level ' . (int) $level . '): ' . $message;
            self::saveInFile($mess, $level, $file, $forceLog);
        }

        if (self::getSaveInBo()) {
            $mess = trim(preg_replace("'\s+'", ' ', $message));
            self::saveInBo($mess, $level, $forceLog);
        }

        if (self::getSaveInSyslogng()) {
            $mess = self::getSeverityLabel($level) . ' (level ' . (int) $level . '): ' . $message;
            $mess = trim(preg_replace("'\s+'", ' ', $mess));
            self::saveInSyslogng($mess, $level, $forceLog);
        }
    }
}
