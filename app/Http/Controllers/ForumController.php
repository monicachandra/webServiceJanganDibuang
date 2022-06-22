<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\forums;
use App\actors;
use App\reportforums;

class ForumController extends Controller
{
    public function addForumAdmin(Request $req)
    {
        $judul     = $req->judul;
        $deskripsi = $req->deskripsi;
        $username  = $req->username;
        $fileName  = $req->m_filename;
        $dataGambar = base64_decode($req->m_image);
        file_put_contents("forum/".$fileName,$dataGambar);

        $forum = new forums();
        $forum->judulForum     = $judul;
        $forum->deskripsiForum = $deskripsi;
        $forum->tanggalForum   = date("Y-m-d");
        $forum->fotoForum      = $fileName;
        $forum->username       = $username;
        $forum->jenisUser      = 'A';
        $forum->statusAktif    = 1;
        $save                  = $forum->save();
        
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function addForumVendorPembeli(Request $req)
    {
        $judul     = $req->judul;
        $deskripsi = $req->deskripsi;
        $username  = $req->username;
        $jenisUser = $req->jenisUser;
        $fileName  = $req->m_filename;
        $dataGambar = base64_decode($req->m_image);
        file_put_contents("forum/".$fileName,$dataGambar);

        $forum = new forums();
        $forum->judulForum     = $judul;
        $forum->deskripsiForum = $deskripsi;
        $forum->tanggalForum   = date("Y-m-d");
        $forum->fotoForum      = $fileName;
        $forum->username       = $username;
        $forum->jenisUser      = $jenisUser;
        $forum->statusAktif    = 1;
        $save                  = $forum->save();
        
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getForumAdmin(Request $req)
    {
        $status = $req->status;
        $forum  = new forums();
        $arrForum = array();
        $dataForum = $forum->where('statusAktif','=',$status)->orderBy('tanggalForum','desc')->get();
        for($i=0;$i<sizeof($dataForum);$i++){
            $actor = new actors();
            $logoActor = $actor->find($dataForum[$i]->username)->logo;
            $reportf = new reportforums();
            $dataReport = $reportf->where('idForum','=',$dataForum[$i]->idForum)->where('statusAktif','=',1)->get();
            $baru = array("idForum"=>$dataForum[$i]->idForum,
                            "judulForum"=>$dataForum[$i]->judulForum,
                            "deskripsiForum"=>$dataForum[$i]->deskripsiForum,
                            "tanggalForum"=>$dataForum[$i]->tanggalForum,
                            "fotoForum"=>$dataForum[$i]->fotoForum,
                            "username"=>$dataForum[$i]->username,
                            "jenisUser"=>$dataForum[$i]->jenisUser,
                            "statusAktif"=>$dataForum[$i]->statusAktif,
                            "fotoProfil"=>$logoActor,
                            "report"=>$dataReport
                        );
            array_push($arrForum,$baru);
        }
        $res['data']=$arrForum;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getForumVendorPembeli(Request $req)
    {
        $username = $req->username;
        $forum  = new forums();
        $arrForum = array();
        $dataForum = $forum->where('statusAktif','=',1)->orWhere('statusAktif','=',2)->orderBy('tanggalForum','desc')->get();
        for($i=sizeof($dataForum)-1;$i>=0;$i--){
            $actor = new actors();
            $logoActor = $actor->find($dataForum[$i]->username)->logo;
            $reportf = new reportforums();
            $dataReport = $reportf->where('idForum','=',$dataForum[$i]->idForum)->where('statusAktif','=',1)->get();
            $baru = array("idForum"=>$dataForum[$i]->idForum,
                        "judulForum"=>$dataForum[$i]->judulForum,
                        "deskripsiForum"=>$dataForum[$i]->deskripsiForum,
                        "tanggalForum"=>$dataForum[$i]->tanggalForum,
                        "fotoForum"=>$dataForum[$i]->fotoForum,
                        "username"=>$dataForum[$i]->username,
                        "jenisUser"=>$dataForum[$i]->jenisUser,
                        "statusAktif"=>$dataForum[$i]->statusAktif,
                        "fotoProfil"=>$logoActor,
                        "report"=>$dataReport
                    );
            array_push($arrForum,$baru);                                    
        }
        $res['data']=$arrForum;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function getForumSaya(Request $req)
    {
        $username = $req->username;
        $forum  = new forums();
        $arrForum = array();
        $dataForum = $forum->where('username','=',$username)->orderBy('tanggalForum','desc')->get();
        for($i=0;$i<sizeof($dataForum);$i++){
            $actor = new actors();
            $logoActor = $actor->find($dataForum[$i]->username)->logo;
            $reportf = new reportforums();
            $dataReport = $reportf->where('idForum','=',$dataForum[$i]->idForum)->where('statusAktif','=',1)->get();
            $baru = array("idForum"=>$dataForum[$i]->idForum,
                            "judulForum"=>$dataForum[$i]->judulForum,
                            "deskripsiForum"=>$dataForum[$i]->deskripsiForum,
                            "tanggalForum"=>$dataForum[$i]->tanggalForum,
                            "fotoForum"=>$dataForum[$i]->fotoForum,
                            "username"=>$dataForum[$i]->username,
                            "jenisUser"=>$dataForum[$i]->jenisUser,
                            "statusAktif"=>$dataForum[$i]->statusAktif,
                            "fotoProfil"=>$logoActor,
                            "report"=>$dataReport
                        );
            array_push($arrForum,$baru);
        }
        $res['data']=$arrForum;
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function nonAktifkanForum(Request $req)
    {
        $idForum = $req->idForum;
        $forum = new forums();
        $dataForum = $forum->find($idForum);
        $dataForum->statusAktif=0;
        $save = $dataForum->save();
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function aktifkanForum(Request $req)
    {
        $idForum = $req->idForum;
        $forum = new forums();
        $dataForum = $forum->find($idForum);
        $dataForum->statusAktif=1;
        $save = $dataForum->save();
        $reportf = new reportforums();
        $dataHapus = $reportf->where('idForum',"=",$idForum)->get();
        for($i=0;$i<sizeof($dataHapus);$i++){
            $dataHapus[$i]->statusAktif=0;
            $saveHapus = $dataHapus[$i]->save();
        }
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function editHalfForum(Request $req)
    {
        $loginJenis=$req->loginJenis;
        $idForum   =$req->idForum;
        $judul     =$req->judul;
        $deskripsi =$req->deskripsi;
        $status    =1;
        if($loginJenis!="A"){
            $status=3;
        }
        else{
            $reportf = new reportforums();
            $dataHapus = $reportf->where('idForum',"=",$idForum)->get();
            for($i=0;$i<sizeof($dataHapus);$i++){
                $dataHapus[$i]->statusAktif=0;
                $saveHapus = $dataHapus[$i]->save();
            }
        }

        $forum     = new forums();
        $dataForum = $forum->find($idForum);
        $dataForum->judulForum = $judul;
        $dataForum->deskripsiForum = $deskripsi;
        $dataForum->statusAktif = $status;
        $save = $dataForum->save();
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function editFullForum(Request $req)
    {
        $fileName  = $req->m_filename;
        $dataGambar = base64_decode($req->m_image);
        file_put_contents("forum/".$fileName,$dataGambar);

        $loginJenis=$req->loginJenis;
        $idForum   =$req->idForum;
        $judul     =$req->judul;
        $deskripsi =$req->deskripsi;
        $status    =1;
        if($loginJenis!="A"){
            $status=3;
        }
        else{
            $reportf = new reportforums();
            $dataHapus = $reportf->where('idForum',"=",$idForum)->get();
            for($i=0;$i<sizeof($dataHapus);$i++){
                $dataHapus[$i]->statusAktif=0;
                $saveHapus = $dataHapus[$i]->save();
            }
        }

        $forum     = new forums();
        $dataForum = $forum->find($idForum);
        $dataForum->judulForum = $judul;
        $dataForum->deskripsiForum = $deskripsi;
        $dataForum->statusAktif = $status;
        $dataForum->fotoForum = $fileName;
        $save = $dataForum->save();
        $res['status']="Sukses";
        return json_encode($res);
    }

    public function submitReport(Request $req)
    {
        $username   = $req->username;
        $alasan     = $req->alasan;
        $subAlasan  = $req->subAlasan;
        $idForum    = $req->idForum;

        $reportf = new reportforums();
        $reportf->idForum = $idForum;
        $reportf->username = $username;
        $reportf->alasanReport = $alasan;
        $reportf->subAlasanReport = $subAlasan;
        $reportf->tanggal   = date("Y-m-d");
        $reportf->waktu   = date("H:i:s");
        $reportf->statusAktif   = 1;
        $save = $reportf->save();

        $actor = new actors();
        $dataActor = $actor->find($username);
        $tipeActor = $dataActor->tipeActor;

        $forum = new forums();
        $dataForum = $forum->find($idForum);
        if($tipeActor=='A'){
            $dataForum->statusAktif = 0;
        }
        else{
            $dataForum->statusAktif = 2;
        }
        $saveF = $dataForum->save();
        $res['status']="Sukses";
        return json_encode($res);
    }
}
