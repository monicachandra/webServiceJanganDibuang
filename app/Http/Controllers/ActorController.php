<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\actors;
use App\alamats;

class ActorController extends Controller
{
    public function registerActor(Request $req)
    {
        $act        = new actors();
        $dataAll    = $act->all();
        $username   = $req->username;
        $ada        = false;
        for($i=0;$i<sizeof($dataAll);$i++){
            if($dataAll[$i]['username']==$username){
                $ada=true;
            }
        }
        if($ada){
            $res['status']="Username telah terdaftar";
        }
        else{
            $nama       = $req->nama;
            $email      = $req->email;
            $noHP       = $req->noHP;
            $tipeUser   = $req->tipeUser;
                $tUser  = 'P';
                if($tipeUser=="Penyedia Makanan"){
                    $tUser = 'V';
                }
            $password   = md5($req->password);

            //simpan actors

            $act->username  = $username;
            $act->nama      = $nama;
            $act->email     = $email;
            $act->noHP      = $noHP;
            $act->tipeActor = $tUser;
            $act->password  = $password;
            $act->ongkir    = 0;
            $save           = $act->save();

            if($tUser!="V"){
                $act->ongkir=null;
                $save       = $act->save();
            }
            else{
                
                $almt           = new alamats();
                $almt->username = $username;
                $almt->alamat   = '-';
                $save           = $almt->save();
            }

            $res['status']="Sukses";
        }
        return json_encode($res);
    }

    public function registerActorGuest(Request $req)
    {
        $latitude  = $req->latitude;
        $longitude = $req->longitude;
        $almt      = $req->alamat;
        $keterangan = $req->keterangan;
        $kodePos    = $req->kodePos;
        $email      = $req->email;
        $noHP       = $req->noHP;
        $token      = $req->token;

        $depanEmail = explode("@", $email);
        $angka = rand(1001, 9999);
        $bolehLanjut=false;
        $rangkaiUsername=$depanEmail[0].$angka;

        while(!$bolehLanjut){
            $act = new actors();
            $data = $act->find($rangkaiUsername);
            if($data==null){
                $bolehLanjut=true;
            }
            else{
                $angka = rand(1001,9999);
                $rangkaiUsername = $depanEmail[0].$angka;
            }
        }
        
        $nama       = $depanEmail[0];
        $tUser      = 'P';
        $password   = md5('123Aa');

        //simpan actors
        $act = new actors();
        $act->username  = $rangkaiUsername;
        $act->nama      = $nama;
        $act->email     = $email;
        $act->noHP      = $noHP;
        $act->tipeActor = $tUser;
        $act->password  = $password;
        $act->otpCode   = $angka;
        $act->firebaseToken = $token;
        $act->timeOtp   = date("H:i:s");
        $save           = $act->save();

        //simpan alamat
        $alamat          = new alamats();
        $alamat->username = $rangkaiUsername;
        $alamat->latitude  = $latitude;
        $alamat->longitude = $longitude;
        $alamat->alamat    = $almt;
        $alamat->keterangan = $keterangan;
        $alamat->kodePos    = $kodePos;
        $save               = $alamat->save();

        $res['status']="Sukses";
        $res['username']=$rangkaiUsername;
        return json_encode($res);
    }

    public function loginActor(Request $req)
    {
        $act        = new actors();
        $username   = $req->username;
        $password   = md5($req->password);
        $tokenKu    = $req->tokenKu;
        $data       = $act->find($username);
        if($data==null){
            $res['status']="Username belum terdaftar";
        }
        else{
            if($data['password']!=$password){
                $res['status']="Password salah";
            }
            else{
                $data->firebaseToken = $tokenKu;
                $save = $data->save();
                $res['status']="Sukses";
            }
            $res['jenis']=$data['tipeActor'];
            $res['statusActor']=$data['status'];
            $res['isVerified']=$data['isVerified'];
        }
        return json_encode($res);
    }

    public function getVendorWoConfirmation()
    {
        $act            = new actors();
        $data           = $act->where('tipeActor','=','V')
                            ->where('status','=',0)
                            ->where('isVerified','=',1)
                            ->get();
        $res['status']  = "Sukses";
        $res['data']    = $data;
        return json_encode($res);
    }
    
    public function getDetailVendor(Request $req)
    {
        $username       = $req->username;
        $act            = new actors();
        $data           = $act->find($username);
        $almt           = new alamats();
        $dataAlamat     = $almt->where('username','=',$username)
                                ->get();
        $res['status']  = "Sukses";
        $res['data']    = $data;
        $res['alamat']  = $dataAlamat;
        return json_encode($res);
    }

    public function konfirmasiVendor(Request $req)
    {
        $username     = $req->username;
        $act          = new actors();
        $data         = $act->find($username);
        $data->status = 1;
        $save         = $data->save();
        $res['status']  = "Sukses";
        return json_encode($res);
    }

