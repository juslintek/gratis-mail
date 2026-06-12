<?php declare(strict_types=1);

namespace Gratis\Mail;

defined('ABSPATH') || exit;

/**
 * SMTP settings screen (Settings API) plus a "Send test email" action and a
 * recent-email log table. Credentials are stored in discrete options; the
 * password is encrypted at rest via {@see Crypto}.
 */
final class Settings {

    /** Settings group / page slug. */
    private const GROUP = 'gratis_mail_settings_group';
    private const PAGE  = 'gratis-mail';

    /** Placeholder shown in the password field when a value is stored. */
    private const PASSWORD_MASK = '********';

    /**
     * Register the settings page under the Settings menu.
     */
    public static function register_menu(): void {
        add_options_page(
            __('Gratis Mail', 'gratis-mail'),
            __('Gratis Mail', 'gratis-mail'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render_page']
        );
    }

    /**
     * Register each SMTP option with its sanitisation callback.
     */
    public static function register_settings(): void {
        register_setting(self::GROUP, 'gratis_mail_host', ['type' => 'string', 'sanitize_callback' => [__CLASS__, 'sanitize_host'], 'default' => '']);
        register_setting(self::GROUP, 'gratis_mail_port', ['type' => 'integer', 'sanitize_callback' => [__CLASS__, 'sanitize_port'], 'default' => 587]);
        register_setting(self::GROUP, 'gratis_mail_encryption', ['type' => 'string', 'sanitize_callback' => [__CLASS__, 'sanitize_encryption'], 'default' => 'tls']);
        register_setting(self::GROUP, 'gratis_mail_username', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '']);
        register_setting(self::GROUP, 'gratis_mail_password', ['type' => 'string', 'sanitize_callback' => [__CLASS__, 'sanitize_password'], 'default' => '']);
        register_setting(self::GROUP, 'gratis_mail_from_email', ['type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => '']);
        register_setting(self::GROUP, 'gratis_mail_from_name', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '']);
    }

    /**
     * Sanitise the SMTP host (hostname or IP, no scheme).
     */
    public static function sanitize_host($value): string {
        $value = sanitize_text_field((string) $value);
        $value = preg_replace('#^[a-z]+://#i', '', $value);
        return trim((string) $value);
    }

    /**
     * Clamp the port to the valid TCP range.
     */
    public static function sanitize_port($value): int {
        $port = absint($value);
        return ($port >= 1 && $port <= 65535) ? $port : 587;
    }

    /**
     * Whitelist the encryption mode.
     */
    public static function sanitize_encryption($value): string {
        $value = sanitize_text_field((string) $value);
        return in_array($value, ['none', 'tls', 'ssl'], true) ? $value : 'tls';
    }

    /**
     * Encrypt the password before storage. The masked placeholder means "keep
     * the existing value", so it is not re-saved.
     */
    public static function sanitize_password($value): string {
        $value = (string) $value;
        if (self::PASSWORD_MASK === $value) {
            return (string) get_option('gratis_mail_password', '');
        }
        if ('' === $value) {
            return '';
        }
        return Crypto::encrypt($value);
    }

    /**
     * Render the settings screen, test-email form, and recent log.
     */
    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'gratis-mail'));
        }

        self::maybe_send_test_email();

        $host          = (string) get_option('gratis_mail_host', '');
        $port          = (int) get_option('gratis_mail_port', 587);
        $encryption    = (string) get_option('gratis_mail_encryption', 'tls');
        $username      = (string) get_option('gratis_mail_username', '');
        $has_password  = '' !== (string) get_option('gratis_mail_password', '');
        $from_email    = (string) get_option('gratis_mail_from_email', '');
        $from_name     = (string) get_option('gratis_mail_from_name', '');

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Gratis Mail', 'gratis-mail') . '</h1>';
        echo '<p>' . esc_html__('Send WordPress email through your own SMTP server. Credentials are encrypted at rest.', 'gratis-mail') . '</p>';

        if ('' === $host) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__('No SMTP host configured yet — WordPress is using the default PHP mailer.', 'gratis-mail') . '</p></div>';
        }

        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row"><label for="gm_host">' . esc_html__('SMTP Host', 'gratis-mail') . '</label></th><td>';
        echo '<input type="text" id="gm_host" class="regular-text" name="gratis_mail_host" value="' . esc_attr($host) . '" placeholder="smtp.example.com">';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="gm_port">' . esc_html__('Port', 'gratis-mail') . '</label></th><td>';
        echo '<input type="number" min="1" max="65535" id="gm_port" class="small-text" name="gratis_mail_port" value="' . esc_attr((string) $port) . '">';
        echo ' <span class="description">' . esc_html__('Common: 587 (TLS), 465 (SSL), 25 (none).', 'gratis-mail') . '</span>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="gm_encryption">' . esc_html__('Encryption', 'gratis-mail') . '</label></th><td>';
        echo '<select id="gm_encryption" name="gratis_mail_encryption">';
        foreach (['tls' => 'TLS', 'ssl' => 'SSL', 'none' => __('None', 'gratis-mail')] as $option_value => $label) {
            echo '<option value="' . esc_attr($option_value) . '"' . selected($encryption, $option_value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="gm_username">' . esc_html__('Username', 'gratis-mail') . '</label></th><td>';
        echo '<input type="text" id="gm_username" class="regular-text" name="gratis_mail_username" value="' . esc_attr($username) . '" autocomplete="off">';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="gm_password">' . esc_html__('Password', 'gratis-mail') . '</label></th><td>';
        echo '<input type="password" id="gm_password" class="regular-text" name="gratis_mail_password" value="' . esc_attr($has_password ? self::PASSWORD_MASK : '') . '" autocomplete="new-password">';
        echo '<p class="description">' . esc_html__('Stored encrypted. Leave the masked value to keep the current password; clear the field to remove it.', 'gratis-mail') . '</p>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="gm_from_email">' . esc_html__('From Email', 'gratis-mail') . '</label></th><td>';
        echo '<input type="email" id="gm_from_email" class="regular-text" name="gratis_mail_from_email" value="' . esc_attr($from_email) . '">';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="gm_from_name">' . esc_html__('From Name', 'gratis-mail') . '</label></th><td>';
        echo '<input type="text" id="gm_from_name" class="regular-text" name="gratis_mail_from_name" value="' . esc_attr($from_name) . '" placeholder="' . esc_attr((string) get_bloginfo('name')) . '">';
        echo '</td></tr>';

        echo '</tbody></table>';
        submit_button();
        echo '</form>';

        self::render_test_form();
        self::render_log();

        echo '</div>';
    }

    /**
     * Render the "Send test email" form.
     */
    private static function render_test_form(): void {
        $current = wp_get_current_user();
        $default = is_object($current) && !empty($current->user_email) ? (string) $current->user_email : '';

        echo '<h2>' . esc_html__('Send Test Email', 'gratis-mail') . '</h2>';
        echo '<form method="post">';
        wp_nonce_field('gratis_mail_test');
        echo '<input type="hidden" name="gratis_mail_action" value="send_test">';
        echo '<input type="email" name="gratis_mail_test_to" class="regular-text" value="' . esc_attr($default) . '" placeholder="you@example.com" required> ';
        submit_button(__('Send test email', 'gratis-mail'), 'secondary', 'submit', false);
        echo '</form>';
    }

    /**
     * Handle the test-email action (nonce + capability gated).
     */
    private static function maybe_send_test_email(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified immediately below.
        $action = isset($_POST['gratis_mail_action']) ? sanitize_key(wp_unslash($_POST['gratis_mail_action'])) : '';
        if ('send_test' !== $action) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('gratis_mail_test');

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by check_admin_referer() above.
        $to = isset($_POST['gratis_mail_test_to']) ? sanitize_email(wp_unslash($_POST['gratis_mail_test_to'])) : '';
        if ('' === $to || !is_email($to)) {
            add_settings_error('gratis_mail', 'test_invalid', __('Please enter a valid recipient email address.', 'gratis-mail'), 'error');
            settings_errors('gratis_mail');
            return;
        }

        $subject = __('Gratis Mail test email', 'gratis-mail');
        $body    = __('This is a test email from Gratis Mail. If you received this, your SMTP configuration is working correctly.', 'gratis-mail');
        $sent    = wp_mail($to, $subject, $body);

        if ($sent) {
            add_settings_error('gratis_mail', 'test_sent', sprintf(/* translators: %s: recipient email. */ __('Test email sent to %s.', 'gratis-mail'), $to), 'success');
        } else {
            add_settings_error('gratis_mail', 'test_failed', __('Test email failed to send. Check your SMTP settings and the log below.', 'gratis-mail'), 'error');
        }
        settings_errors('gratis_mail');
    }

    /**
     * Render the recent email log with delivery stats.
     */
    private static function render_log(): void {
        echo '<h2>' . esc_html__('Recent Emails', 'gratis-mail') . '</h2>';

        $stats = Logger::getStats();
        echo '<p>' . esc_html(sprintf(/* translators: 1: total, 2: sent, 3: failed. */ __('%1$d total · %2$d sent · %3$d failed', 'gratis-mail'), absint($stats['total']), absint($stats['sent']), absint($stats['failed']))) . '</p>';

        $entries = Logger::getLog(50);
        if (empty($entries)) {
            echo '<p>' . esc_html__('No emails have been logged yet.', 'gratis-mail') . '</p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Status', 'gratis-mail') . '</th>';
        echo '<th>' . esc_html__('To', 'gratis-mail') . '</th>';
        echo '<th>' . esc_html__('Subject', 'gratis-mail') . '</th>';
        echo '<th>' . esc_html__('Time', 'gratis-mail') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($entries as $entry) {
            $status   = (string) ($entry['status'] ?? '');
            $to       = implode(', ', array_map('strval', (array) ($entry['to'] ?? [])));
            $subject  = (string) ($entry['subject'] ?? '');
            $time     = (string) ($entry['time'] ?? '');
            $error    = (string) ($entry['error'] ?? '');
            $badge    = 'sent' === $status ? '#46b450' : '#dc3232';

            echo '<tr>';
            echo '<td><span style="color:' . esc_attr($badge) . ';font-weight:600;">' . esc_html(ucfirst($status)) . '</span></td>';
            echo '<td>' . esc_html($to) . '</td>';
            echo '<td>' . esc_html($subject);
            if ('' !== $error) {
                echo '<br><small style="color:#dc3232;">' . esc_html($error) . '</small>';
            }
            echo '</td>';
            echo '<td>' . esc_html($time) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}
