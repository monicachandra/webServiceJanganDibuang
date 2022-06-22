<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\fotos;
use App\dailywastes;
use App\detaildailywastes;
use App\masterbarangs;
use App\actors;
use App\horders;
use App\dorders;
use App\kategoris;
use App\globalsettings;

class FotoController extends Controller
{
    public function addFotoMakanan(Request $req)
    {
        $idBarang         = $req->idBarang;
        $fileName         = $req->m_filename;
        //$exten            = $req->m_image->extension();
        $extens           = explode(".",$fileName);
        $exten            = $extens[1]; 
        $foto             = new fotos();
        $data             = $foto->where('idBarang','=',$idBarang)
                                 ->get();
        $count=0;
        if($data==null){
            $count=1;
        }
        else{
            $count=sizeof($data);
            $count++;
        }
        
        $namaFile = "P_".$idBarang."_".$count.".".$exten;

        $dataGambar = base64_decode($req->m_image);
        file_put_contents("makanan/".$namaFile,$dataGambar);

        $path_foto = "./makanan\\{$namaFile}";
        $output = array();
        //exec("python ./compareImage.py {$path_foto}",$output);
        exec("python ./appendingFeature.py {$path_foto} 2>&1",$output);

        $foto->idBarang   = $idBarang;
        $foto->foto       = $namaFile;
        $save             = $foto->save();
        
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getFotobyIdBarang(Request $req)
    {
        $idBarang = $req->idBarang;
        $foto = new fotos();
        $data = $foto->where('idBarang','=',$idBarang)
                     ->get();
        $res['data']  = $data;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function compareFotoMakanan(Request $req)
    {
        $globalset = new globalsettings();
        $dataSetting = $globalset->find(1);
        $limitShow = $dataSetting->limitShowMakanan;
        $username         = $req->username;
        $fileName         = $req->m_filename;
        $extens           = explode(".",$fileName);
        $exten            = $extens[1]; 
        
        $namaFile = $username.".".$exten;

        $dataGambar = base64_decode($req->m_image);
        file_put_contents("makanan/".$namaFile,$dataGambar);

        $path_foto = "./makanan\\{$namaFile}";
        $output1 = array();
        //exec("python ./compareImage.py {$path_foto}",$output);
        exec("python ./appendingFeature.py {$path_foto} 2>&1",$output1);


        $path_foto = "./makanan\\{$namaFile}";
        //$path_foto = "./makanan/monica.jpg";
        $output = array();
        //exec("python ./compareImage.py {$path_foto}",$output);
        exec("python ./compareImage.py {$path_foto} 2>&1",$output);
        $sizeoutput = sizeof($output);
        
        //output[0] adalah return dari python
        //sekarang dipisah by ,
        $arr = array(); 
        if(strlen($output[$sizeoutput-1])>0){
            $daftarGambar = explode(",", $output[$sizeoutput-1]);
            //ambil path gbr sesuai yg di dbase
            //separate by \

            $arrayPathFoto = array();
            for($i=0;$i<sizeof($daftarGambar);$i++){
                $pathdbase = substr($daftarGambar[$i],10);
                array_push($arrayPathFoto,$pathdbase);
            } 

            $arrayIdBarang = array();
            for($i=0;$i<sizeof($arrayPathFoto);$i++){
                $idBarang = substr($arrayPathFoto[$i],2,2);
                $ada = false;
                for($j=0;$j<sizeof($arrayIdBarang);$j++){
                    if($idBarang==$arrayIdBarang[$j]){
                        $ada=true;
                    }
                }
                if(!$ada){
                    array_push($arrayIdBarang,$idBarang);
                }
            }

            $query = "select g.username from ( 
                select d.username 
                from dailywastes d 
                join masterbarangs m 
                on d.idMasterBarang=m.idBarang where date(tanggal)= DATE(NOW()) and isPaket=0 
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
                and d.realStok>0
            ) g 
            group by g.username";
            $getData = \DB::select($query);

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
                    and d.username='".$username."' and d.realStok>0";
                    $itemWaste = \DB::select($queryItem);
                    $arrItem=array();
                    if($itemWaste){
                        for($j=0;$j<sizeof($itemWaste);$j++){
                            $ambilData = false;
                            for($k=0;$k<sizeof($arrayIdBarang);$k++){
                                if($itemWaste[$j]->idMasterBarang==$arrayIdBarang[$k]){
                                    $ambilData=true;
                                }
                            }
                            if($ambilData){
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
                            $ambilData = false;
                            for($k=0;$k<sizeof($datadetail);$k++){
                                $idMasterBrg = $datadetail[$k]['idMasterBarang'];
                                for($l=0;$l<sizeof($arrayIdBarang);$l++){
                                    if($idMasterBrg==$arrayIdBarang[$l]){
                                        $ambilData=true;
                                    }
                                }
                            }
                            if($ambilData){
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

            for($i=sizeof($arr)-1;$i>=0;$i--){
                $adaIsi=true;
                if(sizeof($arr[$i]['menu'])<=0){
                    $adaIsi=false;
                }
                if(!$adaIsi){
                    array_splice($arr,$i,1);
                }
            }
        }
        $res['data']=$arr;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function compareFotoMakananDaily(Request $req)
    {
        $username         = $req->username;
        $fileName         = $req->m_filename;
        $extens           = explode(".",$fileName);
        $exten            = $extens[1]; 
        
        $namaFile = $username.".".$exten;

        $dataGambar = base64_decode($req->m_image);
        file_put_contents("makanan/".$namaFile,$dataGambar);

        /*$path_foto = "./makanan/{$namaFile}";
        //$path_foto = "./makanan\\monchan.jpg";
        $output = array();
        exec("python ./compareImage.py {$path_foto} 2>&1",$output);
        $sizeoutput = sizeof($output);*/

        $path_foto = "./makanan\\{$namaFile}";
        $output1 = array(); 
        //exec("python ./compareImage.py {$path_foto}",$output);
        exec("python ./appendingFeature.py {$path_foto} 2>&1",$output1);


        $path_foto = "./makanan\\{$namaFile}";
        //$path_foto = "./makanan/monica.jpg";
        $output = array();
        //exec("python ./compareImage.py {$path_foto}",$output);
        exec("python ./compareImage.py {$path_foto} 2>&1",$output);
        $sizeoutput = sizeof($output);
        
        $arr = array();
        if(strlen($output[$sizeoutput-1])>0){
            //output[0] adalah return dari python
            //sekarang dipisah by ,
            $daftarGambar = explode(",", $output[$sizeoutput-1]);
            //ambil path gbr sesuai yg di dbase
            //separate by \

            $arrayPathFoto = array();
            for($i=0;$i<sizeof($daftarGambar);$i++){
                $pathdbase = substr($daftarGambar[$i],10);
                array_push($arrayPathFoto,$pathdbase);
            } 

            $arrayIdBarang = array();
            for($i=0;$i<sizeof($arrayPathFoto);$i++){
                $idBarang = substr($arrayPathFoto[$i],2,2);
                $ada = false;
                for($j=0;$j<sizeof($arrayIdBarang);$j++){
                    if($idBarang==$arrayIdBarang[$j]){
                        $ada=true;
                    }
                }
                if(!$ada){
                    array_push($arrayIdBarang,$idBarang);
                }
            }

            $makanan = new masterbarangs();
            $data = $makanan->where("username","=",$username)
                            ->where("status","=",'1')
                            ->get();

            for($i=0;$i<sizeof($data);$i++){
                $idBarang = $data[$i]['idBarang'];
                $ada=false;
                for($j=0;$j<sizeof($arrayIdBarang);$j++){
                    if($idBarang==$arrayIdBarang[$j]){
                        $ada=true;
                    }
                }
                if($ada){
                    $foto     = new fotos();
                    $addressFoto = "default.jpg";
                    $datafoto = $foto->where("idBarang","=",$idBarang)->get();
                    if(sizeof($datafoto)>0){
                        $addressFoto = $datafoto[0]['foto'];
                    }

                    $elementBaru = array("idBarang"=>$idBarang, "idKategori"=>$data[$i]['idKategori'], 
                                            "namaBarang"=>$data[$i]['namaBarang'],"deskripsiBarang"=>$data[$i]['deskripsiBarang'],
                                            "hargaBarangAsli"=>$data[$i]['hargaBarangAsli'],"hargaBarangFoodWaste"=>$data[$i]['hargaBarangFoodWaste'],
                                            "foto"=>$addressFoto);
                    array_push($arr,$elementBaru);
                }
            }
        }
        $res['data']=$arr;
        $res['status']="Sukses";
        return json_encode($res);
    }
    public function cobacoba(Request $req)
    {
        $path_foto = "./makanan/paninabakery.jpg";
        $output = array();
        exec("python ./compareImage.py {$path_foto} 2>&1",$output);
        $sizeoutput = sizeof($output);
        return $output;
    }
}
