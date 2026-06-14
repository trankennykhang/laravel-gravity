<?php

namespace App\Models;

use App\Gravity\Database\JsonModel;
use Illuminate\Contracts\Auth\Authenticatable;

class User extends JsonModel implements Authenticatable
{
    protected static $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'remember_token'
    ];

    /**
     * Get validation rules for user registration or editing.
     */
    public static function validationRules($id = null)
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => $id ? 'nullable|string|min:6' : 'required|string|min:6'
        ];
    }

    /**
     * Authenticatable Interface Implementation
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}
