<?php
namespace Phalcon;

/**
 * phalcon 兼容 logger 实现
 * @see http://docs.phalconphp.com/en/latest/reference/logging.html
 */
class Logger
{
    const EMERGENCE = 0;
    const CRITICAL = 1;
    const ALERT = 2;
    const ERROR = 3;
    const WARNING = 4;
    const NOTICE = 5;
    const INFO = 6;
    const DEBUG = 7;
    const CUSTOM = 8;
    const SPECIAL = 9;

    public static $LEVELS = array(
        self::EMERGENCE => 'EMERGENCE',
        self::CRITICAL  => 'CRITICAL',
        self::ALERT     => 'ALERT',
        self::ERROR     => 'ERROR',
        self::WARNING   => 'WARNING',
        self::NOTICE    => 'NOTICE',
        self::INFO      => 'INFO',
        self::DEBUG     => 'DEBUG',
        self::CUSTOM    => 'CUSTOM',
        self::SPECIAL   => 'SPECIAL',
    );
}
