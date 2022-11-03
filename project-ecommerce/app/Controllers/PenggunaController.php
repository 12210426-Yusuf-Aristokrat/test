<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Agoenxz21\Datatables\Datatable;
use App\Models\PenggunaModel;
use CodeIgniter\Email\Email;
use Config\Email as ConfigEmail;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Message;

class PenggunaController extends BaseController
{

    public function index(){
        return view('pengguna/table');
    }

    public function login()
    {
        // mengambil email dan password pengguna
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('sandi');

        $pengguna = (new PenggunaModel())->where('email',$email)->first();

        if($pengguna == null) {
            return $this->response->setJSON(['message'=>'email tidak terdaftar'])
            ->setStatusCode(404);
        }
        
        //cek password
        $cekPassword = password_verify($password,$pengguna['sandi']);
        if($cekPassword == false){
            return $this->response->setJSON(['message'=>'email atau sandi tidak cocok'])
            ->setStatusCode(403);
        }

        $this->session->set('pengguna', $pengguna);
        return $this->response->setJSON(['message'=>"Selamat Datang {$pengguna['nama']}"])->setStatusCode(200);
    }

    public function viewLogin(){
        return view('login');
    }

    public function lupaPassword(){
        $email = $this->request->getPost('email');
        
        $pengguna = (new PenggunaModel())->where('email',$email)->first();

        if($pengguna == null) {
            return $this->response->setJSON(['message->'=>'email tidak terdaftar'])->setStatusCode(403);
        }
        $sandibaru=substr(md5(date('Y-m-dH:i:s')),5,5);
        $pengguna['sandi'] = password_hash($sandibaru,PASSWORD_BCRYPT);
        $r =(new PenggunaModel())->update($pengguna['id'],$pengguna);

        if($r == false){
            return $this->response->setJSON(['Message'=>"Gagal Merubah Sandi"])->setStatusCode(502);
        }

        $email = new Email(new ConfigEmail());
        $email->setFrom('ecommercebot32@gmail.com', 'Sistem Informasi Ecommerce');
        $email->setTo($pengguna['email']);
        $email->setSubject("Reset Sandi Pengguna");
        $email->setMessage("Hallo {$pengguna['nama']} telah meminta reset sandi. Sandi Baru kamu adalah <b>$sandibaru</b>");
        $r = $email->send();

        //logic
        if($r == true){
            return $this->response->setJSON(['Message'=>"Sandi baru sudah di kirim ke alamat email $email"])->setStatusCode(200);
        }else{
            return $this->response->setJSON(['Message'=>"maaf ada kesalahan pengiriman ke alamat email $email"])->setStatusCode(500);
        }
    }

    public function viewLupaPassword()
    {
        return view('lupaPassword');
    }

    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('login');
    }

  

    public function all()
    {
        $pm = new PenggunaModel();
        $pm->select('id , nama ,email , gender');
        return(new Datatable($pm))
            ->setFieldFilter(['nama','email','gender'])
            ->draw();
    }

    public function show($id){
        $r= (new PenggunaModel())->where('id',$id)->first();
        if($r == null)throw PageNotFoundException::forPageNotFound();

        return $this->response->setJSON($r);
    }

    public function store(){
        $pm = new PenggunaModel();
        $sandi = $this->request->getvar('sandi');

        $id = $pm-> insert([
            'nama' => $this->request->getVar('nama'),
            'gender' => $this->request->getVar('gender'),
            'email' => $this->request->getVar('email'),
            'sandi'=>password_hash($sandi, PASSWORD_BCRYPT),
        ]);
        return $this->response->setJSON(['id'=>$id])
            ->setStatusCode(intval($id) > 0 ? 200 : 406);
    }

    public function update(){
        $pm = new PenggunaModel();
        $id = (int)$this->request->getvar('id');

        if($pm->find($id) == null)
            throw PageNotFoundException::forPageNotFound();

        $hasil = $pm-> update($id,[
            'nama' => $this->request->getVar('nama'),
            'gender' => $this->request->getVar('gender'),
            'email' => $this->request->getVar('email'),
        ]);
            return $this->response->setJSON(['result'=>$hasil]);
    }

    public function delete(){
        $pm = new PenggunaModel();
        $id = $this->request->getvar('id');
        $hasil = $pm->delete($id);
        return $this->response->setJSON(['result'=>$hasil]);
    }
}
