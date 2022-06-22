<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class detaildailywastes extends Model
{
    public $table = 'detaildailywastes';
    public $primaryKey = 'idDetailDailyWaste';
    public $timestamps = false;
    public $fillable = ['idDailyWaste','idMasterBarang','qty','hargaAsli','hargaWaste'];
    public $incrementing = true;
}
