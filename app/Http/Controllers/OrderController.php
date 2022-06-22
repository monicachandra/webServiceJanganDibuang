<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\horders;
use App\actors;
use App\dorders;
use App\dailywastes;
use App\detaildailywastes;
use App\alamats;
use App\fotos;
use App\penerimabantuans;
use App\masterbarangs;
use App\globalsettings;

class OrderController extends Controller
{
    //
    public function checkOut(Request $req)
    {
        $globalset = new globalsettings();
        $persen    = $globalset->find(1)->appFee;

        $cart = $req->cart;
        $orderan = json_decode($cart);
        $usernamePembeli    = $orderan->usernamePembeli;
        $usernamePenjual    = $orderan->usernamePenjual;
        $totalHargaBarang   = $orderan->totalHargaBarang;
        $biayaOngkir        = $orderan->biayaOngkir;
        $isDonasi           = $orderan->isDonasi;
        $idDonasi           = $orderan->idDonasi;
        $idAlamat           = $orderan->idAlamat;
        $jenisPengiriman    = $orderan->jenisPengiriman;
        if($req->infoTambahan){
            $informasiTambahanPengiriman = $req->infoTambahan;
        }
        else{
            $informasiTambahanPengiriman = "-";
        }
        $statusPengiriman   = 1;
        $detailOrder = json_decode($orderan->arrDetailShoppingCart);

        $status="Sukses";
        $lanjutCheckOut = true;
        
        for($i=0;$i<sizeof($detailOrder);$i++){
            $idArrayOrder = $detailOrder[$i]->idBarang;
            $qtyBeli      = $detailOrder[$i]->qty;
            $daily = new dailywastes();
            $stokDaily = $daily->find($idArrayOrder)->realStok;
            if($qtyBeli>$stokDaily){
                $lanjutCheckOut = false;
            }
        }
        
        if($lanjutCheckOut==true){
            $horder = new horders();
            $horder->tanggalOrder           = date("Y-m-d");
            $horder->waktuOrder             = date("H:i:s");
            $horder->usernamePembeli        = $usernamePembeli;
            $horder->usernamePenjual        = $usernamePenjual;
            $horder->totalHargaBarang       = $totalHargaBarang;
            $horder->biayaOngkir            = $biayaOngkir;
            $horder->isDonasi               = $isDonasi;
            $horder->idDonasi               = $idDonasi;
            $horder->idAlamat               = $idAlamat;
            $horder->jenisPengiriman        = $jenisPengiriman;
            $horder->informasiTambahanPengiriman = $informasiTambahanPengiriman;
            $horder->informasiTambahanPenjual='-';
            $horder->statusPengiriman       = $statusPengiriman;
            $horder->appFee                 = $persen;
            $horder->comment                = '';
            $save                           = $horder->save();

            $idParent = $horder->idHOrder;

            for($i=0;$i<sizeof($detailOrder);$i++){
                $det                = new dorders();
                $det->idHOrder      = $idParent;
                $det->idDailyWaste  = $detailOrder[$i]->idBarang;
                $det->qty           = $detailOrder[$i]->qty;
                $det->harga         = $detailOrder[$i]->harga;
                $det->comment       = '';
                $saveDetailOrder    = $det->save();

                $daily              = new dailywastes();
                $dataUbah           = $daily->find($detailOrder[$i]->idBarang);
                $stokskg            = $dataUbah->realStok;
                $stokskg           -= $detailOrder[$i]->qty;
                $dataUbah->realStok = $stokskg;
                $saveUpd            = $dataUbah->save();
            }

            //kurangi saldo
            $actor      = new actors();
            $act        = $actor->find($usernamePembeli);
            $saldo      = $act->saldo;
            $saldo     -= $totalHargaBarang;
            $saldo     -= $biayaOngkir;
            $act->saldo = $saldo;
            $saveAct    = $act->save();

            //tambah saldo penjual
            $act        = $actor->find($usernamePenjual);
            $saldo      = $act->saldo;
            $totalPembelian = $totalHargaBarang*(100-$persen)/100;
            $totalPembelian+=$biayaOngkir;
            $saldo     += $totalPembelian;
            $act->saldo = $saldo;
            $saveAct    = $act->save();

            //tambah saldo Aplikasi
            $globalset  = new globalsettings();
            $dataGlobal = $globalset->find(1);
            $saldo      = $dataGlobal->saldoAplikasi;
            $totalPembelian = $totalHargaBarang*$persen/100;
            $saldo     += $totalPembelian;
            $dataGlobal->saldoAplikasi = $saldo;
            $saveGlobal = $dataGlobal->save();
        }
        else{
            $status="Terjadi Penyesuaian Cart karena Stok tidak Mencukupi";
        }
        $res['status']=$status;
        return json_encode($res);
    }

