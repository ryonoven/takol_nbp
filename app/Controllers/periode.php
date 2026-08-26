<?php
namespace App\Controllers;

use App\Models\M_periode;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class Periode extends BaseController
{
    protected $periodeModel;
    protected $userModel;

    public function __construct()
    {
        $this->periodeModel = new M_periode();
        $this->userModel = new M_user();
    }

    public function index()
    {
        $user = $this->userModel->find(user_id());

        $data = [
            'judul' => 'Periode Pelaporan',
            'periodes' => $this->periodeModel->getPeriodeByBpr($user['kodebpr']),
            'current_periode' => session('active_periode'),
            'kodebpr' => $user['kodebpr'],
            'validation' => \Config\Services::validation()
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Periode/gcg', $data);
        echo view('templates/v_footer');
    }

    public function handlePeriode()
    {
        $action = $this->request->getPost('action');

        if (!in_array($action, ['create', 'select'])) {
            return redirect()->back()->with('error', 'Aksi tidak valid');
        }

        return $this->$action();
    }

    protected function create()
    {
        try {
            $user = $this->userModel->find(user_id());

            // Validation rules
            $rules = [
                'tahun' => [
                    'rules' => 'required|integer',
                    'errors' => [
                        'required' => 'Tahun harus dipilih',
                        'integer' => 'Tahun harus berupa angka'
                    ]
                ],
                'semester' => [
                    'rules' => 'required|in_list[1,2]',
                    'errors' => [
                        'required' => 'Semester harus dipilih',
                        'in_list' => 'Semester hanya boleh 1 atau 2'
                    ]
                ],
                'jenispelaporan' => [
                    'rules' => 'required|in_list[R,K]',
                    'errors' => [
                        'required' => 'Jenis pelaporan harus dipilih',
                        'in_list' => 'Jenis pelaporan tidak valid'
                    ]
                ],
                'modalinti' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Modal inti harus diisi'
                    ]
                ],
                'totalaset' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Total aset harus diisi'
                    ]
                ]
            ];

            if (!$this->validate($rules)) {
                $errors = $this->validator->getErrors();
                $errorMessage = implode('<br>', $errors);
                return redirect()->back()
                    ->withInput()
                    ->with('error', $errorMessage);
            }

            // Cek duplikasi periode
            $existing = $this->periodeModel->where([
                'kodebpr' => $user['kodebpr'],
                'tahun' => $this->request->getPost('tahun'),
                'semester' => $this->request->getPost('semester')
            ])->first();

            if ($existing) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Periode untuk Semester ' . $this->request->getPost('semester') . ' Tahun ' . $this->request->getPost('tahun') . ' sudah ada!');
            }

            // Clean and validate currency inputs
            $modalinti = $this->request->getPost('modalinti');
            $totalaset = $this->request->getPost('totalaset');

            // Remove formatting (dots and commas)
            $modalinti = str_replace(['.', ','], '', $modalinti);
            $totalaset = str_replace(['.', ','], '', $totalaset);

            // Validate numeric
            if (!is_numeric($modalinti) || !is_numeric($totalaset)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Modal inti dan Total aset harus berupa angka');
            }

            // Validate positive numbers
            if ($modalinti <= 0 || $totalaset <= 0) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Modal inti dan Total aset harus lebih dari 0');
            }

            // Determine kategori based on modalinti
            // A = >= 50 miliar, B = < 50 miliar
            $kategori = ($modalinti >= 50000000000) ? 'A' : 'B';

            // Insert data
            $data = [
                'user_id' => user_id(),
                'kodebpr' => $user['kodebpr'],
                'tahun' => $this->request->getPost('tahun'),
                'semester' => $this->request->getPost('semester'),
                'jenispelaporan' => $this->request->getPost('jenispelaporan'),
                'modalinti' => $modalinti,
                'totalaset' => $totalaset,
                'kategori' => $kategori
            ];

            $periodeId = $this->periodeModel->insert($data);

            if (!$periodeId) {
                // Log error for debugging
                log_message('error', 'Failed to insert periode: ' . json_encode($this->periodeModel->errors()));

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Gagal membuat periode. Silakan coba lagi.');
            }

            // Set active periode
            $this->setActivePeriode($periodeId);

            return redirect()->to('/ShowFaktor')
                ->with('message', 'Periode baru berhasil dibuat!');

        } catch (\Exception $e) {
            log_message('error', 'Exception in Periode::create(): ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function select()
    {
        try {
            $periodeId = $this->request->getPost('periode_id');
            $user = $this->userModel->find(user_id());

            if (!$periodeId) {
                return redirect()->back()
                    ->with('error', 'ID Periode tidak valid');
            }

            // Validasi kepemilikan periode
            $periode = $this->periodeModel->where([
                'id' => $periodeId,
                'kodebpr' => $user['kodebpr']
            ])->first();

            if (!$periode) {
                return redirect()->back()
                    ->with('error', 'Periode tidak ditemukan atau tidak memiliki akses');
            }

            $this->setActivePeriode($periodeId);

            return redirect()->to('/ShowFaktor')
                ->with('message', 'Periode berhasil dipilih!');

        } catch (\Exception $e) {
            log_message('error', 'Exception in Periode::select(): ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function switch($id)
    {
        try {
            $user = $this->userModel->find(user_id());

            // Validasi kepemilikan periode
            $periode = $this->periodeModel->where([
                'id' => $id,
                'kodebpr' => $user['kodebpr']
            ])->first();

            if (!$periode) {
                return redirect()->back()
                    ->with('error', 'Periode tidak valid');
            }

            $this->setActivePeriode($id);

            return redirect()->back()
                ->with('message', 'Periode berhasil diubah!');

        } catch (\Exception $e) {
            log_message('error', 'Exception in Periode::switch(): ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    protected function setActivePeriode($id)
    {
        session()->set('active_periode', $id);
        session()->set('periode_data', $this->periodeModel->find($id));
    }

    protected function hasActivePeriode()
    {
        return session()->has('active_periode');
    }
}