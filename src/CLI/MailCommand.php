<?php declare(strict_types=1);
namespace Gratis\Mail\CLI;

use Gratis\Mail\Logger;

final class MailCommand
{
    /**
     * Show email log.
     * [--limit=<n>] : Number of entries.
     * @when after_wp_load
     */
    public function log($args, $assoc_args): void
    {
        $entries = Logger::getLog((int) ($assoc_args['limit'] ?? 20));
        if (empty($entries)) { \WP_CLI::log("No emails logged."); return; }
        $rows = array_map(fn($e) => [
            'Status' => $e['status'], 'To' => implode(', ', (array) $e['to']),
            'Subject' => substr($e['subject'], 0, 40), 'Time' => $e['time'],
        ], $entries);
        \WP_CLI\Utils\format_items('table', $rows, ['Status', 'To', 'Subject', 'Time']);
    }

    /**
     * Send a test email.
     * <to> : Recipient email.
     * @when after_wp_load
     */
    public function test($args, $assoc_args): void
    {
        $to = $args[0];
        $sent = wp_mail($to, 'Gratis Mail Test', 'This is a test email from Gratis Mail. If you received this, SMTP is working correctly.');
        $sent ? \WP_CLI::success("Test email sent to {$to}") : \WP_CLI::error("Failed to send.");
    }

    /**
     * Configure SMTP settings.
     * --host=<host> : SMTP host.
     * [--port=<port>] : Port (default 587).
     * [--user=<user>] : Username.
     * [--pass=<pass>] : Password.
     * [--from=<email>] : From email.
     * @when after_wp_load
     */
    public function configure($args, $assoc_args): void
    {
        if (isset($assoc_args['host'])) update_option('gratis_mail_host', $assoc_args['host']);
        if (isset($assoc_args['port'])) update_option('gratis_mail_port', $assoc_args['port']);
        if (isset($assoc_args['user'])) update_option('gratis_mail_username', $assoc_args['user']);
        if (isset($assoc_args['pass'])) update_option('gratis_mail_password', $assoc_args['pass']);
        if (isset($assoc_args['from'])) update_option('gratis_mail_from_email', $assoc_args['from']);
        \WP_CLI::success("SMTP configured: " . get_option('gratis_mail_host'));
    }

    /**
     * Show email stats.
     * @when after_wp_load
     */
    public function stats($args, $assoc_args): void
    {
        $stats = Logger::getStats();
        \WP_CLI::log("Emails: {$stats['total']} total, {$stats['sent']} sent, {$stats['failed']} failed");
    }
}
