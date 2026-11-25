<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Komentar halaman pelaporan (laporan tahunan & SAF) — lihat App\Models\M_komentar.
 *  - Komentar disimpan di tabel yang sudah ada: transparansi_comments (subkategori = nama halaman).
 *  - Status baca: SATU baris per user + halaman + BPR + periode berisi id komentar terakhir yang dibaca
 *    (bukan satu baris per komentar), sehingga hitung belum-dibaca = COUNT(id > last_read_id).
 */
class CreateLaporantahunanCommentReads extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'kodebpr' => ['type' => 'VARCHAR', 'constraint' => 20],
            'periode_id' => ['type' => 'INT', 'unsigned' => true],
            'subkategori' => ['type' => 'VARCHAR', 'constraint' => 50],
            'last_read_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'read_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'kodebpr', 'periode_id', 'subkategori'], 'uq_ltcr_user_page');
        $this->forge->createTable('laporantahunan_comment_reads', true);

        // Index untuk daftar & hitung komentar per halaman (laporan tahunan & SAF)
        $this->db->query('ALTER TABLE transparansi_comments ADD INDEX idx_tc_page (kodebpr, periode_id, subkategori, id)');
        $this->db->query('ALTER TABLE semesteran_comments ADD INDEX idx_sc_page (kodebpr, periode_id, subkategori, id)');
    }

    public function down()
    {
        $this->forge->dropTable('laporantahunan_comment_reads', true);
        $this->db->query('ALTER TABLE transparansi_comments DROP INDEX idx_tc_page');
        $this->db->query('ALTER TABLE semesteran_comments DROP INDEX idx_sc_page');
    }
}
