<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\penerimabantuans;
use App\horders;
use App\dorders;
use App\actors;

class PenerimaBantuanController extends Controller
{
    //
    public function getPenerimaBantuan()
    {
        $bantu      = new penerimabantuans();
        $dataBantuan= $bantu->where('username','=',null)->get();

        $res['dataBantuan']=$dataBantuan;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getPenerimaBantuanUser(Request $req)
    {
        $bantu      = new penerimabantuans();
        $dataBantuan= $bantu->where('username','=',null)->orWhere('username','=',$req->username)->get();

        $res['dataBantuan']=$dataBantuan;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getLaporanDonasi(Request $req)
    {
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $jenisPengurutan = $req->jenisPengurutan;
        $bantu     = new penerimabantuans();
        $dataPenerima = $bantu->where('username','=',null)->get();
        $arr = array();
        for($i=0;$i<sizeof($dataPenerima);$i++){
            $berapaKali = 0;
            $jumlahProduk = 0;
            $idPenerima = $dataPenerima[$i]->idPenerimaBantuan;
            $horder     = new horders();
            $dataOrder  = $horder->where("idDonasi","=",$idPenerima)
                                ->whereDate('tanggalOrder','>=',$tglAwal)
                                ->whereDate('tanggalOrder','<=',$tglAkhir)
                                ->get();
            if($dataOrder!=null){
                $berapaKali = sizeof($dataOrder);
                for($j=0;$j<sizeof($dataOrder);$j++){
                    $dOrder = new dorders();
                    $detOrd = $dOrder->where("idHOrder","=",$dataOrder[$j]->idHOrder)->get();
                    if($detOrd!=null){
                        for($k=0;$k<sizeof($detOrd);$k++){
                            $jumlahProduk += $detOrd[$k]->qty;
                        }
                    }
                }
            }
            $elementBaru = array("id"=>$dataPenerima[$i]->idPenerimaBantuan,"nama"=>$dataPenerima[$i]->namaPenerimaBantuan,
                            "alamat"=>$dataPenerima[$i]->alamat.", ".$dataPenerima[$i]->kota.", ".$dataPenerima[$i]->kodePos,
                            "berapaKali"=>$berapaKali,"jumlahProduk"=>$jumlahProduk
                            );
            array_push($arr,$elementBaru);
        }
        if($jenisPengurutan==0){
            usort($arr, function ($a, $b) {
                return $b["berapaKali"] - $a["berapaKali"];
            });
        }
        else{
            usort($arr, function ($a, $b) {
                return $b["jumlahProduk"] - $a["jumlahProduk"];
            });
        }
        $res['data']=$arr;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function simpanPenerimaBantuan(Request $req)
    {
        $nama       = $req->nama;
        $alamat     = $req->alamat;
        $kodePos    = $req->kodePos;
        $latitude   = $req->latitude;
        $longitude  = $req->longitude;
        $contact    = $req->contact;
        $noTelp     = $req->noTelp;
        $jumlah     = $req->jumlah;
        $keterangan = $req->keterangan;
        $username   = $req->username;

        $act = new actors();
        $jenis = $act->find($username)->tipeActor;
        if($jenis=="A"){
            $username=null;
        }

        $penerima = new penerimabantuans();
        $penerima->namaPenerimaBantuan = $nama;
        $penerima->alamat = $alamat;
        $penerima->kota = "Surabaya";
        $penerima->kodePos = $kodePos;
        $penerima->latitude = $latitude;
        $penerima->longitude = $longitude;
        $penerima->contactPerson = $contact;
        $penerima->noTelp = $noTelp;
        $penerima->jumlahPenerimaBantuan = $jumlah;
        $penerima->keterangan = $keterangan;
        $penerima->username = $username;
        $save = $penerima->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function editPenerimaBantuan(Request $req)
    {
        $nama       = $req->nama;
        $alamat     = $req->alamat;
        $kodePos    = $req->kodePos;
        $latitude   = $req->latitude;
        $longitude  = $req->longitude;
        $contact    = $req->contact;
        $noTelp     = $req->noTelp;
        $jumlah     = $req->jumlah;
        $keterangan = $req->keterangan;
        $username   = $req->username;

        $act = new actors();
        $jenis = $act->find($username)->tipeActor;
        if($jenis=="A"){
            $username=null;
        }

        $bantu = new penerimabantuans();
        $penerima = $bantu->find($req->id);
        $penerima->namaPenerimaBantuan = $nama;
        $penerima->alamat = $alamat;
        $penerima->kota = "Surabaya";
        $penerima->kodePos = $kodePos;
        $penerima->latitude = $latitude;
        $penerima->longitude = $longitude;
        $penerima->contactPerson = $contact;
        $penerima->noTelp = $noTelp;
        $penerima->jumlahPenerimaBantuan = $jumlah;
        $penerima->keterangan = $keterangan;
        $penerima->username = $username;
        $save = $penerima->save();

        $res['status']="Sukses";
        return json_encode($res);
    }
}
