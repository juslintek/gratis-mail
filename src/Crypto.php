<?php declare(strict_types=1);

namespace Gratis\Mail;

defined('ABSPATH') || exit;

/**
 * Symmetric encryption helper for credentials stored in wp_options.
 *
 * Key material is derived from the site's secret keys/salts defined in
 * wp-config.php (AUTH_KEY + AUTH_SALT) so the encryption key never lives in the
 * database next to the ciphertext. A generated, stored key is used only as a
 * fallback when those constants are unavailable (e.g. minimal test harnesses).
 */
final class Crypto {

    /** Cipher used for credential encryption. */
    private const CIPHER = 'aes-256-cbc';

    /** Option holding the fallback key when site salts are unavailable. */
    private const FALLBACK_OPTION = 'gratis_mail_crypto_key';

    /** Marker prefix identifying values encrypted by this helper. */
    private const PREFIX = 'gmenc:';

    /**
     * Encrypt a plaintext string for storage.
     *
     * @param string $plaintext Raw value to protect.
     * @return string Prefixed base64 blob, or the original value on failure.
     */
    public static function encrypt(string $plaintext): string {
        if ('' === $plaintext) {
            return '';
        }
        if (!function_exists('openssl_encrypt')) {
            return $plaintext;
        }

        $iv_length  = (int) openssl_cipher_iv_length(self::CIPHER);
        $iv         = random_bytes(max(1, $iv_length));
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        if (false === $ciphertext) {
            return $plaintext;
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary IV+ciphertext, not obfuscation.
        return self::PREFIX . base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt a value produced by encrypt(). Unprefixed values pass through
     * unchanged so legacy/plaintext options keep working.
     *
     * @param string $value Stored value.
     * @return string Decrypted plaintext (empty string on failure).
     */
    public static function decrypt(string $value): string {
        if ('' === $value || !self::is_encrypted($value)) {
            return $value;
        }
        if (!function_exists('openssl_decrypt')) {
            return '';
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- reversing encrypt(), not obfuscation.
        $raw = base64_decode(substr($value, strlen(self::PREFIX)), true);
        if (false === $raw) {
            return '';
        }

        $iv_length = (int) openssl_cipher_iv_length(self::CIPHER);
        if (strlen($raw) <= $iv_length) {
            return '';
        }

        $iv         = substr($raw, 0, $iv_length);
        $ciphertext = substr($raw, $iv_length);
        $plaintext  = openssl_decrypt($ciphertext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);

        return false === $plaintext ? '' : $plaintext;
    }

    /**
     * Whether a stored value is already encrypted by this helper.
     */
    public static function is_encrypted(string $value): bool {
        return strncmp($value, self::PREFIX, strlen(self::PREFIX)) === 0;
    }

    /**
     * Derive the 32-byte encryption key from site salts (with stored fallback).
     */
    private static function key(): string {
        $material = '';
        if (defined('AUTH_KEY') && '' !== (string) AUTH_KEY) {
            $material .= (string) AUTH_KEY;
        }
        if (defined('AUTH_SALT') && '' !== (string) AUTH_SALT) {
            $material .= (string) AUTH_SALT;
        }
        if ('' === $material) {
            $material = self::fallback_key();
        }

        return hash('sha256', $material, true);
    }

    /**
     * Lazily generate and persist a random fallback key.
     */
    private static function fallback_key(): string {
        $key = get_option(self::FALLBACK_OPTION, '');
        if (!is_string($key) || '' === $key) {
            $key = bin2hex(random_bytes(32));
            update_option(self::FALLBACK_OPTION, $key, false);
        }

        return $key;
    }
}
