<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ubahsaldos extends Model
{
    public $table = 'ubahsaldos';
    public $primaryKey = 'idUbahSaldo';
    public $timestamps = false;
    public $fillable = ['idUbahSaldo','jenisUbah','invoiceUbahSaldo','username',
                        'nominal','tanggal','waktu','namaRekening','namaBank','status','catatan'];
    public $incrementing = true;
}
