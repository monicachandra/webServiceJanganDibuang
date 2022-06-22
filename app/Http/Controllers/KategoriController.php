<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\kategoris;

class KategoriController extends Controller
{
    public function getAllKategorisNoPaket()
    {
        $kat        = new kategoris();
        $dataAll    = $kat->where('namaKategori','<>','Paket')
                          ->where('status','=',1)
                            ->get();
        $res['status'] = "Sukses";
        $res['data']   = $dataAll;
        return json_encode($res);
    }
    public function getAllKategori(Request $req)
    {
        $status = $req->status;
        $kat        = new kategoris();
        $dataAll    = $kat->where('status','=',$status)->get();
        $res['status'] = "Sukses";
        $res['data']   = $dataAll;
        return json_encode($res);
    }

    public function tambahKategori(Request $req)
    {
        $nama = $req->nama;
        $kat = new kategoris();
        $kat->namaKategori = $nama;
        $kat->status = 1;
        $save = $kat->save();
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function editKategori(Request $req)
    {
        $id   = $req->id;
        $nama = $req->nama;
        $k = new kategoris();

        $kat = $k->find($id);
        $kat->idKategori = $id;
        $kat->namaKategori = $nama;
        $save = $kat->save();
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function nonAktifKategori(Request $req)
    {
        $id = $req->id;
        $k = new kategoris();
        $kat = $k->find($id);
        $kat->status = 0;
        $save = $kat->save();
        $res['status'] = "Sukses";
        return json_encode($res);
    }

    public function aktifKategori(Request $req)
    {
        $id = $req->id;
        $k = new kategoris();
        $kat = $k->find($id);
        $kat->status = 1;
        $save = $kat->save();
        $res['status'] = "Sukses";
        return json_encode($res);
    }
}
