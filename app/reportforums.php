<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class reportforums extends Model
{
    public $table = 'reportforums';
    public $primaryKey = 'idReport';
    public $timestamps = false;
    public $fillable = ['idReport','idForum','username','alasanReport','subAlasanReport','tanggal','waktu','statusAktif'];
    public $incrementing = true;
}
