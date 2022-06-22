<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class actors extends Model
{
    public $table = 'actors';
    public $primaryKey = 'username';
    public $timestamps = false;
    public $fillable = ['username','password','nama','email','noHP','tipeActor','firebaseToken','saldo','status',
                            'ongkir','isVerified','otpCode','timeOtp'];
    public $incrementing = false;
}
