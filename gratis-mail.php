<?php
/**
 * Plugin Name: Gratis Mail
 * Description: SMTP mailer + email templates + transactional logs. Replaces WP Mail SMTP ($49/yr) + Mailgun plugin.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/autoload.php';
add_action('phpmailer_init', [\Gratis\Mail\SMTPConfig::class, 'configure']);
add_action('wp_mail_succeeded', [\Gratis\Mail\Logger::class, 'logSuccess']);
add_action('wp_mail_failed', [\Gratis\Mail\Logger::class, 'logFailure']);
add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) \WP_CLI::add_command('gratis-mail', \Gratis\Mail\CLI\MailCommand::class);
});
