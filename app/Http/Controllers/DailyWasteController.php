<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\dailywastes;
use App\detaildailywastes;
use App\masterbarangs;
use App\fotos;
use App\actors;
use App\horders;
use App\dorders;
use App\alamats;
use App\penerimabantuans;
use App\globalsettings;
use App\kategoris;
use App\adjuststoks;

class DailyWasteController extends Controller
{
    public function simpanItem(Request $req)
    {
        $username = $req->username;
        $arr      = $req->chosenItem;

        $itemToday= new dailywastes();
        $dataToday= $itemToday->where('username','=',$username)
                              ->where('tanggal','=',date("Y-m-d"))
                              ->get();

        $pecahArr = json_decode($arr);
        for($i=0;$i<sizeof($pecahArr);$i++){
            $ada=-1;
            for($j=0;$j<sizeof($dataToday);$j++){
                if($pecahArr[$i]->idBarang==$dataToday[$j]['idMasterBarang']){
                    $ada=$dataToday[$j]['idDailyWaste'];

                }
            }
            $dailyItem = new dailywastes();
            if($ada==-1){
                $dailyItem->isPaket = 0;
                $dailyItem->namaPaket = $pecahArr[$i]->nama;
                $dailyItem->idMasterBarang = $pecahArr[$i]->idBarang;
                $dailyItem->username = $username;
                $dailyItem->stok = intval($pecahArr[$i]->stok);
                $dailyItem->realStok = intval($pecahArr[$i]->stok);
                $dailyItem->tanggal = date("Y-m-d");
                $dailyItem->waktu = date("H:i:s");
                $dailyItem->hargaAsli = $pecahArr[$i]->hargaAsli;
                $dailyItem->hargaWaste = $pecahArr[$i]->hargaWaste;
                $dailyItem->save();
            }
            else{
                $dataNow = $dailyItem->find($ada);
                $stokNow = $dataNow['stok'];
                $stokReal= $dataNow['realStok'];
                $tambahan = intval($pecahArr[$i]->stok);
                $stokNow += $tambahan;
                $stokReal += $tambahan;
                $dataNow->stok = $stokNow;
                $dataNow->realStok = $stokReal;
                $dataNow->save();
            }
        }
        $res['status']="Sukses";
        $res['leng']=count($pecahArr);
        return json_encode($res);
    }

    public function simpanPaket(Request $req)
    {
        $username = $req->username;
        $arr      = $req->chosenItem;
        $namaPaket= $req->namaPaket;
        $stokPaket= $req->stokPaket;
        $hargaAsli= $req->hargaAsli;
        $hargaWaste= $req->hargaWaste;
        
        //simpan header;
        $dailyItem = new dailywastes();
        $dailyItem->isPaket = 1;
        $dailyItem->namaPaket = $namaPaket;
        $dailyItem->idMasterBarang = null;
        $dailyItem->username = $username;
        $dailyItem->stok = intval($stokPaket);
        $dailyItem->realStok = intval($stokPaket);
        $dailyItem->tanggal = date("Y-m-d");
        $dailyItem->waktu = date("H:i:s");
        $dailyItem->hargaAsli = $hargaAsli;
        $dailyItem->hargaWaste = $hargaWaste;
        $dailyItem->save();

        $idParent = $dailyItem->idDailyWaste;

        //simpan detail
        $pecahArr = json_decode($arr);
        for($i=0;$i<sizeof($pecahArr);$i++){
            $detaildailyItem = new detaildailywastes();
            $detaildailyItem->idDailyWaste = $idParent;
            $detaildailyItem->idMasterBarang = $pecahArr[$i]->idBarang;
            $detaildailyItem->qty = intval($pecahArr[$i]->stok/$stokPaket);
            $detaildailyItem->hargaAsli = $pecahArr[$i]->hargaAsli;
            $detaildailyItem->hargaWaste = $pecahArr[$i]->hargaWaste;
            $detaildailyItem->save();
        }
        $res['status']="Sukses";
        $res['leng']=count($pecahArr);
        return json_encode($res);
    }

