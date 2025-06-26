<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\M_nilaifaktor;
use App\Models\M_nilaifaktor2;
use App\Models\M_nilaifaktor3;
use App\Models\M_nilaifaktor4;
use App\Models\M_nilaifaktor5;
use App\Models\M_nilaifaktor6;
use App\Models\M_nilaifaktor7;
use App\Models\M_nilaifaktor8;
use App\Models\M_nilaifaktor9;
use App\Models\M_nilaifaktor10;
use App\Models\M_nilaifaktor11;
use App\Models\M_nilaifaktor12;

class M_showfaktor extends Model
{
    protected $table = 'showfaktor'; // Nama tabel di database
    protected $primaryKey = 'id'; // Primary key tabel
    protected $allowedFields = [
        'kodebpr',
        'periode_id',
        'tahun',
        'semester',
        'nilaikomposit',
        'peringkatkomposit',
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
        'pdf2_filename'
    ]; // Fields yang dapat diisi

    public function ubah($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function ubahttd($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }
    // Fungsi untuk menghitung nilai rata-rata faktor
    public function hitungRataRata($kodebpr, $periodeId)
    {
        // Semua faktor yang ada
        $allnfaktor = [
            'nfaktor' => new M_nilaifaktor(),
            'nfaktor2' => new M_nilaifaktor2(),
            'nfaktor3' => new M_nilaifaktor3(),
            'nfaktor4' => new M_nilaifaktor4(),
            'nfaktor5' => new M_nilaifaktor5(),
            'nfaktor6' => new M_nilaifaktor6(),
            'nfaktor7' => new M_nilaifaktor7(),
            'nfaktor8' => new M_nilaifaktor8(),
            'nfaktor9' => new M_nilaifaktor9(),
            'nfaktor10' => new M_nilaifaktor10(),
            'nfaktor11' => new M_nilaifaktor11(),
            'nfaktor12' => new M_nilaifaktor12()
        ];

        $totalNilai = 0;
        $countValid = 0;

        // Loop untuk menghitung total dan jumlah nilai valid
        foreach ($allnfaktor as $field => $model) {
            // Ambil nilai dari setiap faktor yang sesuai
            $nilai = $model->select($field) // Ambil nilai berdasarkan field (nfaktor, nfaktor2, dll)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->orderBy('created_at', 'DESC')
                ->first(); // Mengambil nilai terakhir

            if ($nilai && is_numeric($nilai[$field])) {
                $totalNilai += $nilai[$field]; // Menambahkan nilai ke total
                $countValid++;
            }
        }

        // Menghitung nilai rata-rata
        return $countValid > 0 ? $totalNilai / $countValid : 0;
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
    public function simpanShowFaktor($data)
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
