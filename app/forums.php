<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class forums extends Model
{
    public $table = 'forums';
    public $primaryKey = 'idForum';
    public $timestamps = false;
    public $fillable = ['idForum','judulForum','deskripsiForum','tanggalForum','fotoForum','username','jenisUser','statusAktif'];
    public $incrementing = true;
}
