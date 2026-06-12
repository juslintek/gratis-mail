<?php declare(strict_types=1);

namespace Gratis\Mail;

defined('ABSPATH') || exit;

/**
 * Routes WordPress mail through a configured SMTP server by hooking
 * phpmailer_init. No-ops (falls back to the default PHP mailer) when no host
 * has been configured.
 */
final class SMTPConfig {

    /**
     * Configure the PHPMailer instance from stored settings.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer Active mailer instance.
     */
    public static function configure($phpmailer): void {
        $host = (string) get_option('gratis_mail_host', '');
        if ('' === $host) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = $host;
        $phpmailer->Port       = (int) get_option('gratis_mail_port', 587);
        $encryption            = (string) get_option('gratis_mail_encryption', 'tls');
        $phpmailer->SMTPSecure = in_array($encryption, ['tls', 'ssl'], true) ? $encryption : '';
        $phpmailer->SMTPAutoTLS = ('' !== $phpmailer->SMTPSecure);

        $username = (string) get_option('gratis_mail_username', '');
        $password = Crypto::decrypt((string) get_option('gratis_mail_password', ''));

        if ('' !== $username || '' !== $password) {
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $username;
            $phpmailer->Password = $password;
        } else {
            $phpmailer->SMTPAuth = false;
        }

        $from_email = (string) get_option('gratis_mail_from_email', '');
        if ('' !== $from_email && is_email($from_email)) {
            $from_name = (string) get_option('gratis_mail_from_name', get_bloginfo('name'));
            $phpmailer->setFrom($from_email, $from_name, false);
        }

        /**
         * Fires after Gratis Mail has configured the PHPMailer instance, allowing
         * advanced overrides (custom timeouts, debug level, OAuth, etc.).
         *
         * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer The configured mailer.
         */
        do_action('gratis_mail_configured', $phpmailer);
    }
}
