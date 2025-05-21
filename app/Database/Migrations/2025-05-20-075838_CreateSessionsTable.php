<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSessionsTable extends Migration
{
    public function up()
    {
        // Membuat tabel 'ci_sessions'
        $this->forge->addField([
            'id'                => [
                'type'           => 'VARCHAR',
                'constraint'     => 128,
                'null'           => false,
            ],
            'ip_address'        => [
                'type'           => 'VARCHAR',
                'constraint'     => 45,
                'null'           => false,
            ],
            'timestamp'         => [
                'type'           => 'INT',
                'constraint'     => 10,
                'null'           => false,
            ],
            'data'              => [
                'type'           => 'TEXT',
                'null'           => false,
            ],
        ]);

        // Menambahkan primary key untuk 'id'
        $this->forge->addPrimaryKey('id');

        // Membuat tabel
        $this->forge->createTable('ci_sessions');
    }

    public function down()
    {
        // Menghapus tabel 'ci_sessions'
        $this->forge->dropTable('ci_sessions');
    }
}
