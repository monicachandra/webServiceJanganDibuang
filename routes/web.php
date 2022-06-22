<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/getVendorWoConfirmation', ['uses'=>'ActorController@getVendorWoConfirmation']);
Route::post('/registerActor', ['uses'=>'ActorController@registerActor']);
Route::post('/loginActor', ['uses'=>'ActorController@loginActor']);
Route::post('/getDetailVendor', ['uses'=>'ActorController@getDetailVendor']);
Route::post('/konfirmasiVendor', ['uses'=>'ActorController@konfirmasiVendor']);
Route::post('/getProfilToko', ['uses'=>'ActorController@getProfilToko']);
Route::post('/getSaldo', ['uses'=>'ActorController@getSaldo']);
Route::post('/gantiPassword', ['uses'=>'ActorController@gantiPassword']);
Route::post('/gantiProfileImage', ['uses'=>'ActorController@gantiProfileImage']);
Route::post('/gantiProfilVendor', ['uses'=>'ActorController@gantiProfilVendor']);
Route::post('/gantiProfilPembeli', ['uses'=>'ActorController@gantiProfilPembeli']);
Route::post('/getFotoProfil', ['uses'=>'ActorController@getFotoProfil']);
Route::post('/registerActorGuest', ['uses'=>'ActorController@registerActorGuest']);
Route::post('/simpanAlamatVendor', ['uses'=>'ActorController@simpanAlamatVendor']);
Route::post('/simpanAlamatPembeli', ['uses'=>'ActorController@simpanAlamatPembeli']);
Route::post('/getAllAlamatPembeli', ['uses'=>'ActorController@getAllAlamatPembeli']);
Route::post('/editAlamatPembeli', ['uses'=>'ActorController@editAlamatPembeli']);

Route::get('/getKategoriNoPaket', ['uses'=>'KategoriController@getAllKategorisNoPaket']);
Route::post('/getAllKategori', ['uses'=>'KategoriController@getAllKategori']);
Route::post('/tambahKategori', ['uses'=>'KategoriController@tambahKategori']);
Route::post('/editKategori', ['uses'=>'KategoriController@editKategori']);
Route::post('/nonAktifKategori', ['uses'=>'KategoriController@nonAktifKategori']);
Route::post('/aktifKategori', ['uses'=>'KategoriController@aktifKategori']);

Route::post('/addMakanan', ['uses'=>'MasterBarangController@addMakanan']);
Route::post('/getDataMakanan', ['uses'=>'MasterBarangController@getDataMakanan']);
Route::post('/editMakanan', ['uses'=>'MasterBarangController@editMakanan']);
Route::post('/getAllMakanan', ['uses'=>'MasterBarangController@getAllMakanan']);
Route::post('/hapusItem', ['uses'=>'MasterBarangController@hapusItem']);

Route::post('/addFotoMakanan', ['uses'=>'FotoController@addFotoMakanan']);
Route::post('/getFotobyIdBarang', ['uses'=>'FotoController@getFotobyIdBarang']);
Route::post('/compareFotoMakanan', ['uses'=>'FotoController@compareFotoMakanan']);
Route::post('/compareFotoMakananDaily', ['uses'=>'FotoController@compareFotoMakananDaily']);
Route::get('/cobacoba', ['uses'=>'FotoController@cobacoba']);

Route::post('/simpanItem', ['uses'=>'DailyWasteController@simpanItem']);
Route::post('/simpanPaket', ['uses'=>'DailyWasteController@simpanPaket']);
Route::post('/getItemToday', ['uses'=>'DailyWasteController@getItemToday']);
Route::post('/getAllWaste', ['uses'=>'DailyWasteController@getAllWaste']);
Route::post('/getAllWastePembeli', ['uses'=>'DailyWasteController@getAllWastePembeli']);
Route::post('/getAllWastebyUsername', ['uses'=>'DailyWasteController@getAllWastebyUsername']);
Route::post('/getAlamatPengiriman', ['uses'=>'DailyWasteController@getAlamatPengiriman']);
Route::post('/getTidakLaku', ['uses'=>'DailyWasteController@getTidakLaku']);
Route::post('/getRankLaku', ['uses'=>'DailyWasteController@getRankLaku']);
Route::post('/getPenjualanTerpasif', ['uses'=>'DailyWasteController@getPenjualanTerpasif']);
Route::post('/getAllWasteInPeriode', ['uses'=>'DailyWasteController@getAllWasteInPeriode']);
Route::post('/adjustStokVendor', ['uses'=>'DailyWasteController@adjustStokVendor']);

