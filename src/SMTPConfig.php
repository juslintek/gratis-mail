<?php declare(strict_types=1);
namespace Gratis\Mail;

final class SMTPConfig
{
    public static function configure(\PHPMailer\PHPMailer\PHPMailer $phpmailer): void
    {
        $host = get_option('gratis_mail_host');
        if (!$host) return;

        $phpmailer->isSMTP();
        $phpmailer->Host       = $host;
        $phpmailer->Port       = (int) get_option('gratis_mail_port', 587);
        $phpmailer->SMTPSecure = get_option('gratis_mail_encryption', 'tls');
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = get_option('gratis_mail_username', '');
        $phpmailer->Password   = get_option('gratis_mail_password', '');

        $from = get_option('gratis_mail_from_email');
        if ($from) {
            $phpmailer->setFrom($from, get_option('gratis_mail_from_name', get_bloginfo('name')));
        }
    }
}
