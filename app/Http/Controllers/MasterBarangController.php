<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\masterbarangs;
use App\fotos;

class MasterBarangController extends Controller
{
    public function addMakanan(Request $req)
    {
        $idKategori       = $req->idKategori;
        $namaMakanan      = $req->namaMakanan;
        $deskripsi        = $req->deskripsi;
        $hargaAwal        = $req->hargaAwal;
        $hargaWaste       = $req->hargaWaste;
        $username         = $req->username;
        $status           = $req->status;

        $makanan                        = new masterbarangs();
        $makanan->idKategori            = $idKategori;
        $makanan->namaBarang            = $namaMakanan;
        $makanan->deskripsiBarang       = $deskripsi;
        $makanan->hargaBarangAsli       = $hargaAwal;
        $makanan->hargaBarangFoodWaste  = $hargaWaste;
        $makanan->username              = $username;
        $makanan->status                = $status;
        $save                           = $makanan->save();
        
        $res['id']=$makanan->idBarang;
        $res['nama']=$makanan->namaBarang;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function editMakanan(Request $req)
    {
        $idKategori       = $req->idKategori;
        $namaMakanan      = $req->namaMakanan;
        $deskripsi        = $req->deskripsi;
        $hargaAwal        = $req->hargaAwal;
        $hargaWaste       = $req->hargaWaste;
        $idBarang         = $req->idBarang;
        $status           = $req->status;

        $makanan                        = new masterbarangs();
        $data                           = $makanan->find($idBarang);
        $data->idKategori               = $idKategori;
        $data->namaBarang               = $namaMakanan;
        $data->deskripsiBarang          = $deskripsi;
        $data->hargaBarangAsli          = $hargaAwal;
        $data->hargaBarangFoodWaste     = $hargaWaste;
        $data->status                   = $status;
        $save                           = $data->save();
        
        $res['id']=$data->idBarang;
        $res['nama']=$data->namaBarang;
        $res['status']="Sukses";
        return json_encode($res);
    }
    
    public function getDataMakanan(Request $req)
    {
        $idBarang = $req->idBarang;
        $makanan = new masterbarangs();
        $data = $makanan->find($idBarang);
        $res['data']=$data;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getAllMakanan(Request $req)
    {
        $username = $req->username;
        $status   = $req->status;
        $makanan = new masterbarangs();
        $data = $makanan->where("username","=",$username)
                        ->where("status","=",$status)
                        ->get();

        $arr = array();
        for($i=0;$i<sizeof($data);$i++){
            $idBarang = $data[$i]['idBarang'];
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
        $res['data']=$arr;
        $res['status']="Sukses";
        return json_encode($res);
    }
    
    public function hapusItem(Request $req)
    {
        $idBarang     = $req->idBarang;
        $makanan      = new masterbarangs();
        $data         = $makanan->find($idBarang);
        $data->status = 0;
        $save         = $data->save();
        $res['status']="Sukses";
        return json_encode($res);
    }
}