    public function getItemToday(Request $req)
    {
        $username = $req->username;
        $itemToday= new dailywastes();
        $dataAll  = $itemToday->where('username','=',$username)
                              ->where('tanggal','=',date("Y-m-d"))
                              ->where('isPaket','=',0)
                              ->get();
        $res['data']=$dataAll;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getAllWaste(Request $req)
    {
        $username = $req->username;
        $waste = new dailywastes();
        $data = $waste->where("username","=",$username)
                        ->where("tanggal","=",date("Y-m-d"))
                        ->get();

        $arr = array();
        for($i=0;$i<sizeof($data);$i++){
            $idDaily = $data[$i]['idDailyWaste'];
            $isPaket = $data[$i]['isPaket'];
            $arrFoto = array();
            if($isPaket==0){
                //bukanPaket
                $idBarang   = $data[$i]['idMasterBarang'];
                $namaBarang = $data[$i]['namaPaket'];

                $master         = new masterbarangs();
                $dataMaster     = $master->find($idBarang);
                $kat            = $dataMaster->idKategori;
                $deskripsiBrg   = $dataMaster->deskripsiBarang;

                $katBrg         = new kategoris();
                $namaKat        = $katBrg->find($kat)->namaKategori;

                $stok       = $data[$i]['realStok'];
                $hargaWaste = $data[$i]['hargaWaste'];

                $foto     = new fotos();
                $addressFoto = "default.jpg";
                $datafoto = $foto->where("idBarang","=",$idBarang)->get();
                if(sizeof($datafoto)>0){
                    $addressFoto = $datafoto[0]['foto'];
                }
                array_push($arrFoto,$addressFoto);
                $dorder = new dorders();
                $detOrders = $dorder->where("idDailyWaste","=",$data[$i]['idDailyWaste'])->get();
                $ratingMakanan = 0.0;
                $counterMakanan= 0;
                $komentarMakanan = array();
                if($detOrders!=null){
                    for($k=0;$k<sizeof($detOrders);$k++){
                        if($detOrders[$k]->rating!=0.0){
                            $ratingMakanan+=$detOrders[$k]->rating;
                            $counterMakanan++;
                            $h = new horders();
                            $pem = $h->find($detOrders[$k]->idHOrder);
                            $pembeli = $pem['usernamePembeli'];
                            $komens  = $detOrders[$k]->comment;
                            $stars   = $detOrders[$k]->rating;
                            $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                            array_push($komentarMakanan,$addKomens);
                        }
                    }
                    if($counterMakanan!=0){
                        $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                    }
                    else{
                        $totalRatingMakanan = 0.0;
                    }
                }
                else{
                    $totalRatingMakanan = 0.0;
                    $komentarMakanan=[];
                }
                $elementBaru = array("idDaily"=>$idDaily, "isPaket"=>$isPaket, "idBarang"=>$idBarang,
                                    "namaBarang"=>$namaBarang,"stok"=>$stok,
                                    "hargaWaste"=>$hargaWaste,"foto"=>$arrFoto,"detailPaket"=>[],
                                    "totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                    "kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg
                                );
                array_push($arr,$elementBaru);
            }
            else{
                //paket
                $namaPaket = $data[$i]['namaPaket'];
                $stok       = $data[$i]['realStok'];
                $hargaWaste = $data[$i]['hargaWaste'];
                $detaildailyItem = new detaildailywastes();
                $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
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
                $dorder = new dorders();
                $detOrders = $dorder->where("idDailyWaste","=",$data[$i]['idDailyWaste'])->get();
                $ratingMakanan = 0.0;
                $counterMakanan= 0;
                $komentarMakanan = array();
                if($detOrders!=null){
                    for($k=0;$k<sizeof($detOrders);$k++){
                        if($detOrders[$k]->rating!=0.0){
                            $ratingMakanan+=$detOrders[$k]->rating;
                            $counterMakanan++;
                            $h = new horders();
                            $pem = $h->find($detOrders[$k]->idHOrder);
                            $pembeli = $pem['usernamePembeli'];
                            $komens  = $detOrders[$k]->comment;
                            $stars   = $detOrders[$k]->rating;
                            $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                            array_push($komentarMakanan,$addKomens);
                        }
                    }
                    if($counterMakanan!=0){
                        $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                    }
                    else{
                        $totalRatingMakanan = 0.0;
                    }
                }
                else{
                    $totalRatingMakanan = 0.0;
                    $komentarMakanan=[];
                }
                $elementBaru = array("idDaily"=>$idDaily, "isPaket"=>$isPaket, "idBarang"=>null,
                                    "namaBarang"=>$namaPaket,"stok"=>$stok,
                                    "hargaWaste"=>$hargaWaste,"foto"=>$arrFoto,"detailPaket"=>$detailPaket,
                                    "totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                    "kategori"=>"","deskripsi"=>""
                                );
                array_push($arr,$elementBaru);
            }
        }
        $res['data']=$arr;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getAllWastePembeli(Request $req)
    {
        $globalset = new globalsettings();
        $dataSetting = $globalset->find(1);
        $limitShow = $dataSetting->limitShowMakanan;

        $filter = $req->filter;
        $kodeList = $req->kodeList;
        $loginUser = $req->loginUser;
        $longitude = $req->longitude;
        $latitude = $req->latitude;

        if($kodeList==0){ //filter biasa
            $query = "select g.username from ( 
                select d.username 
                from dailywastes d 
                join masterbarangs m 
                on d.idMasterBarang=m.idBarang where date(tanggal)= DATE(NOW()) and isPaket=0 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
                
                union 
                
                select d.username 
                from dailywastes d 
                join detaildailywastes det 
                on d.idDailyWaste=det.idDailyWaste
                join masterbarangs m 
                on det.idMasterBarang=m.idBarang 
                where date(tanggal)= DATE(NOW()) 
                and isPaket=1 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
            ) g 
            group by g.username";
            $getData = \DB::select($query);

            $arr = array(); 
            if($getData){
                for($i=0;$i<sizeof($getData);$i++){
                    $username = $getData[$i]->username;
                    $act      = new actors();
                    $dataActors = $act->find($username);
                    //ambil data item;
                    $queryItem = "select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join masterbarangs m 
                    on d.idMasterBarang=m.idBarang 
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=0 
                    and m.namaBarang like '%".$filter."%'
                    and d.username='".$username."' and d.realStok>0";
                    $itemWaste = \DB::select($queryItem);
                    $arrItem=array();
                    if($itemWaste){
                        for($j=0;$j<sizeof($itemWaste);$j++){
                            $arrFoto = array();
                            $foto     = new fotos();
                            $addressFoto = "default.jpg";
                            $datafoto = $foto->where("idBarang","=",$itemWaste[$j]->idMasterBarang)->get();
                            if(sizeof($datafoto)>0){
                                $addressFoto = $datafoto[0]['foto'];
                            }
                            array_push($arrFoto,$addressFoto);

                            $master         = new masterbarangs();
                            $dataMaster     = $master->find($itemWaste[$j]->idMasterBarang);
                            $kat            = $dataMaster->idKategori;
                            $deskripsiBrg   = $dataMaster->deskripsiBarang;

                            $katBrg         = new kategoris();
                            $namaKat        = $katBrg->find($kat)->namaKategori;

                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$itemWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan= 0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem['usernamePembeli'];
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $elementBaru = array("idDailyWaste"=>$itemWaste[$j]->idDailyWaste, "isPaket"=>0, "idBarang"=>$itemWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$itemWaste[$j]->hargaAsli,"hargaWaste"=>$itemWaste[$j]->hargaWaste,
                                        "namaBarang"=>$itemWaste[$j]->namaPaket,"detailBarang"=>[],"foto"=>$arrFoto,
                                        "stok"=>$itemWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,"kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg);
                            array_push($arrItem,$elementBaru);
                        }
                    }

                    //ambil data paket
                    $queryPaket="select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join detaildailywastes det 
                    on d.idDailyWaste=det.idDailyWaste
                    join masterbarangs m 
                    on det.idMasterBarang=m.idBarang 
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=1 
                    and m.namaBarang like '%".$filter."%' 
                    and d.realStok>0
                    and d.username = '".$username."'
                    group by d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok";
                    $paketWaste = \DB::select($queryPaket);
                    if($paketWaste){
                        for($j=0;$j<sizeof($paketWaste);$j++){
                            $idDaily = $paketWaste[$j]->idDailyWaste;
                            $detaildailyItem = new detaildailywastes();
                            $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
                                                        ->get();
                            $detailPaket = array();
                            $arrFoto = array();
                            for($k=0;$k<sizeof($datadetail);$k++){
                                $idMasterBrg = $datadetail[$k]['idMasterBarang'];
                                $qty         = $datadetail[$k]['qty'];
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
                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$paketWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan=0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem->usernamePembeli;
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $elementBaru = array("idDailyWaste"=>$paketWaste[$j]->idDailyWaste, "isPaket"=>1, "idBarang"=>$paketWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$paketWaste[$j]->hargaAsli,"hargaWaste"=>$paketWaste[$j]->hargaWaste,
                                        "namaBarang"=>$paketWaste[$j]->namaPaket,"detailBarang"=>$detailPaket,"foto"=>$arrFoto,
                                        "stok"=>$paketWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,"kategori"=>"","deskripsi"=>"");
                            array_push($arrItem,$elementBaru);
                        }
                    }
                    if(sizeof($arrItem)>$limitShow){
                        $pengurangan = sizeof($arrItem)-$limitShow;
                        $res['kurang']=$pengurangan;
                        $ctrPengurangan = 0;
                        while($ctrPengurangan<$pengurangan){
                            array_splice($arrItem,sizeof($arrItem)-1,1);
                            $ctrPengurangan++;
                        }
                    }
                    $horders = new horders();
                    $headOrder = $horders->where("usernamePenjual","=",$username)->get();
                    $ratingPenjual = 0.0;
                    $counter=0;
                    $komentar = array();
                    if($headOrder!=null){
                        for($j=0;$j<sizeof($headOrder);$j++){
                            if($headOrder[$j]->rating!=0.0){
                                $ratingPenjual+=$headOrder[$j]->rating;
                                $counter++;
                                $pembeli = $headOrder[$j]->usernamePembeli;
                                $komen   = $headOrder[$j]->comment;
                                $star    = $headOrder[$j]->rating;
                                $addKomen = array("pembeli"=>$pembeli,"komentar"=>$komen,"star"=>$star);
                                array_push($komentar,$addKomen);
                            }
                        }
                        if($counter!=0){
                            $totalRating = floatval($ratingPenjual/$counter);
                        }
                        else{
                            $totalRating = 0.0;
                        }
                    }
                    else{
                        $totalRating = 0.0;
                        $komentar=[];
                    }
                    //ini nanti di bwh paket
                    $elementLuar = array("username"=>$username,"logo"=>$dataActors['logo'],"menu"=>$arrItem,"totalRating"=>$totalRating,"komentar"=>$komentar);

                    array_push($arr,$elementLuar);
                }
            }
        }
        else if($kodeList==1){ //jarak terdekat
            $query = "select g.username,g.longitude,g.latitude from ( 
                select d.username,al.longitude,al.latitude 
                from dailywastes d 
                join masterbarangs m 
                on d.idMasterBarang=m.idBarang
    			join alamats al
    			on al.username=d.username
    			where date(tanggal)= DATE(NOW()) and isPaket=0
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
                
                union 
                
                select d.username,al.longitude,al.latitude 
                from dailywastes d 
                join detaildailywastes det 
                on d.idDailyWaste=det.idDailyWaste
                join masterbarangs m 
                on det.idMasterBarang=m.idBarang 
    			join alamats al
    			on al.username=d.username
                where date(tanggal)= DATE(NOW()) 
                and isPaket=1 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
            ) g 
            group by g.username,g.longitude,g.latitude";
            $getData = \DB::select($query);

            $arr = array(); 
            if($getData){
                for($i=0;$i<sizeof($getData);$i++){
                    $username = $getData[$i]->username;
                    $longitudeT = $getData[$i]->longitude;
                    $latitudeT = $getData[$i]->latitude;
                    $jarak = sqrt((($longitude-$longitudeT)*($longitude-$longitudeT))+(($latitude-$latitudeT)*($latitude-$latitudeT)));
                    $act      = new actors();
                    $dataActors = $act->find($username);
                    //ambil data item;
                    $queryItem = "select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join masterbarangs m 
                    on d.idMasterBarang=m.idBarang 
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=0 
                    and m.namaBarang like '%".$filter."%'
                    and d.username='".$username."' and d.realStok>0";
                    $itemWaste = \DB::select($queryItem);
                    $arrItem=array();
                    if($itemWaste){
                        for($j=0;$j<sizeof($itemWaste);$j++){
                            $arrFoto = array();
                            $foto     = new fotos();
                            $addressFoto = "default.jpg";
                            $datafoto = $foto->where("idBarang","=",$itemWaste[$j]->idMasterBarang)->get();
                            if(sizeof($datafoto)>0){
                                $addressFoto = $datafoto[0]['foto'];
                            }
                            array_push($arrFoto,$addressFoto);

                            $master         = new masterbarangs();
                            $dataMaster     = $master->find($itemWaste[$j]->idMasterBarang);
                            $kat            = $dataMaster->idKategori;
                            $deskripsiBrg   = $dataMaster->deskripsiBarang;

                            $katBrg         = new kategoris();
                            $namaKat        = $katBrg->find($kat)->namaKategori;

                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$itemWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan= 0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem['usernamePembeli'];
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $elementBaru = array("idDailyWaste"=>$itemWaste[$j]->idDailyWaste, "isPaket"=>0, "idBarang"=>$itemWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$itemWaste[$j]->hargaAsli,"hargaWaste"=>$itemWaste[$j]->hargaWaste,
                                        "namaBarang"=>$itemWaste[$j]->namaPaket,"detailBarang"=>[],"foto"=>$arrFoto,
                                        "stok"=>$itemWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,"kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg);
                            array_push($arrItem,$elementBaru);
                        }
                    }

                    //ambil data paket
                    $queryPaket="select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join detaildailywastes det 
                    on d.idDailyWaste=det.idDailyWaste
                    join masterbarangs m 
                    on det.idMasterBarang=m.idBarang 
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=1 
                    and m.namaBarang like '%".$filter."%' 
                    and d.realStok>0
                    and d.username = '".$username."'
                    group by d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok";
                    $paketWaste = \DB::select($queryPaket);
                    if($paketWaste){
                        for($j=0;$j<sizeof($paketWaste);$j++){
                            $idDaily = $paketWaste[$j]->idDailyWaste;
                            $detaildailyItem = new detaildailywastes();
                            $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
                                                        ->get();
                            $detailPaket = array();
                            $arrFoto = array();
                            for($k=0;$k<sizeof($datadetail);$k++){
                                $idMasterBrg = $datadetail[$k]['idMasterBarang'];
                                $qty         = $datadetail[$k]['qty'];
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
                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$paketWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan=0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem->usernamePembeli;
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $elementBaru = array("idDailyWaste"=>$paketWaste[$j]->idDailyWaste, "isPaket"=>1, "idBarang"=>$paketWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$paketWaste[$j]->hargaAsli,"hargaWaste"=>$paketWaste[$j]->hargaWaste,
                                        "namaBarang"=>$paketWaste[$j]->namaPaket,"detailBarang"=>$detailPaket,"foto"=>$arrFoto,
                                        "stok"=>$paketWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,"kategori"=>"","deskripsi"=>"");
                            array_push($arrItem,$elementBaru);
                        }
                    }
                    if(sizeof($arrItem)>$limitShow){
                        $pengurangan = sizeof($arrItem)-$limitShow;
                        $res['kurang']=$pengurangan;
                        $ctrPengurangan = 0;
                        while($ctrPengurangan<$pengurangan){
                            array_splice($arrItem,sizeof($arrItem)-1,1);
                            $ctrPengurangan++;
                        }
                    }
                    $horders = new horders();
                    $headOrder = $horders->where("usernamePenjual","=",$username)->get();
                    $ratingPenjual = 0.0;
                    $counter=0;
                    $komentar = array();
                    if($headOrder!=null){
                        for($j=0;$j<sizeof($headOrder);$j++){
                            if($headOrder[$j]->rating!=0.0){
                                $ratingPenjual+=$headOrder[$j]->rating;
                                $counter++;
                                $pembeli = $headOrder[$j]->usernamePembeli;
                                $komen   = $headOrder[$j]->comment;
                                $star    = $headOrder[$j]->rating;
                                $addKomen = array("pembeli"=>$pembeli,"komentar"=>$komen,"star"=>$star);
                                array_push($komentar,$addKomen);
                            }
                        }
                        if($counter!=0){
                            $totalRating = floatval($ratingPenjual/$counter);
                        }
                        else{
                            $totalRating = 0.0;
                        }
                    }
                    else{
                        $totalRating = 0.0;
                        $komentar=[];
                    }
                    //ini nanti di bwh paket
                    $elementLuar = array("username"=>$username,"jarak"=>$jarak,"logo"=>$dataActors['logo'],"menu"=>$arrItem,"totalRating"=>$totalRating,"komentar"=>$komentar);
                    array_push($arr,$elementLuar);
                    usort($arr, function ($a, $b) {
                        return $a["jarak"] - $b["jarak"];
                    });
                }
            }
        }
        else if($kodeList==2){ //beli lagi
            $query = "select g.username from ( 
                select d.username 
                from dailywastes d 
                join masterbarangs m 
                on d.idMasterBarang=m.idBarang 
                join horders h 
				on h.usernamePenjual = d.username
                where date(tanggal)= DATE(NOW()) and isPaket=0 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
                and h.usernamePembeli='".$loginUser."'
                
                union 
                
                select d.username 
                from dailywastes d 
                join detaildailywastes det 
                on d.idDailyWaste=det.idDailyWaste
                join masterbarangs m 
                on det.idMasterBarang=m.idBarang 
                join horders h 
				on h.usernamePenjual = d.username
                where date(tanggal)= DATE(NOW()) 
                and isPaket=1 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
                and h.usernamePembeli='".$loginUser."'
            ) g 
            group by g.username";
            $getData = \DB::select($query);

            $arr = array(); 
            if($getData){
                for($i=0;$i<sizeof($getData);$i++){
                    $username = $getData[$i]->username;
                    $act      = new actors();
                    $dataActors = $act->find($username);
                    //ambil data item;
                    $queryItem = "select DISTINCT d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join masterbarangs m 
                    on d.idMasterBarang=m.idBarang 
                    join horders h
                    on h.usernamePenjual=d.username
                    join dorders ds
                    on ds.idHOrder=h.idHOrder
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=0 
                    and m.namaBarang like '%".$filter."%'
                    and d.username='".$username."' and d.realStok>0
                    and ds.idDailyWaste=d.idDailyWaste";
                    $itemWaste = \DB::select($queryItem);
                    $arrItem=array();
                    if($itemWaste){
                        for($j=0;$j<sizeof($itemWaste);$j++){
                            $arrFoto = array();
                            $foto     = new fotos();
                            $addressFoto = "default.jpg";
                            $datafoto = $foto->where("idBarang","=",$itemWaste[$j]->idMasterBarang)->get();
                            if(sizeof($datafoto)>0){
                                $addressFoto = $datafoto[0]['foto'];
                            }
                            array_push($arrFoto,$addressFoto);

                            $master         = new masterbarangs();
                            $dataMaster     = $master->find($itemWaste[$j]->idMasterBarang);
                            $kat            = $dataMaster->idKategori;
                            $deskripsiBrg   = $dataMaster->deskripsiBarang;

                            $katBrg         = new kategoris();
                            $namaKat        = $katBrg->find($kat)->namaKategori;

                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$itemWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan= 0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem['usernamePembeli'];
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $elementBaru = array("idDailyWaste"=>$itemWaste[$j]->idDailyWaste, "isPaket"=>0, "idBarang"=>$itemWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$itemWaste[$j]->hargaAsli,"hargaWaste"=>$itemWaste[$j]->hargaWaste,
                                        "namaBarang"=>$itemWaste[$j]->namaPaket,"detailBarang"=>[],"foto"=>$arrFoto,
                                        "stok"=>$itemWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                        "kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg    
                                        );
                            array_push($arrItem,$elementBaru);
                        }
                    }

                    //ambil data paket
                    $queryPaket="select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join detaildailywastes det 
                    on d.idDailyWaste=det.idDailyWaste
                    join masterbarangs m 
                    on det.idMasterBarang=m.idBarang 
                    join horders h
                    on h.usernamePenjual=d.username
                    join dorders ds
                    on ds.idHOrder=h.idHOrder
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=1 
                    and m.namaBarang like '%".$filter."%' 
                    and d.realStok>0
                    and d.username = '".$username."'
                    and ds.idDailyWaste=d.idDailyWaste
                    group by d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok";
                    $paketWaste = \DB::select($queryPaket);
                    if($paketWaste){
                        for($j=0;$j<sizeof($paketWaste);$j++){
                            $idDaily = $paketWaste[$j]->idDailyWaste;
                            $detaildailyItem = new detaildailywastes();
                            $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
                                                        ->get();
                            $detailPaket = array();
                            $arrFoto = array();
                            for($k=0;$k<sizeof($datadetail);$k++){
                                $idMasterBrg = $datadetail[$k]['idMasterBarang'];
                                $qty         = $datadetail[$k]['qty'];
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
                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$paketWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan=0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem->usernamePembeli;
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $elementBaru = array("idDailyWaste"=>$paketWaste[$j]->idDailyWaste, "isPaket"=>1, "idBarang"=>$paketWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$paketWaste[$j]->hargaAsli,"hargaWaste"=>$paketWaste[$j]->hargaWaste,
                                        "namaBarang"=>$paketWaste[$j]->namaPaket,"detailBarang"=>$detailPaket,"foto"=>$arrFoto,
                                        "stok"=>$paketWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                        "kategori"=>"","deskripsi"=>""
                                        );
                            array_push($arrItem,$elementBaru);
                        }
                    }
                    if(sizeof($arrItem)>$limitShow){
                        $pengurangan = sizeof($arrItem)-$limitShow;
                        $res['kurang']=$pengurangan;
                        $ctrPengurangan = 0;
                        while($ctrPengurangan<$pengurangan){
                            array_splice($arrItem,sizeof($arrItem)-1,1);
                            $ctrPengurangan++;
                        }
                    }
                    $horders = new horders();
                    $headOrder = $horders->where("usernamePenjual","=",$username)->get();
                    $ratingPenjual = 0.0;
                    $counter=0;
                    $komentar = array();
                    if($headOrder!=null){
                        for($j=0;$j<sizeof($headOrder);$j++){
                            if($headOrder[$j]->rating!=0.0){
                                $ratingPenjual+=$headOrder[$j]->rating;
                                $counter++;
                                $pembeli = $headOrder[$j]->usernamePembeli;
                                $komen   = $headOrder[$j]->comment;
                                $star    = $headOrder[$j]->rating;
                                $addKomen = array("pembeli"=>$pembeli,"komentar"=>$komen,"star"=>$star);
                                array_push($komentar,$addKomen);
                            }
                        }
                        if($counter!=0){
                            $totalRating = floatval($ratingPenjual/$counter);
                        }
                        else{
                            $totalRating = 0.0;
                        }
                    }
                    else{
                        $totalRating = 0.0;
                        $komentar=[];
                    }
                    //ini nanti di bwh paket
                    $elementLuar = array("username"=>$username,"logo"=>$dataActors['logo'],"menu"=>$arrItem,"totalRating"=>$totalRating,"komentar"=>$komentar);

                    array_push($arr,$elementLuar);
                }
            }
        }
        else if($kodeList==3){ //best seller
            $query = "select g.username from ( 
                select d.username 
                from dailywastes d 
                join masterbarangs m 
                on d.idMasterBarang=m.idBarang where date(tanggal)= DATE(NOW()) and isPaket=0 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
                
                union 
                
                select d.username 
                from dailywastes d 
                join detaildailywastes det 
                on d.idDailyWaste=det.idDailyWaste
                join masterbarangs m 
                on det.idMasterBarang=m.idBarang 
                where date(tanggal)= DATE(NOW()) 
                and isPaket=1 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
            ) g 
            group by g.username";
            $getData = \DB::select($query);

            $arr = array(); 
            if($getData){
                for($i=0;$i<sizeof($getData);$i++){
                    $username = $getData[$i]->username;
                    $act      = new actors();
                    $dataActors = $act->find($username);
                    //ambil data item;
                    $queryItem = "select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join masterbarangs m 
                    on d.idMasterBarang=m.idBarang 
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=0 
                    and m.namaBarang like '%".$filter."%'
                    and d.username='".$username."' and d.realStok>0";
                    $itemWaste = \DB::select($queryItem);
                    $arrItem=array();
                    if($itemWaste){
                        for($j=0;$j<sizeof($itemWaste);$j++){
                            $arrFoto = array();
                            $foto     = new fotos();
                            $addressFoto = "default.jpg";
                            $datafoto = $foto->where("idBarang","=",$itemWaste[$j]->idMasterBarang)->get();
                            if(sizeof($datafoto)>0){
                                $addressFoto = $datafoto[0]['foto'];
                            }
                            array_push($arrFoto,$addressFoto);

                            $master         = new masterbarangs();
                            $dataMaster     = $master->find($itemWaste[$j]->idMasterBarang);
                            $kat            = $dataMaster->idKategori;
                            $deskripsiBrg   = $dataMaster->deskripsiBarang;

                            $katBrg         = new kategoris();
                            $namaKat        = $katBrg->find($kat)->namaKategori;

                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$itemWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan= 0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem['usernamePembeli'];
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $elementBaru = array("idDailyWaste"=>$itemWaste[$j]->idDailyWaste, "isPaket"=>0, "idBarang"=>$itemWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$itemWaste[$j]->hargaAsli,"hargaWaste"=>$itemWaste[$j]->hargaWaste,
                                        "namaBarang"=>$itemWaste[$j]->namaPaket,"detailBarang"=>[],"foto"=>$arrFoto,
                                        "stok"=>$itemWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,"kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg);
                            array_push($arrItem,$elementBaru);
                        }
                    }

                    //ambil data paket
                    $queryPaket="select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join detaildailywastes det 
                    on d.idDailyWaste=det.idDailyWaste
                    join masterbarangs m 
                    on det.idMasterBarang=m.idBarang 
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=1 
                    and m.namaBarang like '%".$filter."%' 
                    and d.realStok>0
                    and d.username = '".$username."'
                    group by d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok";
                    $paketWaste = \DB::select($queryPaket);
                    if($paketWaste){
                        for($j=0;$j<sizeof($paketWaste);$j++){
                            $idDaily = $paketWaste[$j]->idDailyWaste;
                            $detaildailyItem = new detaildailywastes();
                            $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
                                                        ->get();
                            $detailPaket = array();
                            $arrFoto = array();
                            for($k=0;$k<sizeof($datadetail);$k++){
                                $idMasterBrg = $datadetail[$k]['idMasterBarang'];
                                $qty         = $datadetail[$k]['qty'];
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
                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$paketWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan=0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem->usernamePembeli;
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $elementBaru = array("idDailyWaste"=>$paketWaste[$j]->idDailyWaste, "isPaket"=>1, "idBarang"=>$paketWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$paketWaste[$j]->hargaAsli,"hargaWaste"=>$paketWaste[$j]->hargaWaste,
                                        "namaBarang"=>$paketWaste[$j]->namaPaket,"detailBarang"=>$detailPaket,"foto"=>$arrFoto,
                                        "stok"=>$paketWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,"kategori"=>"","deskripsi"=>"");
                            array_push($arrItem,$elementBaru);
                        }
                    }
                    if(sizeof($arrItem)>$limitShow){
                        $pengurangan = sizeof($arrItem)-$limitShow;
                        $res['kurang']=$pengurangan;
                        $ctrPengurangan = 0;
                        while($ctrPengurangan<$pengurangan){
                            array_splice($arrItem,sizeof($arrItem)-1,1);
                            $ctrPengurangan++;
                        }
                    }
                    
                    $horders = new horders();
                    $headOrder = $horders->where("usernamePenjual","=",$username)->get();
                    $ratingPenjual = 0.0;
                    $counter=0;
                    $komentar = array();
                    if($headOrder!=null){
                        for($j=0;$j<sizeof($headOrder);$j++){
                            if($headOrder[$j]->rating!=0.0){
                                $ratingPenjual+=$headOrder[$j]->rating;
                                $counter++;
                                $pembeli = $headOrder[$j]->usernamePembeli;
                                $komen   = $headOrder[$j]->comment;
                                $star    = $headOrder[$j]->rating;
                                $addKomen = array("pembeli"=>$pembeli,"komentar"=>$komen,"star"=>$star);
                                array_push($komentar,$addKomen);
                            }
                        }
                        if($counter!=0){
                            $totalRating = floatval($ratingPenjual/$counter);
                        }
                        else{
                            $totalRating = 0.0;
                        }
                    }
                    else{
                        $totalRating = 0.0;
                        $komentar=[];
                    }
                    //ini nanti di bwh paket
                    $elementLuar = array("username"=>$username,"logo"=>$dataActors['logo'],"menu"=>$arrItem,"totalRating"=>$totalRating,"komentar"=>$komentar);
                    array_push($arr,$elementLuar);
                    usort($arr, function ($a, $b) {
                        return intval($b["totalRating"]) - intval($a["totalRating"]);
                    });
                }
            }
        }
        else if($kodeList==4){ //paling murah
            $query = "select g.username from ( 
                select d.username 
                from dailywastes d 
                join masterbarangs m 
                on d.idMasterBarang=m.idBarang where date(tanggal)= DATE(NOW()) and isPaket=0 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
                
                union 
                
                select d.username 
                from dailywastes d 
                join detaildailywastes det 
                on d.idDailyWaste=det.idDailyWaste
                join masterbarangs m 
                on det.idMasterBarang=m.idBarang 
                where date(tanggal)= DATE(NOW()) 
                and isPaket=1 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
            ) g 
            group by g.username";
            $getData = \DB::select($query);

            $arr = array(); 
            if($getData){
                for($i=0;$i<sizeof($getData);$i++){
                    $username = $getData[$i]->username;
                    $act      = new actors();
                    $dataActors = $act->find($username);
                    //ambil data item;
                    $queryItem = "select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join masterbarangs m 
                    on d.idMasterBarang=m.idBarang 
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=0 
                    and m.namaBarang like '%".$filter."%'
                    and d.username='".$username."' and d.realStok>0";
                    $itemWaste = \DB::select($queryItem);
                    $arrItem=array();
                    if($itemWaste){
                        for($j=0;$j<sizeof($itemWaste);$j++){
                            $arrFoto = array();
                            $foto     = new fotos();
                            $addressFoto = "default.jpg";
                            $datafoto = $foto->where("idBarang","=",$itemWaste[$j]->idMasterBarang)->get();
                            if(sizeof($datafoto)>0){
                                $addressFoto = $datafoto[0]['foto'];
                            }
                            array_push($arrFoto,$addressFoto);

                            $master         = new masterbarangs();
                            $dataMaster     = $master->find($itemWaste[$j]->idMasterBarang);
                            $kat            = $dataMaster->idKategori;
                            $deskripsiBrg   = $dataMaster->deskripsiBarang;

                            $katBrg         = new kategoris();
                            $namaKat        = $katBrg->find($kat)->namaKategori;

                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$itemWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan= 0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem['usernamePembeli'];
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $elementBaru = array("idDailyWaste"=>$itemWaste[$j]->idDailyWaste, "isPaket"=>0, "idBarang"=>$itemWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$itemWaste[$j]->hargaAsli,"hargaWaste"=>$itemWaste[$j]->hargaWaste,
                                        "namaBarang"=>$itemWaste[$j]->namaPaket,"detailBarang"=>[],"foto"=>$arrFoto,
                                        "stok"=>$itemWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                        "kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg
                                        );
                            array_push($arrItem,$elementBaru);
                        }
                    }

                    //ambil data paket
                    $queryPaket="select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join detaildailywastes det 
                    on d.idDailyWaste=det.idDailyWaste
                    join masterbarangs m 
                    on det.idMasterBarang=m.idBarang 
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=1 
                    and m.namaBarang like '%".$filter."%' 
                    and d.realStok>0
                    and d.username = '".$username."'
                    group by d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok";
                    $paketWaste = \DB::select($queryPaket);
                    if($paketWaste){
                        for($j=0;$j<sizeof($paketWaste);$j++){
                            $idDaily = $paketWaste[$j]->idDailyWaste;
                            $detaildailyItem = new detaildailywastes();
                            $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
                                                        ->get();
                            $detailPaket = array();
                            $arrFoto = array();
                            for($k=0;$k<sizeof($datadetail);$k++){
                                $idMasterBrg = $datadetail[$k]['idMasterBarang'];
                                $qty         = $datadetail[$k]['qty'];
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
                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$paketWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan=0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem->usernamePembeli;
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $elementBaru = array("idDailyWaste"=>$paketWaste[$j]->idDailyWaste, "isPaket"=>1, "idBarang"=>$paketWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$paketWaste[$j]->hargaAsli,"hargaWaste"=>$paketWaste[$j]->hargaWaste,
                                        "namaBarang"=>$paketWaste[$j]->namaPaket,"detailBarang"=>$detailPaket,"foto"=>$arrFoto,
                                        "stok"=>$paketWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                        "kategori"=>"","deskripsi"=>""
                                        );
                            array_push($arrItem,$elementBaru);
                        }
                    }
                    if(sizeof($arrItem)>$limitShow){
                        $pengurangan = sizeof($arrItem)-$limitShow;
                        $res['kurang']=$pengurangan;
                        $ctrPengurangan = 0;
                        while($ctrPengurangan<$pengurangan){
                            array_splice($arrItem,sizeof($arrItem)-1,1);
                            $ctrPengurangan++;
                        }
                    }
                    $horders = new horders();
                    $headOrder = $horders->where("usernamePenjual","=",$username)->get();
                    $ratingPenjual = 0.0;
                    $counter=0;
                    $komentar = array();
                    if($headOrder!=null){
                        for($j=0;$j<sizeof($headOrder);$j++){
                            if($headOrder[$j]->rating!=0.0){
                                $ratingPenjual+=$headOrder[$j]->rating;
                                $counter++;
                                $pembeli = $headOrder[$j]->usernamePembeli;
                                $komen   = $headOrder[$j]->comment;
                                $star    = $headOrder[$j]->rating;
                                $addKomen = array("pembeli"=>$pembeli,"komentar"=>$komen,"star"=>$star);
                                array_push($komentar,$addKomen);
                            }
                        }
                        if($counter!=0){
                            $totalRating = floatval($ratingPenjual/$counter);
                        }
                        else{
                            $totalRating = 0.0;
                        }
                    }
                    else{
                        $totalRating = 0.0;
                        $komentar=[];
                    }
                    //ini nanti di bwh paket
                    usort($arrItem, function ($a, $b) {
                        return $a["hargaWaste"] - $b["hargaWaste"];
                    });
                    $elementLuar = array("username"=>$username,"logo"=>$dataActors['logo'],"menu"=>$arrItem,"totalRating"=>$totalRating,"komentar"=>$komentar);

                    array_push($arr,$elementLuar);
                }
            }
        }
        else if($kodeList==5){ //paling laku
            $query = "select g.username from ( 
                select d.username 
                from dailywastes d 
                join masterbarangs m 
                on d.idMasterBarang=m.idBarang 
                join horders h
    			on h.usernamePenjual=d.username
                where date(tanggal)= DATE(NOW()) and isPaket=0 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
                
                union 
                
                select d.username 
                from dailywastes d 
                join detaildailywastes det 
                on d.idDailyWaste=det.idDailyWaste
                join masterbarangs m 
                on det.idMasterBarang=m.idBarang
                join horders h
    			on h.usernamePenjual=d.username 
                where date(tanggal)= DATE(NOW()) 
                and isPaket=1 
                and (d.username like '%".$filter."%' or m.namaBarang like '%".$filter."%') 
                and d.realStok>0
            ) g 
            group by g.username";
            $getData = \DB::select($query);

            $arr = array(); 
            if($getData){
                for($i=0;$i<sizeof($getData);$i++){
                    $username = $getData[$i]->username;
                    $act      = new actors();
                    $dataActors = $act->find($username);
                    //ambil data item;
                    $queryItem = "
                        select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                        from dailywastes d 
                        join masterbarangs m 
                        on d.idMasterBarang=m.idBarang 
                        join horders h
                        on h.usernamePenjual = d.username
                        join dorders ds
                        on ds.idHOrder=h.idHOrder
                        where date(tanggal)= DATE(NOW()) 
                        and isPaket=0 
                        and m.namaBarang like '%".$filter."%'
                        and d.username='".$username."' and d.realStok>0
                        group by d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok";
                    $itemWaste = \DB::select($queryItem);
                    $arrItem=array();
                    if($itemWaste){
                        for($j=0;$j<sizeof($itemWaste);$j++){
                            $arrFoto = array();
                            $foto     = new fotos();
                            $addressFoto = "default.jpg";
                            $datafoto = $foto->where("idBarang","=",$itemWaste[$j]->idMasterBarang)->get();
                            if(sizeof($datafoto)>0){
                                $addressFoto = $datafoto[0]['foto'];
                            }
                            array_push($arrFoto,$addressFoto);

                            $master         = new masterbarangs();
                            $dataMaster     = $master->find($itemWaste[$j]->idMasterBarang);
                            $kat            = $dataMaster->idKategori;
                            $deskripsiBrg   = $dataMaster->deskripsiBarang;

                            $katBrg         = new kategoris();
                            $namaKat        = $katBrg->find($kat)->namaKategori;

                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$itemWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan= 0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem['usernamePembeli'];
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $idDailyWasteuJumlah = $itemWaste[$j]->idDailyWaste;
                            $qJumlah = "select sum(d.qty) as jum
                                            from dorders d
                                            where idDailyWaste=".$idDailyWasteuJumlah;
                            $Qjum = \DB::select($qJumlah);
                            $jumlah = $Qjum[0]->jum;
                            $elementBaru = array("idDailyWaste"=>$itemWaste[$j]->idDailyWaste, "isPaket"=>0, "idBarang"=>$itemWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$itemWaste[$j]->hargaAsli,"hargaWaste"=>$itemWaste[$j]->hargaWaste,
                                        "namaBarang"=>$itemWaste[$j]->namaPaket,"detailBarang"=>[],"foto"=>$arrFoto,
                                        "stok"=>$itemWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,"jumlah"=>$jumlah,
                                        "kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg
                                        );
                            array_push($arrItem,$elementBaru);
                        }
                    }

                    //ambil data paket
                    $queryPaket="select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
                    from dailywastes d 
                    join detaildailywastes det 
                    on d.idDailyWaste=det.idDailyWaste
                    join masterbarangs m 
                    on det.idMasterBarang=m.idBarang 
                    join horders h
                    on h.usernamePenjual = d.username
                    join dorders ds
                    on ds.idHOrder=h.idHOrder
                    where date(tanggal)= DATE(NOW()) 
                    and isPaket=1 
                    and m.namaBarang like '%".$filter."%' 
                    and d.realStok>0
                    and d.username = '".$username."'
                    group by d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok";
                    $paketWaste = \DB::select($queryPaket);
                    if($paketWaste){
                        for($j=0;$j<sizeof($paketWaste);$j++){
                            $idDaily = $paketWaste[$j]->idDailyWaste;
                            $detaildailyItem = new detaildailywastes();
                            $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
                                                        ->get();
                            $detailPaket = array();
                            $arrFoto = array();
                            for($k=0;$k<sizeof($datadetail);$k++){
                                $idMasterBrg = $datadetail[$k]['idMasterBarang'];
                                $qty         = $datadetail[$k]['qty'];
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
                            $dorder = new dorders();
                            $detOrders = $dorder->where("idDailyWaste","=",$paketWaste[$j]->idDailyWaste)->get();
                            $ratingMakanan = 0.0;
                            $counterMakanan=0;
                            $komentarMakanan = array();
                            if($detOrders!=null){
                                for($k=0;$k<sizeof($detOrders);$k++){
                                    if($detOrders[$k]->rating!=0.0){
                                        $ratingMakanan+=$detOrders[$k]->rating;
                                        $counterMakanan++;
                                        $h = new horders();
                                        $pem = $h->find($detOrders[$k]->idHOrder);
                                        $pembeli = $pem->usernamePembeli;
                                        $komens  = $detOrders[$k]->comment;
                                        $stars   = $detOrders[$k]->rating;
                                        $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                        array_push($komentarMakanan,$addKomens);
                                    }
                                }
                                if($counterMakanan!=0){
                                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                                }
                                else{
                                    $totalRatingMakanan = 0.0;
                                }
                            }
                            else{
                                $totalRatingMakanan = 0.0;
                                $komentarMakanan=[];
                            }
                            $idDailyWasteuJumlah = $paketWaste[$j]->idDailyWaste;
                            $qJumlah = "select sum(d.qty) as jum
                                            from dorders d
                                            where idDailyWaste=".$idDailyWasteuJumlah;
                            $Qjum = \DB::select($qJumlah);
                            $jumlah = $Qjum[0]->jum;
                            $elementBaru = array("idDailyWaste"=>$paketWaste[$j]->idDailyWaste, "isPaket"=>1, "idBarang"=>$paketWaste[$j]->idMasterBarang,
                                        "hargaAsli"=>$paketWaste[$j]->hargaAsli,"hargaWaste"=>$paketWaste[$j]->hargaWaste,
                                        "namaBarang"=>$paketWaste[$j]->namaPaket,"detailBarang"=>$detailPaket,"foto"=>$arrFoto,
                                        "stok"=>$paketWaste[$j]->realStok,"totalRatingMakanan"=>$totalRatingMakanan,
                                        "komentarMakanan"=>$komentarMakanan,"jumlah"=>$jumlah,"kategori"=>"","deskripsi"=>"");
                            array_push($arrItem,$elementBaru);
                        }
                    }
                    if(sizeof($arrItem)>$limitShow){
                        $pengurangan = sizeof($arrItem)-$limitShow;
                        $res['kurang']=$pengurangan;
                        $ctrPengurangan = 0;
                        while($ctrPengurangan<$pengurangan){
                            array_splice($arrItem,sizeof($arrItem)-1,1);
                            $ctrPengurangan++;
                        }
                    }
                    $horders = new horders();
                    $headOrder = $horders->where("usernamePenjual","=",$username)->get();
                    $ratingPenjual = 0.0;
                    $counter=0;
                    $komentar = array();
                    if($headOrder!=null){
                        for($j=0;$j<sizeof($headOrder);$j++){
                            if($headOrder[$j]->rating!=0.0){
                                $ratingPenjual+=$headOrder[$j]->rating;
                                $counter++;
                                $pembeli = $headOrder[$j]->usernamePembeli;
                                $komen   = $headOrder[$j]->comment;
                                $star    = $headOrder[$j]->rating;
                                $addKomen = array("pembeli"=>$pembeli,"komentar"=>$komen,"star"=>$star);
                                array_push($komentar,$addKomen);
                            }
                        }
                        if($counter!=0){
                            $totalRating = floatval($ratingPenjual/$counter);
                        }
                        else{
                            $totalRating = 0.0;
                        }
                    }
                    else{
                        $totalRating = 0.0;
                        $komentar=[];
                    }
                    usort($arrItem, function ($a, $b) {
                        return $b["jumlah"] - $a["jumlah"];
                    });
                    //ini nanti di bwh paket
                    $elementLuar = array("username"=>$username,"logo"=>$dataActors['logo'],"menu"=>$arrItem,"totalRating"=>$totalRating,"komentar"=>$komentar);
                    array_push($arr,$elementLuar);
                }
            }
        }
        $res['data']=$arr;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getAllWastebyUsername(Request $req)
    {
        $username = $req->username;
        $arr = array(); 
        $act      = new actors();
        $dataActors = $act->find($username);
        //ambil data item;
        $queryItem = "select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
        from dailywastes d 
        join masterbarangs m 
        on d.idMasterBarang=m.idBarang 
        where date(tanggal)= DATE(NOW()) 
        and isPaket=0 
        and d.username='".$username."' and d.realStok>0";
        $itemWaste = \DB::select($queryItem);
        $arrItem=array();
        if($itemWaste){
            for($j=0;$j<sizeof($itemWaste);$j++){
                $arrFoto = array();
                $foto     = new fotos();
                $addressFoto = "default.jpg";
                $datafoto = $foto->where("idBarang","=",$itemWaste[$j]->idMasterBarang)->get();
                if(sizeof($datafoto)>0){
                    $addressFoto = $datafoto[0]['foto'];
                }
                array_push($arrFoto,$addressFoto);

                $master         = new masterbarangs();
                $dataMaster     = $master->find($itemWaste[$j]->idMasterBarang);
                $kat            = $dataMaster->idKategori;
                $deskripsiBrg   = $dataMaster->deskripsiBarang;

                $katBrg         = new kategoris();
                $namaKat        = $katBrg->find($kat)->namaKategori;

                $dorder = new dorders();
                $detOrders = $dorder->where("idDailyWaste","=",$itemWaste[$j]->idDailyWaste)->get();
                $ratingMakanan = 0.0;
                $counterMakanan=0;
                $komentarMakanan = array();
                if($detOrders!=null){
                    for($k=0;$k<sizeof($detOrders);$k++){
                        if($detOrders[$k]->rating!=0.0){
                            $ratingMakanan+=$detOrders[$k]->rating;
                            $counterMakanan++;
                            $h = new horders();
                            $pem = $h->find($detOrders[$k]->idHOrder);
                            $pembeli = $pem['usernamePembeli'];
                            $komens  = $detOrders[$k]->comment;
                            $stars   = $detOrders[$k]->rating;
                            $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                            array_push($komentarMakanan,$addKomens);
                        }
                    }
                    if($counterMakanan!=0){
                        $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                    }
                    else{
                        $totalRatingMakanan = 0.0;
                    }
                }
                else{
                    $totalRatingMakanan = 0.0;
                    $komentarMakanan=[];
                }
                $elementBaru = array("idDailyWaste"=>$itemWaste[$j]->idDailyWaste, "isPaket"=>0, "idBarang"=>$itemWaste[$j]->idMasterBarang,
                            "hargaAsli"=>$itemWaste[$j]->hargaAsli,"hargaWaste"=>$itemWaste[$j]->hargaWaste,
                            "namaBarang"=>$itemWaste[$j]->namaPaket,"detailBarang"=>[],"foto"=>$arrFoto,"stok"=>$itemWaste[$j]->realStok,
                            "totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,"kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg);
                array_push($arrItem,$elementBaru);
            }
        }

        //ambil data paket
        $queryPaket="select d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok
        from dailywastes d 
        join detaildailywastes det 
        on d.idDailyWaste=det.idDailyWaste
        join masterbarangs m 
        on det.idMasterBarang=m.idBarang 
        where date(tanggal)= DATE(NOW()) 
        and isPaket=1 
        and d.realStok>0
        and d.username='".$username."'
        group by d.idDailyWaste,d.isPaket,d.idMasterBarang,d.username,d.hargaAsli,d.hargaWaste,d.namaPaket,d.realStok";
        $paketWaste = \DB::select($queryPaket);
        if($paketWaste){
            for($j=0;$j<sizeof($paketWaste);$j++){
                $idDaily = $paketWaste[$j]->idDailyWaste;
                $detaildailyItem = new detaildailywastes();
                $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
                                            ->get();
                $detailPaket = array();
                $arrFoto = array();
                for($k=0;$k<sizeof($datadetail);$k++){
                    $idMasterBrg = $datadetail[$k]['idMasterBarang'];
                    $qty         = $datadetail[$k]['qty'];
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
                $dorder = new dorders();
                $detOrders = $dorder->where("idDailyWaste","=",$paketWaste[$j]->idDailyWaste)->get();
                $ratingMakanan = 0.0;
                $counterMakanan=0;
                $komentarMakanan = array();
                if($detOrders!=null){
                    for($k=0;$k<sizeof($detOrders);$k++){
                        if($detOrders[$k]->rating!=0.0){
                            $ratingMakanan+=$detOrders[$k]->rating;
                            $counterMakanan++;
                            $h = new horders();
                            $pem = $h->find($detOrders[$k]->idHOrder);
                            $pembeli = $pem->usernamePembeli;
                            $komens  = $detOrders[$k]->comment;
                            $stars   = $detOrders[$k]->rating;
                            $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                            array_push($komentarMakanan,$addKomens);
                        }
                    }
                    if($counterMakanan!=0){
                        $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                    }
                    else{
                        $totalRatingMakanan = 0.0;
                    }
                }
                else{
                    $totalRatingMakanan = 0.0;
                    $komentarMakanan=[];
                }
                $elementBaru = array("idDailyWaste"=>$paketWaste[$j]->idDailyWaste, "isPaket"=>1, "idBarang"=>$paketWaste[$j]->idMasterBarang,
                            "hargaAsli"=>$paketWaste[$j]->hargaAsli,"hargaWaste"=>$paketWaste[$j]->hargaWaste,
                            "namaBarang"=>$paketWaste[$j]->namaPaket,"detailBarang"=>$detailPaket,"foto"=>$arrFoto,"stok"=>$paketWaste[$j]->realStok,
                            "totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,"kategori"=>"","deskripsi"=>"");
                array_push($arrItem,$elementBaru);
            }
        }
        $horders = new horders();
        $headOrder = $horders->where("usernamePenjual","=",$username)->get();
        $ratingPenjual = 0.0;
        $counter=0;
        $komentar = array();
        if($headOrder!=null){
            for($j=0;$j<sizeof($headOrder);$j++){
                if($headOrder[$j]->rating!=0.0){
                    $ratingPenjual+=$headOrder[$j]->rating;
                    $counter++;
                    $pembeli = $headOrder[$j]->usernamePembeli;
                    $komen   = $headOrder[$j]->comment;
                    $star    = $headOrder[$j]->rating;
                    $addKomen = array("pembeli"=>$pembeli,"komentar"=>$komen,"star"=>$star);
                    array_push($komentar,$addKomen);
                }
            }
            if($counter!=0){
                $totalRating = floatval($ratingPenjual/$counter);
            }
            else{
                $totalRating = 0.0;
            }
        }
        else{
            $totalRating = 0.0;
            $komentar=[];
        }
        //ini nanti di bwh paket
        $elementLuar = array("username"=>$username,"logo"=>$dataActors['logo'],"menu"=>$arrItem,"totalRating"=>$totalRating,"komentar"=>$komentar);
        array_push($arr,$elementLuar);
        $res['data']=$arr;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getAlamatPengiriman(Request $req)
    {
        $username = $req->username;
        //get alamat pribadi
        $almt       = new alamats();
        $dataAlamat = $almt->where('username','=',$username)->get();

        $bantu      = new penerimabantuans();
        $dataBantuan= $bantu->where('username','=',null)
                            ->orWhere('username','=',$username)
                            ->get();;

        $res['dataPribadi']=$dataAlamat;
        $res['dataBantuan']=$dataBantuan;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getTidakLaku(Request $req)
    {
        $username = $req->username;
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $jenis    = $req->jenis;

        $waste = new dailywastes();
        if($jenis==0){
            $data = $waste->where("username","=",$username)
                        ->whereDate('tanggal','>=',$tglAwal)
                        ->whereDate('tanggal','<=',$tglAkhir)
                        ->where("realStok","<>",0)
                        ->where("isPaket","=",0)
                        ->orderBy('tanggal','asc')
                        ->get();
        }
        else{
            $data = $waste->where("username","=",$username)
                        ->whereDate('tanggal','>=',$tglAwal)
                        ->whereDate('tanggal','<=',$tglAkhir)
                        ->where("realStok","<>",0)
                        ->where("isPaket","=",1)
                        ->orderBy('tanggal','asc')
                        ->get();
        }

        $arr = array();
        for($i=0;$i<sizeof($data);$i++){
            $idDaily = $data[$i]['idDailyWaste'];
            $isPaket = $data[$i]['isPaket'];
            $tanggal = $data[$i]['tanggal'];
            $arrFoto = array();
            if($jenis==0){
                $idBarang   = $data[$i]['idMasterBarang'];
                $namaBarang = $data[$i]['namaPaket'];

                $master         = new masterbarangs();
                $dataMaster     = $master->find($idBarang);
                $kat            = $dataMaster->idKategori;
                $deskripsiBrg   = $dataMaster->deskripsiBarang;

                $katBrg         = new kategoris();
                $namaKat        = $katBrg->find($kat)->namaKategori;

                $stok       = $data[$i]['realStok'];
                $oristok       = $data[$i]['stok'];
                $hargaWaste = $data[$i]['hargaWaste'];

                $foto     = new fotos();
                $addressFoto = "default.jpg";
                $datafoto = $foto->where("idBarang","=",$idBarang)->get();
                if(sizeof($datafoto)>0){
                    $addressFoto = $datafoto[0]['foto'];
                }
                array_push($arrFoto,$addressFoto);
                $dorder = new dorders();
                $detOrders = $dorder->where("idDailyWaste","=",$data[$i]['idDailyWaste'])->get();
                $ratingMakanan = 0.0;
                $counterMakanan= 0;
                $komentarMakanan = array();
                if($detOrders!=null){
                    for($k=0;$k<sizeof($detOrders);$k++){
                        if($detOrders[$k]->rating!=0.0){
                            $ratingMakanan+=$detOrders[$k]->rating;
                            $counterMakanan++;
                            $h = new horders();
                            $pem = $h->find($detOrders[$k]->idHOrder);
                            $pembeli = $pem['usernamePembeli'];
                            $komens  = $detOrders[$k]->comment;
                            $stars   = $detOrders[$k]->rating;
                            $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                            array_push($komentarMakanan,$addKomens);
                        }
                    }
                    if($counterMakanan!=0){
                        $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                    }
                    else{
                        $totalRatingMakanan = 0.0;
                    }
                }
                else{
                    $totalRatingMakanan = 0.0;
                    $komentarMakanan=[];
                }
                $elementBaru = array("idDaily"=>$idDaily, "isPaket"=>$isPaket, "idBarang"=>$idBarang,
                                    "namaBarang"=>$namaBarang,"stok"=>$stok,"oristok"=>$oristok,
                                    "hargaWaste"=>$hargaWaste,"foto"=>$arrFoto,"detailPaket"=>[],
                                    "totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                    "kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg,"tanggal"=>$tanggal
                                );
                array_push($arr,$elementBaru);
            }
            else{
                $namaPaket = $data[$i]['namaPaket'];
                $stok       = $data[$i]['realStok'];
                $oristok       = $data[$i]['stok'];
                $hargaWaste = $data[$i]['hargaWaste'];
                $detaildailyItem = new detaildailywastes();
                $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
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
                $dorder = new dorders();
                $detOrders = $dorder->where("idDailyWaste","=",$data[$i]['idDailyWaste'])->get();
                $ratingMakanan = 0.0;
                $counterMakanan= 0;
                $komentarMakanan = array();
                if($detOrders!=null){
                    for($k=0;$k<sizeof($detOrders);$k++){
                        if($detOrders[$k]->rating!=0.0){
                            $ratingMakanan+=$detOrders[$k]->rating;
                            $counterMakanan++;
                            $h = new horders();
                            $pem = $h->find($detOrders[$k]->idHOrder);
                            $pembeli = $pem['usernamePembeli'];
                            $komens  = $detOrders[$k]->comment;
                            $stars   = $detOrders[$k]->rating;
                            $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                            array_push($komentarMakanan,$addKomens);
                        }
                    }
                    if($counterMakanan!=0){
                        $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                    }
                    else{
                        $totalRatingMakanan = 0.0;
                    }
                }
                else{
                    $totalRatingMakanan = 0.0;
                    $komentarMakanan=[];
                }
                $elementBaru = array("idDaily"=>$idDaily, "isPaket"=>$isPaket, "idBarang"=>null,
                                    "namaBarang"=>$namaPaket,"stok"=>$stok,"oristok"=>$oristok,
                                    "hargaWaste"=>$hargaWaste,"foto"=>$arrFoto,"detailPaket"=>$detailPaket,
                                    "totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                    "kategori"=>"","deskripsi"=>"","tanggal"=>$tanggal
                                );
                array_push($arr,$elementBaru);
            }
        }
        $res['data']=$arr;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getRankLaku(Request $req)
    {
        $username = $req->username;
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $jenis    = $req->jenis;

        $waste    = new dailywastes();
        if($jenis==0){
            $dataWaste= $waste->where("username","=",$username)
                                ->whereDate('tanggal','>=',$tglAwal)
                                ->whereDate('tanggal','<=',$tglAkhir)
                                ->orderBy('tanggal','asc')
                                ->get();
        }
        else{
            $dataWaste= $waste->where("username","=",$username)
                            ->where("isPaket","=",1)
                            ->whereDate('tanggal','>=',$tglAwal)
                            ->whereDate('tanggal','<=',$tglAkhir)
                            ->orderBy('tanggal','asc')
                            ->get();
        }
        $data = array();
        for($i=0;$i<sizeof($dataWaste);$i++){
            if($jenis==1){
                $dorders = new dorders();
                $detOrder= $dorders->where("idDailyWaste","=",$dataWaste[$i]->idDailyWaste)->get();
                $jual = 0;
                if($detOrder!=null){
                    for($k=0;$k<sizeof($detOrder);$k++){
                        $jual += $detOrder[$k]->qty;
                    }
                }
                $elementBaru = array("idMasterBarang"=>null,"idDailyWaste"=>$dataWaste[$i]->idDailyWaste,"namaBarang"=>$dataWaste[$i]->namaPaket,"terjual"=>$jual,"isPaket"=>1);
                array_push($data,$elementBaru);
            }
            else{
                $ada=false;
                $posisiArr = -1;
                if($dataWaste[$i]->isPaket==0){
                    for($j=0;$j<sizeof($data);$j++){
                        if($data[$j]['idMasterBarang']==$dataWaste[$i]->idMasterBarang){
                            $ada=true;
                            $posisiArr=$j;
                        }
                    }
                    if($ada==false){
                        $dorders = new dorders();
                        $detOrder= $dorders->where("idDailyWaste","=",$dataWaste[$i]->idDailyWaste)->get();
                        $jual=0;
                        if($detOrder!=null){
                            for($k=0;$k<sizeof($detOrder);$k++){
                                $jual += $detOrder[$k]->qty;
                            }
                        }
                        $idDailyWaste = array();
                        array_push($idDailyWaste,$dataWaste[$i]->idDailyWaste);
                        $elementBaru = array("idMasterBarang"=>$dataWaste[$i]->idMasterBarang,"idDailyWaste"=>$idDailyWaste,"namaBarang"=>$dataWaste[$i]->namaPaket,"terjual"=>$jual,"isPaket"=>0);
                        array_push($data,$elementBaru);
                    }
                    else{
                        $dorders = new dorders();
                        $detOrder= $dorders->where("idDailyWaste","=",$dataWaste[$i]->idDailyWaste)->get();
                        $jual = 0;
                        for($k=0;$k<sizeof($detOrder);$k++){
                            $jual += $detOrder[$k]->qty;
                        }
                        $data[$posisiArr]['terjual']+=$jual;
                    }
                }
                else{
                    //paket dianggap unique
                    $dorders = new dorders();
                    $detOrder= $dorders->where("idDailyWaste","=",$dataWaste[$i]->idDailyWaste)->get();
                    $jual = 0;
                    if($detOrder!=null){
                        for($k=0;$k<sizeof($detOrder);$k++){
                            $jual += $detOrder[$k]->qty;
                        }
                    }
                    $detDWaste = new detaildailywastes();
                    $detDailyWaste = $detDWaste->where("idDailyWaste","=",$dataWaste[$i]->idDailyWaste)->get();
                    for($k=0;$k<sizeof($detDailyWaste);$k++){
                        $adaPaket = false;
                        for($j=0;$j<sizeof($data);$j++){
                            if($data[$j]['idMasterBarang']==$detDailyWaste[$k]->idMasterBarang){
                                $adaPaket=true;
                                $posisiArr=$j;
                            }
                        }
                        if($adaPaket==false){
                            $tambahan = $jual*$detDailyWaste[$k]->qty;
                            $dorders = new dorders();
                            $idDailyWaste = array();
                            array_push($idDailyWaste,$dataWaste[$i]->idDailyWaste);
                            $idBarangg = $detDailyWaste[$k]->idMasterBarang;
                            $barang = new masterbarangs();
                            $dtBrg  = $barang->find($idBarangg);
                            $namaBarangg = $dtBrg->namaBarang;
                            $elementBaru = array("idMasterBarang"=>$detDailyWaste[$k]->idMasterBarang,"idDailyWaste"=>$idDailyWaste,
                            "namaBarang"=>$namaBarangg,"terjual"=>$tambahan,"isPaket"=>0);
                            array_push($data,$elementBaru);
                        }
                        else{
                            $tambahan = $jual*$detDailyWaste[$k]->qty;
                            $data[$posisiArr]['terjual']+=$tambahan;
                        }
                    }
                }
            }
        }
        $arr = array();
        for($i=0;$i<sizeof($data);$i++){
            $isPaket = $data[$i]['isPaket'];
            $arrFoto = array();
            if($isPaket==0){
                //bukanPaket
                $idDaily    = $data[$i]['idDailyWaste'];
                $idBarang   = $data[$i]['idMasterBarang'];
                $namaBarang = $data[$i]['namaBarang'];

                $master         = new masterbarangs();
                $dataMaster     = $master->find($idBarang);
                $kat            = $dataMaster->idKategori;
                $deskripsiBrg   = $dataMaster->deskripsiBarang;

                $katBrg         = new kategoris();
                $namaKat        = $katBrg->find($kat)->namaKategori;

                $terjual       = $data[$i]['terjual'];

                $foto     = new fotos();
                $addressFoto = "default.jpg";
                $datafoto = $foto->where("idBarang","=",$idBarang)->get();
                if(sizeof($datafoto)>0){
                    $addressFoto = $datafoto[0]['foto'];
                }
                array_push($arrFoto,$addressFoto);
                $ratingMakanan = 0.0;
                $counterMakanan= 0;
                $komentarMakanan = [];
                $totalRatingMakanan =0.0;
                for($j=0;$j<sizeof($idDaily);$j++){
                    $dorder = new dorders();
                    $detOrders = $dorder->where("idDailyWaste","=",$idDaily[$j])->get();
                    if($detOrders!=null){
                        for($k=0;$k<sizeof($detOrders);$k++){
                            if($detOrders[$k]->rating!=0.0){
                                $ratingMakanan+=$detOrders[$k]->rating;
                                $counterMakanan++;
                                $h = new horders();
                                $pem = $h->find($detOrders[$k]->idHOrder);
                                $pembeli = $pem['usernamePembeli'];
                                $komens  = $detOrders[$k]->comment;
                                $stars   = $detOrders[$k]->rating;
                                $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                                array_push($komentarMakanan,$addKomens);
                            }
                        }
                    }
                }
                if($counterMakanan!=0){
                    $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                }
                $elementBaru = array("idDaily"=>$idDaily[0], "isPaket"=>$isPaket, "idBarang"=>$idBarang,
                                    "namaBarang"=>$namaBarang,"terjual"=>$terjual,
                                    "foto"=>$arrFoto,"detailPaket"=>[],
                                    "totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                    "kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg
                                );
                array_push($arr,$elementBaru);
            }
            else{
                //paket
                $idDaily = $data[$i]['idDailyWaste'];
                $namaPaket = $data[$i]['namaBarang'];
                $terjual       = $data[$i]['terjual'];
                $detaildailyItem = new detaildailywastes();
                $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
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
                $dorder = new dorders();
                $detOrders = $dorder->where("idDailyWaste","=",$data[$i]['idDailyWaste'])->get();
                $ratingMakanan = 0.0;
                $counterMakanan= 0;
                $komentarMakanan = array();
                if($detOrders!=null){
                    for($k=0;$k<sizeof($detOrders);$k++){
                        if($detOrders[$k]->rating!=0.0){
                            $ratingMakanan+=$detOrders[$k]->rating;
                            $counterMakanan++;
                            $h = new horders();
                            $pem = $h->find($detOrders[$k]->idHOrder);
                            $pembeli = $pem['usernamePembeli'];
                            $komens  = $detOrders[$k]->comment;
                            $stars   = $detOrders[$k]->rating;
                            $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                            array_push($komentarMakanan,$addKomens);
                        }
                    }
                    if($counterMakanan!=0){
                        $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                    }
                    else{
                        $totalRatingMakanan = 0.0;
                    }
                }
                else{
                    $totalRatingMakanan = 0.0;
                    $komentarMakanan=[];
                }
                $elementBaru = array("idDaily"=>$idDaily, "isPaket"=>$isPaket, "idBarang"=>null,
                                    "namaBarang"=>$namaPaket,"terjual"=>$terjual,
                                    "foto"=>$arrFoto,"detailPaket"=>$detailPaket,
                                    "totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                    "kategori"=>"","deskripsi"=>""
                                );
                array_push($arr,$elementBaru);
            }
        }
        usort($arr, function ($a, $b) {
            return $b["terjual"] - $a["terjual"];
        });
        $res['data']=$arr;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getPenjualanTerpasif(Request $req)
    {
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;
        $act = new actors();
        $dataActor = $act->where("tipeActor","=","V")
                         ->where("status","=",1)
                         ->get();
        $arrPenjual = array();
        for($i=0;$i<sizeof($dataActor);$i++){
            $jumlahPosting = 0;
            $daily      = new dailywastes();
            $dailyWaste = $daily->where('username',"=",$dataActor[$i]->username)
                                ->whereDate('tanggal','>=',$tglAwal)
                                ->whereDate('tanggal','<=',$tglAkhir)
                                ->get();
            if($dailyWaste!=null){
                $jumlahPosting = sizeof($dailyWaste);
            }
            $elemenBaru = array("username"=>$dataActor[$i]->username,"logo"=>$dataActor[$i]->logo,"jumlah"=>$jumlahPosting);
            array_push($arrPenjual,$elemenBaru);
        }
        usort($arrPenjual, function ($a, $b) {
            return $b["jumlah"] - $a["jumlah"];
        });
        $res['data']=$arrPenjual;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getAllWasteInPeriode(Request $req)
    {
        $username = $req->username;
        $tglAwal  = $req->tglAwal;
        $tglAkhir = $req->tglAkhir;

        $waste = new dailywastes();
        $data = $waste->where("username","=",$username)
                        ->whereDate('tanggal','>=',$tglAwal)
                        ->whereDate('tanggal','<=',$tglAkhir)
                        ->orderBy('tanggal','asc')
                        ->get();

        $arr = array();
        for($i=0;$i<sizeof($data);$i++){
            $idDaily = $data[$i]['idDailyWaste'];
            $isPaket = $data[$i]['isPaket'];
            $tanggal = $data[$i]['tanggal'];
            $arrFoto = array();
            if($isPaket==0){
                //bukanPaket
                $idBarang   = $data[$i]['idMasterBarang'];
                $namaBarang = $data[$i]['namaPaket'];

                $master         = new masterbarangs();
                $dataMaster     = $master->find($idBarang);
                $kat            = $dataMaster->idKategori;
                $deskripsiBrg   = $dataMaster->deskripsiBarang;

                $katBrg         = new kategoris();
                $namaKat        = $katBrg->find($kat)->namaKategori;

                $stok       = $data[$i]['stok']-$data[$i]['realStok'];
                $hargaWaste = $data[$i]['hargaWaste'];

                $foto     = new fotos();
                $addressFoto = "default.jpg";
                $datafoto = $foto->where("idBarang","=",$idBarang)->get();
                if(sizeof($datafoto)>0){
                    $addressFoto = $datafoto[0]['foto'];
                }
                array_push($arrFoto,$addressFoto);
                $dorder = new dorders();
                $detOrders = $dorder->where("idDailyWaste","=",$data[$i]['idDailyWaste'])->get();
                $ratingMakanan = 0.0;
                $counterMakanan= 0;
                $komentarMakanan = array();
                if($detOrders!=null){
                    for($k=0;$k<sizeof($detOrders);$k++){
                        if($detOrders[$k]->rating!=0.0){
                            $ratingMakanan+=$detOrders[$k]->rating;
                            $counterMakanan++;
                            $h = new horders();
                            $pem = $h->find($detOrders[$k]->idHOrder);
                            $pembeli = $pem['usernamePembeli'];
                            $komens  = $detOrders[$k]->comment;
                            $stars   = $detOrders[$k]->rating;
                            $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                            array_push($komentarMakanan,$addKomens);
                        }
                    }
                    if($counterMakanan!=0){
                        $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                    }
                    else{
                        $totalRatingMakanan = 0.0;
                    }
                }
                else{
                    $totalRatingMakanan = 0.0;
                    $komentarMakanan=[];
                }
                $elementBaru = array("idDaily"=>$idDaily, "isPaket"=>$isPaket, "idBarang"=>$idBarang,
                                    "namaBarang"=>$namaBarang,"stok"=>$stok,
                                    "hargaWaste"=>$hargaWaste,"foto"=>$arrFoto,"detailPaket"=>[],
                                    "totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                    "kategori"=>$namaKat,"deskripsi"=>$deskripsiBrg,"tanggal"=>$tanggal
                                );
                array_push($arr,$elementBaru);
            }
            else{
                //paket
                $namaPaket = $data[$i]['namaPaket'];
                $stok       = $data[$i]['stok']-$data[$i]['realStok'];
                $hargaWaste = $data[$i]['hargaWaste'];
                $detaildailyItem = new detaildailywastes();
                $datadetail = $detaildailyItem->where('idDailyWaste','=',$idDaily)
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
                $dorder = new dorders();
                $detOrders = $dorder->where("idDailyWaste","=",$data[$i]['idDailyWaste'])->get();
                $ratingMakanan = 0.0;
                $counterMakanan= 0;
                $komentarMakanan = array();
                if($detOrders!=null){
                    for($k=0;$k<sizeof($detOrders);$k++){
                        if($detOrders[$k]->rating!=0.0){
                            $ratingMakanan+=$detOrders[$k]->rating;
                            $counterMakanan++;
                            $h = new horders();
                            $pem = $h->find($detOrders[$k]->idHOrder);
                            $pembeli = $pem['usernamePembeli'];
                            $komens  = $detOrders[$k]->comment;
                            $stars   = $detOrders[$k]->rating;
                            $addKomens = array("pembeli"=>$pembeli,"komentar"=>$komens,"star"=>$stars);
                            array_push($komentarMakanan,$addKomens);
                        }
                    }
                    if($counterMakanan!=0){
                        $totalRatingMakanan = floatval($ratingMakanan/$counterMakanan);
                    }
                    else{
                        $totalRatingMakanan = 0.0;
                    }
                }
                else{
                    $totalRatingMakanan = 0.0;
                    $komentarMakanan=[];
                }
                $elementBaru = array("idDaily"=>$idDaily, "isPaket"=>$isPaket, "idBarang"=>null,
                                    "namaBarang"=>$namaPaket,"stok"=>$stok,
                                    "hargaWaste"=>$hargaWaste,"foto"=>$arrFoto,"detailPaket"=>$detailPaket,
                                    "totalRatingMakanan"=>$totalRatingMakanan,"komentarMakanan"=>$komentarMakanan,
                                    "kategori"=>"","deskripsi"=>"","tanggal"=>$tanggal
                                );
                array_push($arr,$elementBaru);
            }
        }
        $res['data']=$arr;
        $res['status']="Sukses";
        return json_encode($res);
    }
    public function adjustStokVendor(Request $req)
    {
        $idDailyWaste = $req->idDailyWaste;
        $qty          = $req->qty;
        $password     = $req->password;

        $status="Sukses";

        $daily = new dailywastes();
        $dailyWaste = $daily->find($idDailyWaste);
        $realStok = $dailyWaste->realStok;

        $act   = new actors();
        $actor = $act->find($dailyWaste->username);
        $passwordSaved = $actor->password;

        if($passwordSaved!=md5($password)){
            $status = "Password yang Anda masukkan salah";
        }
        else{
            if($qty>$realStok){
                $status = "Stok tidak mencukupi";
            }
            else{
                $dataAdjust = new adjuststoks();
                $dataAdjust->idDailyWaste        = $idDailyWaste;
                $dataAdjust->qtyPenjualanOffline = $qty;
                $dataAdjust->realStokSaatItu     = $realStok;
                $dataAdjust->tanggal             = date("Y-m-d");
                $dataAdjust->waktu               = date("H:i:s");
                $save = $dataAdjust->save();

                $realStok -= $qty;
                $dailyWaste->realStok = $realStok;
                $save = $dailyWaste->save();
            }
        }
        $res['status']=$status;
        return json_encode($res);
    }
}