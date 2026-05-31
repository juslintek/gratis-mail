<?php declare(strict_types=1);
namespace Gratis\Mail;

final class Logger
{
    private const OPTION = 'gratis_mail_log';

    public static function logSuccess(array $data): void
    {
        self::log('sent', $data['to'] ?? [], $data['subject'] ?? '');
    }

    public static function logFailure(\WP_Error $error): void
    {
        $data = $error->get_error_data();
        self::log('failed', $data['to'] ?? [], $data['subject'] ?? '', $error->get_error_message());
    }

    private static function log(string $status, array $to, string $subject, string $error = ''): void
    {
        $log = get_option(self::OPTION, []);
        $log[] = [
            'status'  => $status,
            'to'      => $to,
            'subject' => $subject,
            'error'   => $error,
            'time'    => gmdate('c'),
        ];
        if (count($log) > 500) $log = array_slice($log, -500);
        update_option(self::OPTION, $log, false);
    }

    public static function getLog(int $limit = 50): array
    {
        $log = get_option(self::OPTION, []);
        return array_slice(array_reverse($log), 0, $limit);
    }

    public static function getStats(): array
    {
        $log = get_option(self::OPTION, []);
        $sent = count(array_filter($log, fn($e) => $e['status'] === 'sent'));
        $failed = count(array_filter($log, fn($e) => $e['status'] === 'failed'));
        return ['total' => count($log), 'sent' => $sent, 'failed' => $failed];
    }
}
