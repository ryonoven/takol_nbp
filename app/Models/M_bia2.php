<?php
namespace App\Models;

use CodeIgniter\Model;

class M_bia2 extends Model
{
    protected $table = 'bia2';

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }

    public function checkIncrement()
    {
        if (empty($this->getAllData())) {
            $this->db->query('ALTER TABLE bia2 AUTO_INCREMENT = 1');
        }
    }

    public function setIncrement($value)
    {
        $value = (int) $value;
        $sql = 'ALTER TABLE bia2 AUTO_INCREMENT = ?';
        $query = $this->db->query($sql, $value);
        return $query;
    }

    public function getAllData()
    {
        return $this->builder->get()->getResultArray();
    }

    public function tambah($data)
    {
        return $this->builder->insert($data);
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        $this->model->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('bia2'));

        // Menghapus data user berdasarkan ID yang diberikan
        // Menggunakan metode delete() pada tabel "user" dengan kondisi ID = $id
        // $lastData = $this->builder->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();
        // $this->builder->delete(['id' => $id]);
        // $this->setIncrement($lastData[0]['id']);
    }

    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

}