<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'bank_name', 'bank_account_number', 'bank_account_holder'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $appends = [
        'payment_bank_name',
        'payment_bank_account_number',
        'payment_bank_account_holder',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isBuyer()
    {
        return $this->role === 'buyer';
    }

    public function isSeller()
    {
        return $this->role === 'seller';
    }

    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }

    public function getPaymentBankNameAttribute()
    {
        if (!empty($this->bank_name)) {
            return $this->bank_name;
        }
        // Fallback to first superadmin bank name
        return self::where('role', 'superadmin')->whereNotNull('bank_name')->value('bank_name');
    }

    public function getPaymentBankAccountNumberAttribute()
    {
        if (!empty($this->bank_account_number)) {
            return $this->bank_account_number;
        }
        // Fallback to first superadmin bank account number
        return self::where('role', 'superadmin')->whereNotNull('bank_account_number')->value('bank_account_number');
    }

    public function getPaymentBankAccountHolderAttribute()
    {
        if (!empty($this->bank_account_holder)) {
            return $this->bank_account_holder;
        }
        // Fallback to first superadmin bank account holder
        return self::where('role', 'superadmin')->whereNotNull('bank_account_holder')->value('bank_account_holder');
    }
}

