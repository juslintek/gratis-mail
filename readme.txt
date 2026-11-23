=== Gratis Mail ===
Contributors: juslintek
Tags: smtp, email, mailer, wp mail, transactional email
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

SMTP mailer with encrypted credential storage, a test-email tool, and email logging.

== Description ==

SMTP mailer with encrypted credential storage, a test-email tool, and email logging.

Part of the Gratis suite — a collection of free, no-upsell WordPress plugins.

**Features:**

* SMTP delivery with TLS/SSL support
* Credentials encrypted at rest using site salts
* Send a test email from the admin
* Transactional email delivery log

**Philosophy:**

Everything is free. No "Pro" version, no locked features, no nag screens. Optional managed cloud services may be offered separately to cover infrastructure costs.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Configure it from its settings screen in the WordPress admin.

== Frequently Asked Questions ==

= Is this plugin really free? =

Yes. Everything is free — no "Pro" version, no locked features, and no nag screens. Optional managed cloud services may be offered separately to cover infrastructure costs.

= Does this plugin make external HTTP requests? =

No. Unless you explicitly configure an external service (such as a CDN or SMTP host), the plugin does not phone home or send analytics.

= Where do I get support? =

Open an issue on the plugin's GitHub repository.

== Third-Party Services ==

This plugin connects to an external SMTP server to deliver WordPress transactional emails when you configure SMTP credentials.

The SMTP host, port, and credentials are configured by you in the plugin settings. Common providers include Gmail SMTP, Amazon SES, Mailgun, Brevo, and any standard SMTP relay. The plugin sends email content (recipient, subject, body) to whichever SMTP server you configure.

No data is sent to any external service unless you explicitly configure SMTP credentials in the plugin settings.

== Changelog ==

= 1.0.0 =
* Initial public release.

== Upgrade Notice ==

= 1.0.0 =
Initial public release.
