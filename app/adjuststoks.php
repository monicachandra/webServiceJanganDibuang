<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class adjuststoks extends Model
{
    public $table = 'adjuststoks';
    public $primaryKey = 'idAdjustStok';
    public $timestamps = false;
    public $fillable = ['idAdjustStok','idDailyWaste','qtyPenjualanOffline','realStokSaatItu','tanggal','waktu'];
    public $incrementing = true;
}
