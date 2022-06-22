<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class horders extends Model
{
    //
    public $table = 'horders';
    public $primaryKey = 'idHOrder';
    public $timestamps = false;
    public $fillable = ['idHOrder','tanggalOrder','waktuOrder','usernamePembeli',
                        'usernamePenjual','totalHargaBarang','biayaOngkir','isDonasi',
                        'idDonasi','idAlamat','jenisPengiriman','informasiTambahanPengiriman',
                        'informasiTambahanPenjual','statusPengiriman','rating','comment','appFee'];
    public $incrementing = true;
}
