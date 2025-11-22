<?php
/**
 * User Model
 */

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel
{
    protected $table = 'users';
    protected $fillable = ['tenant_id', 'email', 'password', 'first_name', 'last_name', 'role', 'status'];

    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        return $this->first(['email' => $email]);
    }

    /**
     * Create user with hashed password
     */
    public function createUser($data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->create($data);
    }

    /**
     * Verify user password
     */
    public function verifyPassword($email, $password)
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin($userId)
    {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?";
        $this->query($sql, [$userId]);
    }

    /**
     * Get users by tenant
     */
    public function getByTenant($tenantId)
    {
        return $this->where(['tenant_id' => $tenantId], 'created_at', 'DESC');
    }
}
