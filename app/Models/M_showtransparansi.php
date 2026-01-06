<?php

namespace App\Models;

use CodeIgniter\Model;

class M_ShowTransparansi extends Model
{
    protected $table = 'showtransparansi';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kodebpr',
        'periode_id',
        'tahun',
        'dirut',
        'komut',
        'tanggal',
        'lokasi',
        'kesimpulan',
        'positifstruktur',
        'positifproses',
        'positifhasil',
        'negatifstruktur',
        'negatifproses',
        'negatifhasil',
        'pdf1_filename',
        'pdf2_filename',
        'cover'
    ];

    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahttd($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function updatecover($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    // Fungsi untuk menghitung nilai komposit
    public function hitungNilaiKomposit($kodebpr, $periodeId)
    {
        // Menggunakan hitungRataRata untuk mendapatkan rata-rata
        $nilaikomposit = $this->hitungRataRata($kodebpr, $periodeId);

        // Pembulatan nilai komposit
        return ($nilaikomposit - floor($nilaikomposit)) >= 0.5 ? ceil($nilaikomposit) : floor($nilaikomposit);
    }

    // Menyimpan atau memperbarui data ke tabel showfaktor
    public function simpanShowTransparansi($data)
    {
        // Periksa apakah data dengan kodebpr dan periode_id sudah ada
        $existingData = $this->where('kodebpr', $data['kodebpr'])
            ->where('periode_id', $data['periode_id'])
            ->first();

        if ($existingData) {
            // Jika ada, update data yang ada
            $this->update($existingData['id'], $data);
        } else {
            // Jika tidak ada, simpan data baru
            $this->save($data);
        }
    }

    // Fungsi untuk menghitung peringkat komposit
    public function hitungPeringkatKomposit($nilaikomposit)
    {
        if ($nilaikomposit == 1) {
            return 'Sangat Baik';
        } elseif ($nilaikomposit == 2) {
            return 'Baik';
        } elseif ($nilaikomposit == 3) {
            return 'Cukup';
        } elseif ($nilaikomposit == 4) {
            return 'Kurang Baik';
        } elseif ($nilaikomposit == 5) {
            return 'Buruk';
        } else {
            return 'Nilai komposit tidak valid';
        }
    }

    // Fungsi untuk mendapatkan kelas warna untuk peringkat komposit
    public function getColorClassForPeringkat($peringkatkomposit)
    {
        switch ($peringkatkomposit) {
            case 'Sangat Baik':
                return 'text-success';
            case 'Baik':
                return 'text-info';
            case 'Cukup':
                return 'text-warning';
            case 'Kurang Baik':
                return 'text-danger';
            case 'Buruk':
                return 'text-dark';
            default:
                return 'text-muted';
        }
    }
}
