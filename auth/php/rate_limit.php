<?php
/**
 * Rate limiting helper for password reset requests.
 * Allows a maximum of 5 attempts per IP per 15-minute window.
 *
 * @param PDO    $pdo Database connection
 * @param string $ip  Client IP address
 * @return bool true = allowed, false = rate-limited
 */
function checkPasswordResetRateLimit(PDO $pdo, string $ip): bool
{
    $maxAttempts   = 5;
    $windowMinutes = 15;

    // Purge stale entries to keep the table lean
    $pdo->prepare(
        "DELETE FROM password_reset_rate_limit
         WHERE attempt_time < DATE_SUB(NOW(), INTERVAL ? MINUTE)"
    )->execute([$windowMinutes]);

    // Count recent attempts from this IP within the window
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM password_reset_rate_limit
         WHERE ip_address = ?
           AND attempt_time >= DATE_SUB(NOW(), INTERVAL ? MINUTE)"
    );
    $stmt->execute([$ip, $windowMinutes]);

    if ((int) $stmt->fetchColumn() >= $maxAttempts) {
        return false;
    }

    // Record this attempt
    $pdo->prepare(
        "INSERT INTO password_reset_rate_limit (ip_address, attempt_time)
         VALUES (?, NOW())"
    )->execute([$ip]);

    return true;
}
