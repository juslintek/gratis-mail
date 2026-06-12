<?php declare(strict_types=1);

namespace Gratis\Mail\CLI;

defined('ABSPATH') || exit;

use Gratis\Mail\Crypto;
use Gratis\Mail\Logger;

/**
 * WP-CLI commands for Gratis Mail (`wp gratis-mail ...`).
 */
final class MailCommand {

    /**
     * Show the recent email log.
     *
     * [--limit=<n>]
     * : Number of entries to display. Default 20.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args (unused).
     * @param array<string,string> $assoc_args Flags.
     */
    public function log($args, $assoc_args): void {
        unset($args);
        $entries = Logger::getLog((int) ($assoc_args['limit'] ?? 20));
        if (empty($entries)) {
            \WP_CLI::log('No emails logged.');
            return;
        }
        $rows = array_map(static fn($e) => [
            'Status'  => (string) ($e['status'] ?? ''),
            'To'      => implode(', ', array_map('strval', (array) ($e['to'] ?? []))),
            'Subject' => substr((string) ($e['subject'] ?? ''), 0, 40),
            'Time'    => (string) ($e['time'] ?? ''),
        ], $entries);
        \WP_CLI\Utils\format_items('table', $rows, ['Status', 'To', 'Subject', 'Time']);
    }

    /**
     * Send a test email through the configured SMTP server.
     *
     * <to>
     * : Recipient email address.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args.
     * @param array<string,string> $assoc_args Flags (unused).
     */
    public function test($args, $assoc_args): void {
        unset($assoc_args);
        $to = isset($args[0]) ? sanitize_email((string) $args[0]) : '';
        if ('' === $to || !is_email($to)) {
            \WP_CLI::error('A valid recipient email address is required.');
            return;
        }
        $sent = wp_mail($to, 'Gratis Mail Test', 'This is a test email from Gratis Mail. If you received this, SMTP is working correctly.');
        if ($sent) {
            \WP_CLI::success("Test email sent to {$to}");
        } else {
            \WP_CLI::error('Failed to send. Check the SMTP settings and `wp gratis-mail log`.');
        }
    }

    /**
     * Configure SMTP settings. The password is stored encrypted.
     *
     * [--host=<host>]
     * : SMTP host.
     *
     * [--port=<port>]
     * : Port (1-65535).
     *
     * [--encryption=<mode>]
     * : Encryption mode: tls, ssl, or none.
     *
     * [--user=<user>]
     * : SMTP username.
     *
     * [--pass=<pass>]
     * : SMTP password (stored encrypted).
     *
     * [--from=<email>]
     * : From email address.
     *
     * [--from-name=<name>]
     * : From display name.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args (unused).
     * @param array<string,string> $assoc_args Flags.
     */
    public function configure($args, $assoc_args): void {
        unset($args);

        if (isset($assoc_args['host'])) {
            update_option('gratis_mail_host', sanitize_text_field((string) $assoc_args['host']));
        }
        if (isset($assoc_args['port'])) {
            $port = absint($assoc_args['port']);
            update_option('gratis_mail_port', ($port >= 1 && $port <= 65535) ? $port : 587);
        }
        if (isset($assoc_args['encryption'])) {
            $mode = sanitize_text_field((string) $assoc_args['encryption']);
            update_option('gratis_mail_encryption', in_array($mode, ['none', 'tls', 'ssl'], true) ? $mode : 'tls');
        }
        if (isset($assoc_args['user'])) {
            update_option('gratis_mail_username', sanitize_text_field((string) $assoc_args['user']));
        }
        if (isset($assoc_args['pass'])) {
            update_option('gratis_mail_password', Crypto::encrypt((string) $assoc_args['pass']));
        }
        if (isset($assoc_args['from'])) {
            update_option('gratis_mail_from_email', sanitize_email((string) $assoc_args['from']));
        }
        if (isset($assoc_args['from-name'])) {
            update_option('gratis_mail_from_name', sanitize_text_field((string) $assoc_args['from-name']));
        }

        \WP_CLI::success('SMTP configured: ' . (string) get_option('gratis_mail_host'));
    }

    /**
     * Show email delivery stats.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args (unused).
     * @param array<string,string> $assoc_args Flags (unused).
     */
    public function stats($args, $assoc_args): void {
        unset($args, $assoc_args);
        $stats = Logger::getStats();
        \WP_CLI::log("Emails: {$stats['total']} total, {$stats['sent']} sent, {$stats['failed']} failed");
    }
}