    public function getProfilToko(Request $req)
    {
        $username     = $req->username;
        $almt         = new alamats();
        $dataAlamat   = $almt->where('username','=',$username)
                                ->get();
        $actor        = new actors();
        $act          = $actor->where('username','=',$username)
                                ->get();

        $res['status']  = "Sukses";
        $res['alamat']  = $dataAlamat;
        $res['actor']   = $act;
        return json_encode($res);
    }

    public function getSaldo(Request $req)
    {
        $username       = $req->username;
        $actor          = new actors();
        $dataActor      = $actor->find($username);
        
        $res['actor']   = $dataActor;
        $res['status']  = "Sukses";
        return json_encode($res);
    }

    public function gantiPassword(Request $req)
    {
        $username       = $req->username;
        $password       = $req->pwd;
        $passwordBaru   = $req->pwdBaru;

        $status         = "Sukses";

        $actor          = new actors();
        $dataActor      = $actor->find($username);

        if(md5($password)!=$dataActor->password){
            $status     = "Password lama tidak sesuai";
        }
        else{
            $dataActor->password = md5($passwordBaru);
            $save = $dataActor->save();
        }
        $res['status']  = $status;
        return json_encode($res);
    }

    public function gantiProfileImage(Request $req)
    {
        $username         = $req->username;
        $fileName         = $req->m_filename;

        $dataGambar = base64_decode($req->m_image);
        file_put_contents("gambar/".$fileName,$dataGambar);

        $actor            = new actors();
        $dataActor        = $actor->find($username);
        $dataActor->logo  = $fileName;
        $save             = $dataActor->save();
        
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function gantiProfilVendor(Request $req)
    {
        $username   = $req->username;
        $nama       = $req->nama;
        $noHP       = $req->noHP;
        $ongkir     = $req->ongkir;

        $actor      = new actors();
        $dataActor  = $actor->find($username);
        $dataActor->nama = $nama;
        $dataActor->noHP = $noHP;
        $dataActor->ongkir = $ongkir;
        $save = $dataActor->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function gantiProfilPembeli(Request $req)
    {
        $username   = $req->username;
        $nama       = $req->nama;
        $noHP       = $req->noHP;

        $actor      = new actors();
        $dataActor  = $actor->find($username);
        $dataActor->nama = $nama;
        $dataActor->noHP = $noHP;
        $save = $dataActor->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getFotoProfil(Request $req)
    {
        $username   = $req->arrUsername;
        $arrUsername= json_decode($username);
        $arrFoto    = array();
        for($i=0;$i<sizeof($arrUsername);$i++){
            $actor = new actors();
            $dataFoto = $actor->find($arrUsername[$i])->logo;
            array_push($arrFoto,$dataFoto);
        }

        $res['status']="Sukses";
        $res['data']=json_encode($arrFoto);
        return json_encode($res);
    }

    public function simpanAlamatVendor(Request $req)
    {
        $latitude  = $req->latitude;
        $longitude = $req->longitude;
        $almt      = $req->alamat;
        $username  = $req->username;
        $keterangan = $req->keterangan;
        $kodePos    = $req->kodePos;

        $alm          = new alamats();
        $alamat       = $alm->where('username',"=",$username)->get()[0];
        $alamat->latitude  = $latitude;
        $alamat->longitude = $longitude;
        $alamat->alamat    = $almt;
        $alamat->keterangan = $keterangan;
        $alamat->kodePos    = $kodePos;
        $save               = $alamat->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function simpanAlamatPembeli(Request $req)
    {
        $latitude  = $req->latitude;
        $longitude = $req->longitude;
        $almt      = $req->alamat;
        $username  = $req->username;
        $keterangan = $req->keterangan;
        $kodePos    = $req->kodePos;

        $alamat          = new alamats();
        $alamat->username = $username;
        $alamat->latitude  = $latitude;
        $alamat->longitude = $longitude;
        $alamat->alamat    = $almt;
        $alamat->keterangan = $keterangan;
        $alamat->kodePos    = $kodePos;
        $save               = $alamat->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function editAlamatPembeli(Request $req)
    {
        $latitude  = $req->latitude;
        $longitude = $req->longitude;
        $almt      = $req->alamat;
        $idAlamat  = $req->idAlamat;
        $keterangan = $req->keterangan;
        $kodePos    = $req->kodePos;

        $alm          = new alamats();
        $alamat       = $alm->find($idAlamat);
        $alamat->latitude  = $latitude;
        $alamat->longitude = $longitude;
        $alamat->alamat    = $almt;
        $alamat->keterangan = $keterangan;
        $alamat->kodePos    = $kodePos;
        $save               = $alamat->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getAllAlamatPembeli(Request $req)
    {
        $username = $req->username;
        
        $alamat = new alamats();
        $almt = $alamat->where('username','=',$username)->get();

        $res['data']=$almt;
        $res['status']="Sukses";
        return json_encode($res);
    }
}
