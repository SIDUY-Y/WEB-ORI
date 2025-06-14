<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
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
        'password',
        'role',
        'alamat',
        'tanggal_lahir',
    ];

    /**
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $table = 'users';

    //Tabel yang boleh diisikan
    
    //Tabel yang tidak boleh diisikan

    protected $primaryKey = 'id_user';

    protected $guarded = ['id_user'];

    public function users()
    {
        return $this->select(DB::raw('id_user, name, phone, email, alamat, tanggal_lahir, created_at',))->get();
}
    public function user($id_user)
    {
        return $this->select(DB::raw('id_user, name, phone, email, alamat, tanggal_lahir, created_at'))->where('id_user', $id_user)->get();
}
    
}