Route::get('/getPenerimaBantuan',['uses'=>'PenerimaBantuanController@getPenerimaBantuan']);
Route::post('/getPenerimaBantuanUser',['uses'=>'PenerimaBantuanController@getPenerimaBantuanUser']);
Route::post('/getLaporanDonasi',['uses'=>'PenerimaBantuanController@getLaporanDonasi']);
Route::post('/simpanPenerimaBantuan',['uses'=>'PenerimaBantuanController@simpanPenerimaBantuan']);
Route::post('/editPenerimaBantuan',['uses'=>'PenerimaBantuanController@editPenerimaBantuan']);

Route::post('/checkOut', ['uses'=>'OrderController@checkOut']);
Route::post('/getListOrderan', ['uses'=>'OrderController@getListOrderan']);
Route::post('/getDetailListOrderan', ['uses'=>'OrderController@getDetailListOrderan']);
Route::post('/updateHOrderPenjual', ['uses'=>'OrderController@updateHOrderPenjual']);
Route::post('/sudahDiambil', ['uses'=>'OrderController@sudahDiambil']);
Route::post('/getListOrderanPembeli', ['uses'=>'OrderController@getListOrderanPembeli']);
Route::post('/getListOrderanPembeliDonasi', ['uses'=>'OrderController@getListOrderanPembeliDonasi']);
Route::post('/getListOrderanPembeliLaporan', ['uses'=>'OrderController@getListOrderanPembeliLaporan']);
Route::post('/getListOrderanAdminDonasi', ['uses'=>'OrderController@getListOrderanAdminDonasi']);
Route::post('/getPendapatanAdmin', ['uses'=>'OrderController@getPendapatanAdmin']);
Route::post('/getListOrderanLaporan', ['uses'=>'OrderController@getListOrderanLaporan']);
Route::post('/getListOrderanLaporanPembeli', ['uses'=>'OrderController@getListOrderanLaporanPembeli']);
Route::post('/uploadRatingComment', ['uses'=>'OrderController@uploadRatingComment']);
Route::post('/getPenjualanTerbanyak', ['uses'=>'OrderController@getPenjualanTerbanyak']);
Route::post('/getPembelianTerbanyak', ['uses'=>'OrderController@getPembelianTerbanyak']);

Route::get('/getDataSetting',['uses'=>'GlobalSettingController@getDataSetting']);
Route::post('/editSetting', ['uses'=>'GlobalSettingController@editSetting']);

Route::post('/topUp', ['uses'=>'UbahSaldoController@topUp']);
Route::post('/getTopUp', ['uses'=>'UbahSaldoController@getTopUp']);
Route::post('/getWD', ['uses'=>'UbahSaldoController@getWD']);
Route::post('/ajukanWD', ['uses'=>'UbahSaldoController@ajukanWD']);
Route::post('/batalkanWD', ['uses'=>'UbahSaldoController@batalkanWD']);
Route::post('/approveWD', ['uses'=>'UbahSaldoController@approveWD']);
Route::post('/tolakWD', ['uses'=>'UbahSaldoController@tolakWD']);
Route::get('/getDataKonfirmasiWD', ['uses'=>'UbahSaldoController@getDataKonfirmasiWD']);
Route::post('/getLaporanWDVendor', ['uses'=>'UbahSaldoController@getLaporanWDVendor']);
Route::post('/getLaporanWDAdmin', ['uses'=>'UbahSaldoController@getLaporanWDAdmin']);

Route::post('/addForumAdmin', ['uses'=>'ForumController@addForumAdmin']);
Route::post('/getForumAdmin', ['uses'=>'ForumController@getForumAdmin']);
Route::post('/getForumVendorPembeli', ['uses'=>'ForumController@getForumVendorPembeli']);
Route::post('/nonAktifkanForum', ['uses'=>'ForumController@nonAktifkanForum']);
Route::post('/aktifkanForum', ['uses'=>'ForumController@aktifkanForum']);
Route::post('/getForumSaya',['uses'=>'ForumController@getForumSaya']);
Route::post('/addForumVendorPembeli',['uses'=>'ForumController@addForumVendorPembeli']);
Route::post('/editHalfForum',['uses'=>'ForumController@editHalfForum']);
Route::post('/editFullForum',['uses'=>'ForumController@editFullForum']);
Route::post('/submitReport',['uses'=>'ForumController@submitReport']);

Route::post('/sendNotification',['uses'=>'PushNotificationController@sendNotification']);
Route::post('/sendNotificationAdmin',['uses'=>'PushNotificationController@sendNotificationAdmin']);

Route::post('/kirimEmailVerifikasi',['uses'=>'AmazonSESController@kirimEmailVerifikasi']);
Route::get('/verifikasi/{username}',['uses'=>'AmazonSESController@verifikasi']);
Route::post('/kirimEmailOTP',['uses'=>'AmazonSESController@kirimEmailOTP']);
Route::post('/kirimEmailOTPLagi',['uses'=>'AmazonSESController@kirimEmailOTPLagi']);
Route::post('/cekOtp',['uses'=>'AmazonSESController@cekOtp']);