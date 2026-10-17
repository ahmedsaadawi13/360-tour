<?php
/**
 * Login Attempt Model
 *
 * Failed login attempts used to throttle brute-force attacks
 */

require_once __DIR__ . '/BaseModel.php';

class LoginAttempt extends BaseModel
{
    protected $table = 'login_attempts';
    protected $fillable = ['email', 'ip_address'];

    /**
     * Whether the email or IP exceeded the allowed failed attempts
     */
    public function isLockedOut($email, $ip)
    {
        $minutes = (int) config('login_lockout_minutes', 15);
        $max = (int) config('login_max_attempts', 5);

        $sql = "SELECT
                  SUM(email = ?) AS by_email,
                  SUM(ip_address = ?) AS by_ip
                FROM {$this->table}
                WHERE attempted_at > DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)
                AND (email = ? OR ip_address = ?)";
        $row = $this->query($sql, [$email, $ip, $email, $ip])->fetch();

        // IPs get a higher allowance (shared offices / NAT)
        return (int) $row['by_email'] >= $max || (int) $row['by_ip'] >= $max * 4;
    }

    public function recordFailure($email, $ip)
    {
        $this->create(['email' => $email, 'ip_address' => $ip]);
    }

    public function clear($email)
    {
        $this->query("DELETE FROM {$this->table} WHERE email = ?", [$email]);
        // Housekeeping: drop old rows
        $this->query("DELETE FROM {$this->table} WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }
}
