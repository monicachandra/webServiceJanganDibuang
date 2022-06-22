<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\globalsettings;

class GlobalSettingController extends Controller
{
    public function getDataSetting()
    {
        $datasetting = new globalsettings();
        $data        = $datasetting->find(1);
        $res['data']    = $data;
        $res['status']="Sukses";
        return json_encode($res);
    }
    public function editSetting(Request $req)
    {
        $appFee = $req->appFee;
        $limitShow = $req->limitShow;
        $datasetting = new globalsettings();
        $data        = $datasetting->find(1);
        $data->appFee = $appFee;
        $data->limitShowMakanan = $limitShow;
        $data->save();
        $res['status']="Sukses";
        return json_encode($res);
    }
}
