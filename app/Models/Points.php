<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Points extends Model
{
    use HasFactory;

    protected $fillable = [
        'warga', 'type', 'description', 'image', 'address', 'status', 'date', 'bukti'
    ];

    // daftar opsi available:
    public static array $types = [
        'listrik padam',
        'jalan rusak',
        'saluran tersumbat',
        'jembatan',
        'fasilitas umum',
        // ... sesuai kebutuhan
    ];

    public static array $status = [
        'Diproses',
        'Sudah Selesai',
        'Ditolak',
        'menunggu',
        // ... sesuai kebutuhan
    ];

    protected $table = 'table_points';

    //Tabel yang boleh diisikan
    
    //Tabel yang tidak boleh diisikan
    protected $guarded = ['id'];

    public function points()
    {
        return $this->select(DB::raw('id, warga, type, description, image, address, status, created_at, date, bukti',))->get();
}
    public function point($id)
    {
        return $this->select(DB::raw('id, warga, type, description, image, address, status, created_at, date, bukti'))->where('id', $id)->get();
}

}