    public function getListOrderan(Request $req)
    {
        $username = $req->username;
        $jenisPengiriman = $req->jenisPengiriman;
        $tanggal  = date("Y-m-d");
        $arrAlamat = array();
        $arrCP     = array();

        $horder     = new horders();
        $dataOrder  = $horder->where('usernamePenjual','=',$username)
                             ->where('tanggalOrder','=',$tanggal)
                             ->where('statusPengiriman','<>',5)
                             ->where('jenisPengiriman','=',$jenisPengiriman)
                             ->orderBy('waktuOrder','asc')
                             ->get();
        
        for($i=0;$i<sizeof($dataOrder);$i++){
            $CP="";
            $notelp="";
            if($dataOrder[$i]['jenisPengiriman']!=0){
                if($dataOrder[$i]['isDonasi']==0){
                    //buat sendiri
                    $alamat  = new alamats();
                    $almt    = $alamat->find($dataOrder[$i]['idAlamat'])['alamat'];
                    $kota    = $alamat->find($dataOrder[$i]['idAlamat'])['kota'];
                    $kodePos = $alamat->find($dataOrder[$i]['idAlamat'])['kodePos'];
                    $rangkai = $almt." - ".$kota.", ".$kodePos;
                    $CP      = $dataOrder[$i]['usernamePembeli'];
                    $actor   = new actors();
                    $notelp  = $actor->find($CP)['noHP'];
                    array_push($arrAlamat,$rangkai);
                }
                else{
                    //buat donasi
                    $pnb        = new penerimabantuans();
                    $namaBantuan= $pnb->find($dataOrder[$i]['idDonasi'])['namaPenerimaBantuan'];
                    $bantuan    = $pnb->find($dataOrder[$i]['idDonasi'])['alamat'];
                    $kota       = $pnb->find($dataOrder[$i]['idDonasi'])['kota'];
                    $kodePos    = $pnb->find($dataOrder[$i]['idDonasi'])['kodePos'];
                    $CP         = $pnb->find($dataOrder[$i]['idDonasi'])['contactPerson'];
                    $notelp     = $pnb->find($dataOrder[$i]['idDonasi'])['noTelp'];
                    $rangkai = $namaBantuan." (".$bantuan." - ".$kota.", ".$kodePos.")";
                    array_push($arrAlamat,$rangkai);
                }
            }
            else{
                array_push($arrAlamat,"-");
            }
            $rangkaiCP = $CP." - ".$notelp;
            array_push($arrCP,$rangkaiCP);
        }
        $res['data'] = $dataOrder;
        $res['cp'] = $arrCP;
        $res['alamat'] = $arrAlamat;
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function getListOrderanLaporan(Request $req)
    {
        $username = $req->username;
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $jenisPengiriman = $req->jenisPengiriman;
        $arrAlamat = array();
        $arrCP     = array();

        $horder     = new horders();
        if($jenisPengiriman==-1){
            $dataOrder  = $horder->where('usernamePenjual','=',$username)
                                ->whereDate('tanggalOrder','>=',$tglAwal)
                                ->whereDate('tanggalOrder','<=',$tglAkhir)
                                ->orderBy('tanggalOrder','asc')
                                ->orderBy('waktuOrder','asc')
                                ->get();
        }
        else{
            $dataOrder  = $horder->where('usernamePenjual','=',$username)
                                ->where('jenisPengiriman','=',$jenisPengiriman)
                                ->whereDate('tanggalOrder','>=',$tglAwal)
                                ->whereDate('tanggalOrder','<=',$tglAkhir)
                                ->orderBy('tanggalOrder','asc')
                                ->orderBy('waktuOrder','asc')
                                ->get();
        }
        
        for($i=0;$i<sizeof($dataOrder);$i++){
            $CP="";
            $notelp="";
            if($dataOrder[$i]['jenisPengiriman']!=0){
                if($dataOrder[$i]['isDonasi']==0){
                    //buat sendiri
                    $alamat  = new alamats();
                    $almt    = $alamat->find($dataOrder[$i]['idAlamat'])['alamat'];
                    $kota    = $alamat->find($dataOrder[$i]['idAlamat'])['kota'];
                    $kodePos = $alamat->find($dataOrder[$i]['idAlamat'])['kodePos'];
                    $rangkai = $almt." - ".$kota.", ".$kodePos;
                    $CP      = $dataOrder[$i]['usernamePembeli'];
                    $actor   = new actors();
                    $notelp  = $actor->find($CP)['noHP'];
                    array_push($arrAlamat,$rangkai);
                }
                else{
                    //buat donasi
                    $pnb        = new penerimabantuans();
                    $namaBantuan= $pnb->find($dataOrder[$i]['idDonasi'])['namaPenerimaBantuan'];
                    $bantuan    = $pnb->find($dataOrder[$i]['idDonasi'])['alamat'];
                    $kota       = $pnb->find($dataOrder[$i]['idDonasi'])['kota'];
                    $kodePos    = $pnb->find($dataOrder[$i]['idDonasi'])['kodePos'];
                    $CP         = $pnb->find($dataOrder[$i]['idDonasi'])['contactPerson'];
                    $notelp     = $pnb->find($dataOrder[$i]['idDonasi'])['noTelp'];
                    $rangkai = $namaBantuan." (".$bantuan." - ".$kota.", ".$kodePos.")";
                    array_push($arrAlamat,$rangkai);
                }
            }
            else{
                array_push($arrAlamat,"-");
            }
            $rangkaiCP = $CP." - ".$notelp;
            array_push($arrCP,$rangkaiCP);
        }
        $res['data'] = $dataOrder;
        $res['cp'] = $arrCP;
        $res['alamat'] = $arrAlamat;
        $res['status'] = "Sukses";
        return json_encode($res);
    }
    public function getListOrderanLaporanPembeli(Request $req)
    {
        $username = $req->username;
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $jenisPengiriman = $req->jenisPengiriman;
        $arrAlamat = array();
        $arrCP     = array();

        $horder     = new horders();
        if($jenisPengiriman==-1){
            $dataOrder  = $horder->where('usernamePembeli','=',$username)
                                ->whereDate('tanggalOrder','>=',$tglAwal)
                                ->whereDate('tanggalOrder','<=',$tglAkhir)
                                ->orderBy('tanggalOrder','asc')
                                ->orderBy('waktuOrder','asc')
                                ->get();
        }
        else{
            $dataOrder  = $horder->where('usernamePembeli','=',$username)
                                ->where('jenisPengiriman','=',$jenisPengiriman)
                                ->whereDate('tanggalOrder','>=',$tglAwal)
                                ->whereDate('tanggalOrder','<=',$tglAkhir)
                                ->orderBy('tanggalOrder','asc')
                                ->orderBy('waktuOrder','asc')
                                ->get();
        }
        
        for($i=0;$i<sizeof($dataOrder);$i++){
            $CP="";
            $notelp="";
            if($dataOrder[$i]['jenisPengiriman']!=0){
                if($dataOrder[$i]['isDonasi']==0){
                    //buat sendiri
                    $alamat  = new alamats();
                    $almt    = $alamat->find($dataOrder[$i]['idAlamat'])['alamat'];
                    $kota    = $alamat->find($dataOrder[$i]['idAlamat'])['kota'];
                    $kodePos = $alamat->find($dataOrder[$i]['idAlamat'])['kodePos'];
                    $rangkai = $almt." - ".$kota.", ".$kodePos;
                    $CP      = $dataOrder[$i]['usernamePembeli'];
                    $actor   = new actors();
                    $notelp  = $actor->find($CP)['noHP'];
                    array_push($arrAlamat,$rangkai);
                }
                else{
                    //buat donasi
                    $pnb        = new penerimabantuans();
                    $namaBantuan= $pnb->find($dataOrder[$i]['idDonasi'])['namaPenerimaBantuan'];
                    $bantuan    = $pnb->find($dataOrder[$i]['idDonasi'])['alamat'];
                    $kota       = $pnb->find($dataOrder[$i]['idDonasi'])['kota'];
                    $kodePos    = $pnb->find($dataOrder[$i]['idDonasi'])['kodePos'];
                    $CP         = $pnb->find($dataOrder[$i]['idDonasi'])['contactPerson'];
                    $notelp     = $pnb->find($dataOrder[$i]['idDonasi'])['noTelp'];
                    $rangkai = $namaBantuan." (".$bantuan." - ".$kota.", ".$kodePos.")";
                    array_push($arrAlamat,$rangkai);
                }
            }
            else{
                array_push($arrAlamat,"-");
            }
            $rangkaiCP = $CP." - ".$notelp;
            array_push($arrCP,$rangkaiCP);
        }
        $res['data'] = $dataOrder;
        $res['cp'] = $arrCP;
        $res['alamat'] = $arrAlamat;
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function getDetailListOrderan(Request $req)
    {
        $idHOrder = $req->horder;
        $dorder   = new dorders();
        $det      = $dorder->where('idHOrder','=',$idHOrder)
                           ->get();
        $arr      = array();
        for($i=0;$i<sizeof($det);$i++){
            $idDOrder = $det[$i]->idDOrder;
            $idBarangWaste = $det[$i]->idDailyWaste;
            $rating   = $det[$i]->rating;
            $comment   = $det[$i]->comment;
            $dwaste   = new dailywastes();
            $waste    = $dwaste->find($idBarangWaste);
            $isPaket  = $waste['isPaket'];
            $arrFoto = array();
            if($isPaket==0){
                //bukanPaket
                $idBarang   = $waste['idMasterBarang'];
                $namaBarang = $waste['namaPaket'];

                $foto     = new fotos();
                $addressFoto = "default.jpg";
                $datafoto = $foto->where("idBarang","=",$idBarang)->get();
                if(sizeof($datafoto)>0){
                    $addressFoto = $datafoto[0]['foto'];
                }
                array_push($arrFoto,$addressFoto);
                $elementBaru = array("idDailyWaste"=>$idBarangWaste, "isPaket"=>$isPaket, "idBarang"=>$idBarang,
                                    "namaBarang"=>$namaBarang,"qty"=>$det[$i]->qty,"harga"=>$det[$i]->harga,
                                    "foto"=>$arrFoto,"detailPaket"=>[],"rating"=>$rating,"comment"=>$comment,"idDOrder"=>$idDOrder);
                array_push($arr,$elementBaru);
            }
            else{
                //paket
                $idBarang   = $waste['idMasterBarang'];
                $namaPaket = $waste['namaPaket'];
                $detaildailyItem = new detaildailywastes();
                $datadetail = $detaildailyItem->where('idDailyWaste','=',$idBarangWaste)
                                              ->get();
                $detailPaket = array();
                for($j=0;$j<sizeof($datadetail);$j++){
                    $idMasterBrg = $datadetail[$j]['idMasterBarang'];
                    $qty         = $datadetail[$j]['qty'];
                    $brg         = new masterbarangs();
                    $nm          = $brg->find($idMasterBrg)['namaBarang'];
                    $elm         = array("idBarang"=>$idMasterBrg,"namaBarang"=>$nm,"qty"=>$qty);
                    array_push($detailPaket,$elm);
                    
                    $foto     = new fotos();
                    $addressFoto = "default.jpg";
                    $datafoto = $foto->where("idBarang","=",$idMasterBrg)->get();
                    if(sizeof($datafoto)>0){
                        $addressFoto = $datafoto[0]['foto'];
                    }
                    array_push($arrFoto,$addressFoto);
                }
                $elementBaru = array("idDailyWaste"=>$idBarangWaste, "isPaket"=>$isPaket, "idBarang"=>$idBarang,
                                    "namaBarang"=>$namaPaket,"qty"=>$det[$i]->qty,"harga"=>$det[$i]->harga,
                                    "foto"=>$arrFoto,"detailPaket"=>$detailPaket,"rating"=>$rating,"comment"=>$comment,
                                    "idDOrder"=>$idDOrder);
                array_push($arr,$elementBaru);
            }
        }
        $res['data'] = $arr;
        $res['status'] = "Sukses";
        return json_encode($res);
    }
    public function updateHOrderPenjual(Request $req)
    {
        $id = $req->idHOrder;
        $info = $req->informasi;

        $order = new horders();
        $dataOrder = $order->find($id);
        $status = $dataOrder->statusPengiriman;
        $status += 1;
        $dataOrder->statusPengiriman = $status;
        $dataOrder->informasiTambahanPenjual = $info;
        $save = $dataOrder->save();

        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function sudahDiambil(Request $req)
    {
        $id = $req->idHOrder;

        $order = new horders();
        $dataOrder = $order->find($id);
        $status = 5;
        $dataOrder->statusPengiriman = $status;
        $save = $dataOrder->save();

        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function getListOrderanPembeli(Request $req)
    {
        $username = $req->username;
        $jenisPengiriman = $req->jenisPengiriman;
        $tanggal  = date("Y-m-d");

        $arrAlamat = array();
        $arrCP     = array();

        $horder     = new horders();
        
        $dataOrder  = $horder->where('usernamePembeli','=',$username)
                             ->where('tanggalOrder','=',$tanggal)
                             ->where('jenisPengiriman','=',$jenisPengiriman)
                             ->orderBy('tanggalOrder','asc')
                             ->orderBy('waktuOrder','desc')
                             ->get();
        
        for($i=0;$i<sizeof($dataOrder);$i++){
            $CP="";
            $notelp="";
            if($dataOrder[$i]['jenisPengiriman']!=0){
                if($dataOrder[$i]['isDonasi']==0){
                    //buat sendiri
                    $alamat  = new alamats();
                    $almt    = $alamat->find($dataOrder[$i]['idAlamat'])['alamat'];
                    $kota    = $alamat->find($dataOrder[$i]['idAlamat'])['kota'];
                    $kodePos = $alamat->find($dataOrder[$i]['idAlamat'])['kodePos'];
                    $rangkai = $almt." - ".$kota.", ".$kodePos;
                    $CP      = $dataOrder[$i]['usernamePembeli'];
                    $actor   = new actors();
                    $notelp  = $actor->find($CP)['noHP'];
                    array_push($arrAlamat,$rangkai);
                }
                else{
                    //buat donasi
                    $pnb        = new penerimabantuans();
                    $namaBantuan= $pnb->find($dataOrder[$i]['idDonasi'])['namaPenerimaBantuan'];
                    $bantuan    = $pnb->find($dataOrder[$i]['idDonasi'])['alamat'];
                    $kota       = $pnb->find($dataOrder[$i]['idDonasi'])['kota'];
                    $kodePos    = $pnb->find($dataOrder[$i]['idDonasi'])['kodePos'];
                    $CP         = $pnb->find($dataOrder[$i]['idDonasi'])['contactPerson'];
                    $notelp     = $pnb->find($dataOrder[$i]['idDonasi'])['noTelp'];
                    $rangkai = $namaBantuan." (".$bantuan." - ".$kota.", ".$kodePos.")";
                    array_push($arrAlamat,$rangkai);
                }
            }
            else{
                array_push($arrAlamat,"-");
            }
            $rangkaiCP = $CP." - ".$notelp;
            array_push($arrCP,$rangkaiCP);
        }
        $res['data']   = $dataOrder;
        $res['cp']     = $arrCP;
        $res['alamat'] = $arrAlamat;
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function getListOrderanPembeliDonasi(Request $req)
    {
        $username = $req->username;
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;

        $arrAlamat = array();
        $arrCP     = array();

        $horder     = new horders();
        $dataOrder  = $horder->where('usernamePembeli','=',$username)
                             ->where('isDonasi','=',1)
                             ->whereDate('tanggalOrder','>=',$tglAwal)
                             ->whereDate('tanggalOrder','<=',$tglAkhir)
                             ->orderBy('tanggalOrder','asc')
                             ->orderBy('waktuOrder','desc')
                             ->get();
        
        for($i=0;$i<sizeof($dataOrder);$i++){
            $CP="";
            $notelp="";
            if($dataOrder[$i]['jenisPengiriman']!=0){
                if($dataOrder[$i]['isDonasi']==0){
                    //buat sendiri
                    $alamat  = new alamats();
                    $almt    = $alamat->find($dataOrder[$i]['idAlamat'])['alamat'];
                    $kota    = $alamat->find($dataOrder[$i]['idAlamat'])['kota'];
                    $kodePos = $alamat->find($dataOrder[$i]['idAlamat'])['kodePos'];
                    $rangkai = $almt." - ".$kota.", ".$kodePos;
                    $CP      = $dataOrder[$i]['usernamePembeli'];
                    $actor   = new actors();
                    $notelp  = $actor->find($CP)['noHP'];
                    array_push($arrAlamat,$rangkai);
                }
                else{
                    //buat donasi
                    $pnb        = new penerimabantuans();
                    $namaBantuan= $pnb->find($dataOrder[$i]['idDonasi'])['namaPenerimaBantuan'];
                    $bantuan    = $pnb->find($dataOrder[$i]['idDonasi'])['alamat'];
                    $kota       = $pnb->find($dataOrder[$i]['idDonasi'])['kota'];
                    $kodePos    = $pnb->find($dataOrder[$i]['idDonasi'])['kodePos'];
                    $CP         = $pnb->find($dataOrder[$i]['idDonasi'])['contactPerson'];
                    $notelp     = $pnb->find($dataOrder[$i]['idDonasi'])['noTelp'];
                    $rangkai = $namaBantuan." (".$bantuan." - ".$kota.", ".$kodePos.")";
                    array_push($arrAlamat,$rangkai);
                }
            }
            else{
                array_push($arrAlamat,"-");
            }
            $rangkaiCP = $CP." - ".$notelp;
            array_push($arrCP,$rangkaiCP);
        }
        $res['data']   = $dataOrder;
        $res['cp']     = $arrCP;
        $res['alamat'] = $arrAlamat;
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function getListOrderanPembeliLaporan(Request $req)
    {
        $username = $req->username;
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;

        $arrAlamat = array();
        $arrCP     = array();

        $horder     = new horders();
        $dataOrder  = $horder->where('usernamePembeli','=',$username)
                             ->whereDate('tanggalOrder','>=',$tglAwal)
                             ->whereDate('tanggalOrder','<=',$tglAkhir)
                             ->orderBy('tanggalOrder','asc')
                             ->orderBy('waktuOrder','desc')
                             ->get();
        
        for($i=0;$i<sizeof($dataOrder);$i++){
            $CP="";
            $notelp="";
            if($dataOrder[$i]['jenisPengiriman']!=0){
                if($dataOrder[$i]['isDonasi']==0){
                    //buat sendiri
                    $alamat  = new alamats();
                    $almt    = $alamat->find($dataOrder[$i]['idAlamat'])['alamat'];
                    $kota    = $alamat->find($dataOrder[$i]['idAlamat'])['kota'];
                    $kodePos = $alamat->find($dataOrder[$i]['idAlamat'])['kodePos'];
                    $rangkai = $almt." - ".$kota.", ".$kodePos;
                    $CP      = $dataOrder[$i]['usernamePembeli'];
                    $actor   = new actors();
                    $notelp  = $actor->find($CP)['noHP'];
                    array_push($arrAlamat,$rangkai);
                }
                else{
                    //buat donasi
                    $pnb        = new penerimabantuans();
                    $namaBantuan= $pnb->find($dataOrder[$i]['idDonasi'])['namaPenerimaBantuan'];
                    $bantuan    = $pnb->find($dataOrder[$i]['idDonasi'])['alamat'];
                    $kota       = $pnb->find($dataOrder[$i]['idDonasi'])['kota'];
                    $kodePos    = $pnb->find($dataOrder[$i]['idDonasi'])['kodePos'];
                    $CP         = $pnb->find($dataOrder[$i]['idDonasi'])['contactPerson'];
                    $notelp     = $pnb->find($dataOrder[$i]['idDonasi'])['noTelp'];
                    $rangkai = $namaBantuan." (".$bantuan." - ".$kota.", ".$kodePos.")";
                    array_push($arrAlamat,$rangkai);
                }
            }
            else{
                array_push($arrAlamat,"-");
            }
            $rangkaiCP = $CP." - ".$notelp;
            array_push($arrCP,$rangkaiCP);
        }
        $res['data']   = $dataOrder;
        $res['cp']     = $arrCP;
        $res['alamat'] = $arrAlamat;
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function uploadRatingComment(Request $req)
    {
        $idHOrder = $req->idHOrder;
        $ratingAll = $req->ratingAll;
        $komentarAll = $req->komentarAll;
        $arr = json_decode($req->arr);
        
        $horder = new horders();
        $headerOrder = $horder->find($idHOrder);
        $headerOrder->rating  = $ratingAll;
        $headerOrder->comment = $komentarAll;
        $save = $headerOrder->save();

        for($i=0;$i<sizeof($arr);$i++){
            $dorder  = new dorders();
            $detailO = $dorder->find($arr[$i]->id);
            $detailO->rating = $arr[$i]->rating;
            $detailO->comment= $arr[$i]->komentar;
            $save = $detailO->save();
        }
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function getPendapatanAdmin(Request $req)
    {
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;

        $arrAlamat = array();
        $arrCP     = array();

        $horder     = new horders();
        $dataOrder  = $horder->whereDate('tanggalOrder','>=',$tglAwal)
                             ->whereDate('tanggalOrder','<=',$tglAkhir)
                             ->orderBy('tanggalOrder','asc')
                             ->orderBy('waktuOrder','desc')
                             ->get();
        
        for($i=0;$i<sizeof($dataOrder);$i++){
            $CP="";
            $notelp="";
            if($dataOrder[$i]['jenisPengiriman']!=0){
                if($dataOrder[$i]['isDonasi']==0){
                    //buat sendiri
                    $alamat  = new alamats();
                    $almt    = $alamat->find($dataOrder[$i]['idAlamat'])['alamat'];
                    $kota    = $alamat->find($dataOrder[$i]['idAlamat'])['kota'];
                    $kodePos = $alamat->find($dataOrder[$i]['idAlamat'])['kodePos'];
                    $rangkai = $almt." - ".$kota.", ".$kodePos;
                    $CP      = $dataOrder[$i]['usernamePembeli'];
                    $actor   = new actors();
                    $notelp  = $actor->find($CP)['noHP'];
                    array_push($arrAlamat,$rangkai);
                }
                else{
                    //buat donasi
                    $pnb        = new penerimabantuans();
                    $namaBantuan= $pnb->find($dataOrder[$i]['idDonasi'])['namaPenerimaBantuan'];
                    $bantuan    = $pnb->find($dataOrder[$i]['idDonasi'])['alamat'];
                    $kota       = $pnb->find($dataOrder[$i]['idDonasi'])['kota'];
                    $kodePos    = $pnb->find($dataOrder[$i]['idDonasi'])['kodePos'];
                    $CP         = $pnb->find($dataOrder[$i]['idDonasi'])['contactPerson'];
                    $notelp     = $pnb->find($dataOrder[$i]['idDonasi'])['noTelp'];
                    $rangkai = $namaBantuan." (".$bantuan." - ".$kota.", ".$kodePos.")";
                    array_push($arrAlamat,$rangkai);
                }
            }
            else{
                array_push($arrAlamat,"-");
            }
            $rangkaiCP = $CP." - ".$notelp;
            array_push($arrCP,$rangkaiCP);
        }
        $res['data']   = $dataOrder;
        $res['cp']     = $arrCP;
        $res['alamat'] = $arrAlamat;
        $res['status'] = "Sukses";
        return json_encode($res);
    }
    public function getPenjualanTerbanyak(Request $req)
    {
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $jenisPengurutan = $req->jenisPengurutan;

        $act = new actors();
        $dataActor = $act->where("tipeActor","=","V")
                         ->where("status","=",1)
                         ->get();
        $arrPenjual = array();
        for($i=0;$i<sizeof($dataActor);$i++){
            $nominal = 0;
            $terjual = 0;
            $logo    = $dataActor[$i]->logo;
            $horder    = new horders();
            $dataOrder = $horder->where("usernamePenjual","=",$dataActor[$i]->username)
                                ->whereDate('tanggalOrder','>=',$tglAwal)
                                ->whereDate('tanggalOrder','<=',$tglAkhir)
                                ->get();
            if($dataOrder!=null){
                for($j=0;$j<sizeof($dataOrder);$j++){
                    $nominal += $dataOrder[$j]->totalHargaBarang;
                    $dorder  = new dorders();
                    $detOrder= $dorder->where("idHOrder","=",$dataOrder[$j]->idHOrder)->get();
                    if($detOrder!=null){
                        for($k=0;$k<sizeof($detOrder);$k++){
                            $terjual += $detOrder[$k]->qty;
                        }
                    }
                }
            }
            $elementBaru=array("username"=>$dataActor[$i]->username,"nominal"=>$nominal,"terjual"=>$terjual,"logo"=>$logo);
            array_push($arrPenjual,$elementBaru);
        }
        if($jenisPengurutan==0){
            usort($arrPenjual, function ($a, $b) {
                return $b["terjual"] - $a["terjual"];
            });
        }
        else{
            usort($arrPenjual, function ($a, $b) {
                return $b["nominal"] - $a["nominal"];
            });
        }
        $res['data']   = $arrPenjual;
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function getPembelianTerbanyak(Request $req)
    {
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $jenisPengurutan = $req->jenisPengurutan;

        $act = new actors();
        $dataActor = $act->where("tipeActor","=","P")
                         ->where("isVerified","=",1)
                         ->get();
        $arrPenjual = array();
        for($i=0;$i<sizeof($dataActor);$i++){
            $nominal = 0;
            $terjual = 0;
            $logo    = $dataActor[$i]->logo;
            $horder    = new horders();
            $dataOrder = $horder->where("usernamePembeli","=",$dataActor[$i]->username)
                                ->whereDate('tanggalOrder','>=',$tglAwal)
                                ->whereDate('tanggalOrder','<=',$tglAkhir)
                                ->get();
            if($dataOrder!=null){
                for($j=0;$j<sizeof($dataOrder);$j++){
                    $nominal += $dataOrder[$j]->totalHargaBarang;
                    $dorder  = new dorders();
                    $detOrder= $dorder->where("idHOrder","=",$dataOrder[$j]->idHOrder)->get();
                    if($detOrder!=null){
                        for($k=0;$k<sizeof($detOrder);$k++){
                            $terjual += $detOrder[$k]->qty;
                        }
                    }
                }
            }
            $elementBaru=array("username"=>$dataActor[$i]->username,"nominal"=>$nominal,"terjual"=>$terjual,"logo"=>$logo);
            array_push($arrPenjual,$elementBaru);
        }
        if($jenisPengurutan==0){
            usort($arrPenjual, function ($a, $b) {
                return $b["terjual"] - $a["terjual"];
            });
        }
        else{
            usort($arrPenjual, function ($a, $b) {
                return $b["nominal"] - $a["nominal"];
            });
        }
        $res['data']   = $arrPenjual;
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function getListOrderanAdminDonasi(Request $req)
    {
        $id = $req->id;
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $arrAlamat = array();
        $arrCP     = array();

        $horder     = new horders();
        $dataOrder  = $horder->where('idDonasi','=',$id)
                                ->whereDate('tanggalOrder','>=',$tglAwal)
                                ->whereDate('tanggalOrder','<=',$tglAkhir)
                                ->orderBy('tanggalOrder','asc')
                                ->orderBy('waktuOrder','asc')
                                ->get();
        
        for($i=0;$i<sizeof($dataOrder);$i++){
            $CP="";
            $notelp="";
            if($dataOrder[$i]['jenisPengiriman']!=0){
                if($dataOrder[$i]['isDonasi']==0){
                    //buat sendiri
                    $alamat  = new alamats();
                    $almt    = $alamat->find($dataOrder[$i]['idAlamat'])['alamat'];
                    $kota    = $alamat->find($dataOrder[$i]['idAlamat'])['kota'];
                    $kodePos = $alamat->find($dataOrder[$i]['idAlamat'])['kodePos'];
                    $rangkai = $almt." - ".$kota.", ".$kodePos;
                    $CP      = $dataOrder[$i]['usernamePembeli'];
                    $actor   = new actors();
                    $notelp  = $actor->find($CP)['noHP'];
                    array_push($arrAlamat,$rangkai);
                }
                else{
                    //buat donasi
                    $pnb        = new penerimabantuans();
                    $namaBantuan= $pnb->find($dataOrder[$i]['idDonasi'])['namaPenerimaBantuan'];
                    $bantuan    = $pnb->find($dataOrder[$i]['idDonasi'])['alamat'];
                    $kota       = $pnb->find($dataOrder[$i]['idDonasi'])['kota'];
                    $kodePos    = $pnb->find($dataOrder[$i]['idDonasi'])['kodePos'];
                    $CP         = $pnb->find($dataOrder[$i]['idDonasi'])['contactPerson'];
                    $notelp     = $pnb->find($dataOrder[$i]['idDonasi'])['noTelp'];
                    $rangkai = $namaBantuan." (".$bantuan." - ".$kota.", ".$kodePos.")";
                    array_push($arrAlamat,$rangkai);
                }
            }
            else{
                array_push($arrAlamat,"-");
            }
            $rangkaiCP = $CP." - ".$notelp;
            array_push($arrCP,$rangkaiCP);
        }
        $res['data'] = $dataOrder;
        $res['cp'] = $arrCP;
        $res['alamat'] = $arrAlamat;
        $res['status'] = "Sukses";
        return json_encode($res);
    }
}
