<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Dokumen Pendukung laporan tahunan.
 *  - dokumenpendukung         : satu baris per BPR + periode (tempat/tanggal pernyataan + 3 file PDF bertanda tangan).
 *  - dokumenpendukung_anggota : daftar Direksi & Dewan Komisaris (penandatangan Lembar Pernyataan).
 */
class CreateDokumenpendukungTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'kodebpr' => ['type' => 'VARCHAR', 'constraint' => 20],
            'periode_id' => ['type' => 'INT', 'unsigned' => true],
            'kota' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tanggalsurat' => ['type' => 'DATE', 'null' => true],
            'suratdireksi_pdf' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'lembar_pdf' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'pengendalian_pdf' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['kodebpr', 'periode_id'], 'uq_dokpend_bpr_periode');
        $this->forge->createTable('dokumenpendukung', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'dok_id' => ['type' => 'INT', 'unsigned' => true],
            'jenis' => ['type' => 'ENUM', 'constraint' => ['direksi', 'dekom']],
            'urutan' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan' => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['dok_id', 'jenis', 'urutan']);
        $this->forge->addForeignKey('dok_id', 'dokumenpendukung', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('dokumenpendukung_anggota', true);
    }

    public function down()
    {
        $this->forge->dropTable('dokumenpendukung_anggota', true);
        $this->forge->dropTable('dokumenpendukung', true);
    }
}
