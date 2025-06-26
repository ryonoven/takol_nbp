<?php
namespace App\Models;

use CodeIgniter\Model;

class M_userclicklogs extends Model
{
    protected $table = 'user_click_logs';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'kodebpr', 'faktor1id', 'click_time'];

    public function insertLog($data)
    {
        try {
            // Cek apakah data valid
            if ($this->insert($data)) {
                log_message('info', 'Data berhasil disimpan di user_click_logs');
                return true;
            } else {
                log_message('error', 'Data gagal disimpan: ' . print_r($this->errors(), true));
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'Error saat menyimpan data: ' . $e->getMessage());
            return false;
        }
    }
}
