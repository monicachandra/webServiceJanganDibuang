<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\actors;

class PushNotificationController extends Controller
{
    public function sendNotification(Request $req)
    {
        $username = $req->username;
        $judul    = $req->judul;
        $isi      = $req->isi;
        
        $act = new actors();
        $tkn = $act->find($username)->firebaseToken;
        $rtkn = []; 
        array_push($rtkn, $tkn); 
        $ttt = 'QUFBQUVtcGdWSzQ6QVBBOTFiRVd4Y3VKV1BFTFE5TUF0Y29BY2dtel94RmxwbmFQZHQtbmhTU19QQmUwWFIxOHFGRmtYdWxOME5FWFVhRmtaWHBYaU5oZEdHdHhXNlJMNkREVUJfc01PY29SbktyX3hqNU9JcmxNTnRPeGZWbWtEYnFSNnR3UUpyWk5qWUxJZ2pvUjZ2RjU=';

        $data = [
            "registration_ids" => $rtkn,
            "notification" => [
                "title" => $judul,
                "body" => $isi,  
            ]
        ];
        $dataString = json_encode($data);
        $headers = [
            'Authorization: key=' . base64_decode($ttt),
            'Content-Type: application/json',
        ];
  
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        $response = curl_exec($ch);
        //dd($response);
        $res['status']=$response;
        return json_encode($res);
    }

    public function sendNotificationAdmin(Request $req)
    {
        $judul    = $req->judul;
        $isi      = $req->isi;

        $rtkn = []; 
        $act = new actors();
        $tkn = $act->where("tipeActor","=","A")->get();
        for($i=0;$i<sizeof($tkn);$i++){
            array_push($rtkn, $tkn[$i]->firebaseToken); 
        }
        $ttt = 'QUFBQUVtcGdWSzQ6QVBBOTFiRVd4Y3VKV1BFTFE5TUF0Y29BY2dtel94RmxwbmFQZHQtbmhTU19QQmUwWFIxOHFGRmtYdWxOME5FWFVhRmtaWHBYaU5oZEdHdHhXNlJMNkREVUJfc01PY29SbktyX3hqNU9JcmxNTnRPeGZWbWtEYnFSNnR3UUpyWk5qWUxJZ2pvUjZ2RjU=';

        $data = [
            "registration_ids" => $rtkn,
            "notification" => [
                "title" => $judul,
                "body" => $isi,  
            ]
        ];
        $dataString = json_encode($data);
        $headers = [
            'Authorization: key=' . base64_decode($ttt),
            'Content-Type: application/json',
        ];
  
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        $response = curl_exec($ch);
        //dd($response);
        $res['status']=$response;
        return json_encode($res);
    }
}
