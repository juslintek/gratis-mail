<?php
/**
 * Plugin Name: Gratis Mail
 * Plugin URI: https://github.com/juslintek/gratis-mail
 * Description: SMTP mailer with encrypted credential storage, a test-email tool, and transactional email logging. Replaces WP Mail SMTP ($49/yr).
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * Author URI: https://github.com/juslintek
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gratis-mail
 *
 * @package Gratis\Mail
 */

defined('ABSPATH') || exit;

define('GRATIS_MAIL_VERSION', '1.0.0');
define('GRATIS_MAIL_FILE', __FILE__);
define('GRATIS_MAIL_DIR', plugin_dir_path(__FILE__));
define('GRATIS_MAIL_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

// Route WordPress mail through the configured SMTP server.
add_action('phpmailer_init', [\Gratis\Mail\SMTPConfig::class, 'configure']);

// Transactional logging.
add_action('wp_mail_succeeded', [\Gratis\Mail\Logger::class, 'logSuccess']);
add_action('wp_mail_failed', [\Gratis\Mail\Logger::class, 'logFailure']);

// Admin settings page.
add_action('admin_menu', [\Gratis\Mail\Settings::class, 'register_menu']);
add_action('admin_init', [\Gratis\Mail\Settings::class, 'register_settings']);

// WP-CLI commands.
add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-mail', \Gratis\Mail\CLI\MailCommand::class);
    }
});
