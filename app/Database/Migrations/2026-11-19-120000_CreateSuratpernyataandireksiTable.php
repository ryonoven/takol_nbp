<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Surat Pernyataan Direksi tentang Tanggung Jawab atas Laporan Keuangan (laporan tahunan).
 *  - suratpernyataandireksi        : header, satu baris per BPR + periode tahunan.
 *  - suratpernyataandireksi_detail : penandatangan (direksi), jumlah bebas, urut sesuai `urutan`.
 */
class CreateSuratpernyataandireksiTable extends Migration
{
    public function up()
    {
        $text = static fn(int $len) => ['type' => 'VARCHAR', 'constraint' => $len, 'null' => false, 'default' => ''];

        // ---- header
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 10, 'null' => false],
            'kodebpr'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'periode_id'   => ['type' => 'INT', 'constraint' => 10, 'null' => false],
            'kota'         => $text(100),
            'tanggalsurat' => ['type' => 'DATE', 'null' => true],
            'suratpdf'     => $text(50),   // scan surat bertanda tangan + meterai (opsional)
            'is_approved'  => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'approved_by'  => ['type' => 'INT', 'constraint' => 10, 'null' => true],
            'approved_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['kodebpr', 'periode_id'], 'uq_spd_bpr_periode');
        $this->forge->createTable('suratpernyataandireksi', true, ['ENGINE' => 'InnoDB']);

        // ---- detail: penandatangan
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'surat_id'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => false],
            'urutan'         => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'null' => false, 'default' => 0],
            'nama'           => $text(150),
            'jabatan'        => $text(150),
            'telepon'        => $text(30),
            'alamatkantor'   => $text(500),
            'alamatdomisili' => $text(500),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['surat_id', 'urutan'], false, false, 'idx_spdd_surat');
        $this->forge->addForeignKey('surat_id', 'suratpernyataandireksi', 'id', '', 'CASCADE', 'fk_spdd_surat');
        $this->forge->createTable('suratpernyataandireksi_detail', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('suratpernyataandireksi_detail', true);
        $this->forge->dropTable('suratpernyataandireksi', true);
    }
}
