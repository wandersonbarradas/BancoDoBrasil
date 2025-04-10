<?php

namespace WandersonBarradas\BancoDoBrasil\Utils;

use Carbon\Carbon;

class LogHelper
{
    /**
     * Registra informações da requisição para debug.
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return void
     */
    public static function logRequest(string $method, string $url, array $options): void
    {
        // Copia as opções para não modificar o original
        $logOptions = $options;

        // Remove ou mascara informações sensíveis
        if (isset($logOptions['headers']['Authorization'])) {
            $logOptions['headers']['Authorization'] = 'Bearer [REDACTED]';
        }

        $logData = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'method' => $method,
            'url' => $url,
            'options' => $logOptions
        ];

        // Salva o log
        self::saveLog('request', $logData);
    }

    /**
     * Registra informações da resposta para debug.
     *
     * @param mixed $response
     * @return void
     */
    public static function logResponse($response): void
    {
        $logData = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'response' => is_array($response) ? $response : json_decode(json_encode($response), true)
        ];

        // Salva o log
        self::saveLog('response', $logData);
    }

    /**
     * Registra informações de erro para debug.
     *
     * @param string $type
     * @param \Throwable $exception
     * @return void
     */
    public static function logError(string $type, \Throwable $exception): void
    {
        $logData = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'type' => $type,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];

        if ($exception instanceof \GuzzleHttp\Exception\RequestException && $exception->hasResponse()) {
            $logData['response'] = (string) $exception->getResponse()->getBody();
        }

        // Salva o log
        self::saveLog('error', $logData);
    }

    /**
     * Salva o log em arquivo.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    private static function saveLog(string $type, array $data): void
    {
        $date = Carbon::now()->format('Y-m-d');
        $logDir = storage_path('logs/bb_api');

        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true) && !is_dir($logDir)) {
                return; // Não foi possível criar o diretório
            }
        }

        $logFile = "{$logDir}/{$date}_{$type}.log";
        $logEntry = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
