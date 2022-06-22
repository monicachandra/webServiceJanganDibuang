<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class dailywastes extends Model
{
    public $table = 'dailywastes';
    public $primaryKey = 'idDailyWaste';
    public $timestamps = false;
    public $fillable = ['idDailyWaste','isPaket','namaPaket','idMasterBarang',
                        'username','stok','tanggal','waktu','hargaAsli','hargaWaste'];
    public $incrementing = true;
}
