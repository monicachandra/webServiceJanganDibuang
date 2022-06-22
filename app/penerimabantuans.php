<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class penerimabantuans extends Model
{
    public $table = 'penerimabantuans';
    public $primaryKey = 'idPenerimaBantuan';
    public $timestamps = false;
    public $fillable = ['idPenerimaBantuan','namaPenerimaBantuan','alamat',
                        'kota','kodePos','latitude','longitude','contactPerson',
                        'noTelp','jumlahPenerimaBantuan','keterangan','username'];
    public $incrementing = true;
}
