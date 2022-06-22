<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class globalsettings extends Model
{
    public $table = 'globalsettings';
    public $primaryKey = 'id';
    public $timestamps = false;
    public $fillable = ['id','appFee','limitShowMakanan','saldoAplikasi'];
    public $incrementing = false;
}
