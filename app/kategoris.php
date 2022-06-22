<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class kategoris extends Model
{
    public $table = 'kategoris';
    public $primaryKey = 'idKategori';
    public $timestamps = false;
    public $fillable = ['idKategori','namaKategori','status'];
    public $incrementing = true;
}
