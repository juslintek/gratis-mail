<?php declare(strict_types=1);

namespace Gratis\Mail;

defined('ABSPATH') || exit;

/**
 * Transactional email logger. Records the outcome of every wp_mail() call in a
 * capped option so the most recent deliveries can be reviewed in the admin.
 */
final class Logger {

    /** Option holding the log entries. */
    private const OPTION = 'gratis_mail_log';

    /** Maximum number of entries retained. */
    private const MAX_ENTRIES = 200;

    /**
     * Record a successful send (fired on wp_mail_succeeded).
     *
     * @param array<string,mixed> $data Mail data from the hook.
     */
    public static function logSuccess(array $data): void {
        self::log('sent', (array) ($data['to'] ?? []), (string) ($data['subject'] ?? ''));
    }

    /**
     * Record a failed send (fired on wp_mail_failed).
     *
     * @param \WP_Error $error Error from the hook.
     */
    public static function logFailure(\WP_Error $error): void {
        $data = $error->get_error_data();
        $data = is_array($data) ? $data : [];
        self::log('failed', (array) ($data['to'] ?? []), (string) ($data['subject'] ?? ''), $error->get_error_message());
    }

    /**
     * Append an entry to the log, trimming to the cap.
     *
     * @param array<int,string> $to Recipients.
     */
    private static function log(string $status, array $to, string $subject, string $error = ''): void {
        $log   = get_option(self::OPTION, []);
        $log   = is_array($log) ? $log : [];
        $log[] = [
            'status'  => $status,
            'to'      => $to,
            'subject' => $subject,
            'error'   => $error,
            'time'    => gmdate('c'),
        ];
        if (count($log) > self::MAX_ENTRIES) {
            $log = array_slice($log, -self::MAX_ENTRIES);
        }
        update_option(self::OPTION, $log, false);
    }

    /**
     * Return the most recent entries (newest first).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function getLog(int $limit = 50): array {
        $log = get_option(self::OPTION, []);
        $log = is_array($log) ? $log : [];
        return array_slice(array_reverse($log), 0, max(1, $limit));
    }

    /**
     * Delivery counters.
     *
     * @return array{total:int,sent:int,failed:int}
     */
    public static function getStats(): array {
        $log    = get_option(self::OPTION, []);
        $log    = is_array($log) ? $log : [];
        $sent   = count(array_filter($log, static fn($e) => ($e['status'] ?? '') === 'sent'));
        $failed = count(array_filter($log, static fn($e) => ($e['status'] ?? '') === 'failed'));
        return ['total' => count($log), 'sent' => $sent, 'failed' => $failed];
    }

    /**
     * Clear the log.
     */
    public static function clear(): void {
        delete_option(self::OPTION);
    }
}
