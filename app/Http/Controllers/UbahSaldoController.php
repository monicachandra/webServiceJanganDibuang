<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ubahsaldos;
use App\actors;

class UbahSaldoController extends Controller
{
    public function topUp(Request $req)
    {
        $username = $req->username;
        $idXendit = $req->idXendit;
        $nominal  = $req->nominal;
        $ubah     = new ubahsaldos();
        $ubah->jenisUbah = 0;
        $ubah->invoiceUbahSaldo  = $idXendit;
        $ubah->username  = $username;
        $ubah->nominal   = $nominal;
        $ubah->tanggal   = date("Y-m-d");
        $ubah->waktu     = date("H:i:s");
        $ubah->status    = 1;
        $ubah->catatan   = '-';
        $save = $ubah->save();

        $act = new actors();
        $dataActor = $act->find($username);
        $saldo = $dataActor->saldo;
        $saldo +=$nominal;
        $dataActor->saldo=$saldo;
        $save = $dataActor->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getTopUp(Request $req)
    {
        $username = $req->username;
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $ubahsaldo= new ubahsaldos();
        $data     = $ubahsaldo->where('username','=',$username)
                              ->where('jenisUbah','=',0)
                              ->whereDate('tanggal','>=',$tglAwal)
                              ->whereDate('tanggal','<=',$tglAkhir)
                              ->get();
        $total = 0;
        for($i=0;$i<sizeof($data);$i++){
            $total+=$data[$i]->nominal;
        }
        $res['status']="Sukses";
        $res['data']=$data;
        $res['total']=$total;
        return json_encode($res);
    }

    public function getWD(Request $req)
    {
        $statusWd = $req->status;
        $username = $req->username;

        $ubahSaldo = new ubahsaldos();
        $tanda = "<>";

        if($statusWd==0){
            $tanda = "=";
            
        }

        $data = $ubahSaldo->where('username',"=",$username)
                                ->where('jenisUbah',"=",1)
                                ->where('status',$tanda,0)
                                ->orderBy('tanggal','desc')
                                ->orderBy('waktu','desc')
                                ->get();

        $res['status']="Sukses";
        $res['data']=$data;
        return json_encode($res);
    }

    public function ajukanWD(Request $req)
    {
        $username = $req->username;
        $noRek    = $req->noRek;
        $nominal  = $req->nominal;
        $namaRek  = $req->namaRek;
        $namaBank = $req->namaBank;

        $ubahSaldo = new ubahsaldos();
        $ubahSaldo->jenisUbah = 1;
        $ubahSaldo->invoiceUbahSaldo = $noRek;
        $ubahSaldo->username = $username;
        $ubahSaldo->nominal = $nominal;
        $ubahSaldo->tanggal   = date("Y-m-d");
        $ubahSaldo->waktu     = date("H:i:s");
        $ubahSaldo->namaRekening = $namaRek;
        $ubahSaldo->namaBank = $namaBank;
        $ubahSaldo->status = 0;
        $ubahSaldo->catatan = "-";
        $save = $ubahSaldo->save();

        $actor = new actors();
        $act = $actor->find($username);
        $act->saldo -= $nominal;
        $save = $act->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function batalkanWD(Request $req)
    {
        $id = $req->id;
        $ubahSaldo = new ubahsaldos();
        $ubah = $ubahSaldo->find($id);

        $username = $ubah->username;
        $nominal  = $ubah->nominal;
        $ubah->status = 2;
        $save = $ubah->save();

        $actor = new actors();
        $act = $actor->find($username);
        $act->saldo += $nominal;
        $save = $act->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getDataKonfirmasiWD()
    {
        $ubahSaldo = new ubahsaldos();
        $data = $ubahSaldo->where('jenisUbah',"=",1)
                                ->where('status',"=",0)
                                ->orderBy('tanggal','desc')
                                ->orderBy('waktu','desc')
                                ->get();
        $arrFoto = array();
        for($i=0;$i<sizeof($data);$i++){
            $act = new actors();
            $dataAct = $act->find($data[$i]->username);
            $logo = $dataAct->logo;
            array_push($arrFoto,$logo);
        }
        $res['status']="Sukses";
        $res['data']=$data;
        $res['foto']=$arrFoto;
        return json_encode($res);
    }

    public function approveWD(Request $req)
    {
        $id = $req->id;
        $ubahSaldo = new ubahsaldos();
        $ubah = $ubahSaldo->find($id);

        $username = $ubah->username;
        $nominal  = $ubah->nominal;
        $ubah->status = 1;
        $save = $ubah->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function tolakWD(Request $req)
    {
        $id = $req->id;
        $catatan = $req->catatan;

        $ubahSaldo = new ubahsaldos();
        $ubah = $ubahSaldo->find($id);

        $username = $ubah->username;
        $nominal  = $ubah->nominal;
        $ubah->status = 3;
        $ubah->catatan = $catatan;
        $save = $ubah->save();

        $actor = new actors();
        $act = $actor->find($username);
        $act->saldo += $nominal;
        $save = $act->save();

        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getLaporanWDVendor(Request $req)
    {
        $username = $req->username;
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $status   = $req->status;
        $ubahsaldo= new ubahsaldos();

        if($status==-1){
            $data     = $ubahsaldo->where('username','=',$username)
                                    ->where('jenisUbah','=',1)
                                    ->whereDate('tanggal','>=',$tglAwal)
                                    ->whereDate('tanggal','<=',$tglAkhir)
                                    ->orderBy('tanggal','asc')
                                    ->orderBy('waktu','asc')
                                    ->get();
        }
        else{
            $data     = $ubahsaldo->where('username','=',$username)
                                    ->where('jenisUbah','=',1)
                                    ->whereDate('tanggal','>=',$tglAwal)
                                    ->whereDate('tanggal','<=',$tglAkhir)
                                    ->where('status','=',$status)
                                    ->orderBy('tanggal','asc')
                                    ->orderBy('waktu','asc')
                                    ->get(); 
        }
        
        $total = 0;
        for($i=0;$i<sizeof($data);$i++){
            if($data[$i]->status==1){
                $total+=$data[$i]->nominal;
            }
        }
        $res['status']="Sukses";
        $res['data']=$data;
        $res['total']=$total;
        return json_encode($res);
    }

    public function getLaporanWDAdmin(Request $req)
    {
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $status   = $req->status;
        $ubahsaldo= new ubahsaldos();

        if($status==-1){
            $data     = $ubahsaldo->where('jenisUbah','=',1)
                                    ->whereDate('tanggal','>=',$tglAwal)
                                    ->whereDate('tanggal','<=',$tglAkhir)
                                    ->where('status','=',1)
                                    ->orWhere('status','=',3)
                                    ->orderBy('tanggal','asc')
                                    ->orderBy('waktu','asc')
                                    ->get();
        }
        else{
            $data     = $ubahsaldo->where('jenisUbah','=',1)
                                    ->whereDate('tanggal','>=',$tglAwal)
                                    ->whereDate('tanggal','<=',$tglAkhir)
                                    ->where('status','=',$status)
                                    ->orderBy('tanggal','asc')
                                    ->orderBy('waktu','asc')
                                    ->get(); 
        }
        
        $total = 0;
        $arrFoto = array();
        for($i=0;$i<sizeof($data);$i++){
            $act = new actors();
            $dataAct = $act->find($data[$i]->username);
            $logo = $dataAct->logo;
            array_push($arrFoto,$logo);

            if($data[$i]->status==1){
                $total+=$data[$i]->nominal;
            }
        }
        $res['status']="Sukses";
        $res['data']=$data;
        $res['foto']=$arrFoto;
        $res['total']=$total;
        return json_encode($res);
    }
}
