<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class dorders extends Model
{
    //
    public $table = 'dorders';
    public $primaryKey = 'idDOrder';
    public $timestamps = false;
    public $fillable = ['idDOrder','idHOrder','idDailyWaste','qty','harga','rating','comment'];
    public $incrementing = true;
}
