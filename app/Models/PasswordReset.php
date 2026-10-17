<?php
/**
 * Password Reset Model
 *
 * Only a SHA-256 hash of the token is stored; the plain token is emailed.
 */

require_once __DIR__ . '/BaseModel.php';

class PasswordReset extends BaseModel
{
    protected $table = 'password_resets';
    protected $fillable = ['email', 'token', 'expires_at'];

    /**
     * Create a new token for an email and return the plain value.
     * Password resets live 1 hour; team invitations use a longer lifetime.
     */
    public function createToken($email, $validHours = 1)
    {
        $this->query("DELETE FROM {$this->table} WHERE email = ?", [$email]);

        $token = random_string(64);
        $this->create([
            'email' => $email,
            'token' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + (int) $validHours * 3600),
        ]);

        return $token;
    }

    /**
     * Find a valid (non-expired) reset by plain token
     */
    public function findValid($token)
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE token = ? AND expires_at > NOW()
                LIMIT 1";
        return $this->query($sql, [hash('sha256', $token)])->fetch();
    }

    public function deleteForEmail($email)
    {
        $this->query("DELETE FROM {$this->table} WHERE email = ?", [$email]);
    }
}
