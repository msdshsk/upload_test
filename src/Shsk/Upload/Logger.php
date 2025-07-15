<?php

namespace Shsk\Upload;

class Logger
{
    private string $logFile;
    private string $logLevel;
    private array $logLevels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
    ];

    public function __construct(string $logFile = 'upload.log', string $logLevel = 'INFO')
    {
        $this->logFile = $logFile;
        $this->logLevel = $logLevel;
    }

    /**
     * ログを記録
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' - ' . json_encode($context);
        $logLine = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * DEBUGログ
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * INFOログ
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * WARNINGログ
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * ERRORログ
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * ログレベルのチェック
     */
    private function shouldLog(string $level): bool
    {
        return $this->logLevels[$level] >= $this->logLevels[$this->logLevel];
    }
} 