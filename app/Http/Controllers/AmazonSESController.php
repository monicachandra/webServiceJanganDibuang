<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\actors;
use Mail;

class AmazonSESController extends Controller
{
    public function kirimEmailVerifikasi(Request $req)
    {
        $username = $req->username;
        $actor = new actors();
        $dataActor = $actor->find($username);
        $email = $dataActor->email;
        $nama  = $dataActor->nama;
        $req->email = $email;
        
        $data['nama']=$nama;
        $data['username']=$username;
        Mail::send('emailVerification', ['data' => $data ], 
            function ($message) use ($req)
            {
                $message->subject("Verification Email");
                $message->from('noreply.jangandibuang@gmail.com', 'noreply.jangandibuang@gmail.com');
                $message->to($req->email);
            }
        );
        $res['status']  = "Sukses";
        return json_encode($res);
    }
    public function verifikasi($username)
    {
        $actor = new actors();
        $dataActor = $actor->find($username);
        $dataActor->isVerified = 1;
        $save = $dataActor->save();
        return view('verified');
    }

    public function kirimEmailOTP(Request $req)
    {
        $username = $req->username;
        $actor = new actors();
        $dataActor = $actor->find($username);
        $email = $dataActor->email;
        $nama  = $dataActor->nama;
        $otp   = $dataActor->otpCode;
        $req->email = $email;
        
        $data['nama']=$nama;
        $data['username']=$username;
        $data['otp']=$otp;
        Mail::send('emailOTP', ['data' => $data ], 
            function ($message) use ($req)
            {
                $message->subject("OTP Email");
                $message->from('noreply.jangandibuang@gmail.com', 'noreply.jangandibuang@gmail.com');
                $message->to($req->email);
            }
        );
        $res['status']  = "Sukses";
        return json_encode($res);
    }

    public function kirimEmailOTPLagi(Request $req)
    {
        $username = $req->username;
        $actor = new actors();
        $dataActor = $actor->find($username);
        $angka = rand(1001,9999);
        $dataActor->otpCode = $angka;
        $dataActor->timeOtp = date("H:i:s");
        $save = $dataActor->save();
        $email = $dataActor->email;
        $nama  = $dataActor->nama;
        $otp   = $angka;
        $req->email = $email;
        
        $data['nama']=$nama;
        $data['username']=$username;
        $data['otp']=$otp;
        Mail::send('emailOTP', ['data' => $data ], 
            function ($message) use ($req)
            {
                $message->subject("OTP Email");
                $message->from('noreply.jangandibuang@gmail.com', 'noreply.jangandibuang@gmail.com');
                $message->to($req->email);
            }
        );
        $res['status']  = "Sukses";
        return json_encode($res);
    }
    public function cekOtp(Request $req)
    {
        $pin = $req->pin;
        $username = $req->username;

        $act = new actors();
        $dataActor = $act->find($username);

        $otpCode = $dataActor->otpCode;
        $timeOtp = $dataActor->timeOtp;

        $timeNow = date("H:i:s");
        $timestampNow = strtotime($timeNow);
        $timestampBfr = strtotime($timeOtp);

        $status = "Sukses";
        if($timestampNow-$timestampBfr>150){
            $status = "OTP Expired, kirim OTP lagi";
        }
        else{
            if($pin==$otpCode){
                $dataActor->isVerified = 1;
                $save = $dataActor->save();
            }
            else{
                $status = "OTP tidak sesuai";
            }
        }
        $res['status']  = $status;
        return json_encode($res);
    }
}
