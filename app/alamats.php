<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class alamats extends Model
{
    public $table = 'alamats';
    public $primaryKey = 'idAlamat';
    public $timestamps = false;
    public $fillable = ['idAlamat','username','alamat','longitude','latitude','kota','kodePos','keterangan'];
    public $incrementing = true;
}
