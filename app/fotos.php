<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class fotos extends Model
{
    public $table = 'fotos';
    public $primaryKey = 'idFoto';
    public $timestamps = false;
    public $fillable = ['idFoto','idBarang','foto'];
    public $incrementing = true;
}
