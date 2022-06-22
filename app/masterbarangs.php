<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class masterbarangs extends Model
{
    public $table = 'masterbarangs';
    public $primaryKey = 'idBarang';
    public $timestamps = false;
    public $fillable = ['idBarang','idKategori','namaBarang','deskripsiBarang','
                            hargaBarangAsli','hargaBarangFoodWaste','username','status'];
    public $incrementing = true;
}
