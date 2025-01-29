<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'image', 
        'password',
        'type',
        'contact',
        'sex',
        'google_id',
        'facebook_link',
        'linkedin_link'
    

    ];
    public function courses()
    {
        return $this->hasMany(Course::class);
    }


    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }
    public function certifications()
    {
        return $this->hasMany(Certification::class);
    }


    public function presences() {
        return $this->hasMany(Presence::class);
    }

    public function isApprenant() {
        return $this->type === 'apprenant';
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
