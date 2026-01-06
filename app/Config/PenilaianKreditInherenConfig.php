<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class PenilaianKreditInherenConfig extends BaseConfig
{
    public static function get()
    {
        return [
            1 => [
                'descriptions' => [
                    1 => 'Sangat Baik',
                    2 => 'Baik',
                    3 => 'Cukup',
                    4 => 'Kurang Baik',
                    5 => 'Buruk'
                ],
                'catatan' => '-'
            ],

            2 => [
                'threshold' => '95%',
                'descriptions' => [
                    1 => '≤ 95%',
                    2 => '>95%, komponen aset produktif memiliki eksposur Risiko kredit rendah',
                    3 => '>95%, komponen aset produktif memiliki eksposur Risiko kredit moderat',
                    4 => '>95%, komponen aset produktif memiliki eksposur Risiko kredit tinggi',
                    5 => '>95%, komponen aset produktif memiliki eksposur Risiko kredit sangat tinggi'
                ],
                'catatan' => 'BPR dengan rasio ≤ 95% dimungkinkan mendapat peringkat lebih buruk dari 1 antara lain dalam hal BPR memiliki aset produktif dengan eksposur Risiko kredit yang lebih tinggi, misalnya penempatan dana pada'
            ],

            3 => [
                'threshold' => '75%',
                'descriptions' => [
                    1 => '≤ 75%',
                    2 => '>75%, skema kredit sebagian besar atau seluruhnya sederhana, dan jenis kredit tidak beragam',
                    3 => '>75%, skema kredit sebagian besar atau seluruhnya sederhana, dan jenis kredit beragam',
                    4 => '>75%, skema kredit sebagian besar atau seluruhnya kompleks, dan jenis kredit tidak beragam',
                    5 => '>75%, skema kredit sebagian besar atau seluruhnya kompleks, dan jenis kredit beragam'
                ],
                'catatan' => 'BPR dengan rasio ≤75% dimungkinkan mendapat peringkat lebih buruk dari 1, dalam hal portofolio kredit'
            ],

            4 => [
                'threshold' => '20%',
                'descriptions' => [
                    1 => '≤ 20%',
                    2 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang sangat lama',
                    3 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang lama',
                    4 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang cukup lama',
                    5 => '≥ 20%, Target pasar tidak berubah selama jangka waktu yang singkat'
                ],
                'catatan' => 'Konsentrasi kredit pada 25 debitur terbesar'
            ],

            5 => [
                'threshold' => '85%',
                'descriptions' => [
                    1 => '≤ 85%',
                    2 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang sangat lama',
                    3 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang lama',
                    4 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang cukup lama',
                    5 => '≥ 85%, Kredit yang berasal dari 3 (tiga) sektor ekonomi terbesar tidak berubah selama jangka waktu yang singkat'
                ],
                'catatan' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan'
            ],

            6 => [
                'descriptions' => [
                    1 => 'Sangat Baik',
                    2 => 'Baik',
                    3 => 'Cukup',
                    4 => 'Kurang Baik',
                    5 => 'Buruk'
                ],
                'catatan' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan (Kualitas Aset)'
            ],

            7 => [
                'threshold' => '7%',
                'descriptions' => [
                    1 => '≤ 7%',
                    2 => "Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang baik, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi tidak signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan tidak signifikan <br>
                    • Sektor ekonomi berisiko tinggi tidak signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari tidak signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain",
                    3 => 'Rasio di atas  ambang batas  peringkat 1, dengan  kondisi pemberian  kredit memiliki kualitas yang cukup baik, namun terdapat potensi penurunan, antara lain ditunjukkan dengan: <br>
                    • Kredit  restrukturisasi cukup signifikan <br>
                    • Penurunan  kualitas kredit  dari Performing Loan ke Non Performing Loan  cukup signifikan <br>
                    • Sektor  ekonomi berisiko tinggi cukup signifikan <br>
                    • Jumlah kredit  lancar yang menunggak >7 hari cukup <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain signifikan',
                    4 => 'Rasio di atas  ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang kurang baik, antara lain ditunjukkan dengan: <br> 
                    • Kredit  restrukturisasi signifikan <br>
                    • Penurunan kualitas kreditdari Performing Loan ke Non Performing Loan signifikan <br>
                    • Sektor ekonomi berisiko tinggi signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit',
                    5 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang buruk, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi sangat signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan sangat signifikan <br>
                    • Sektor ekonomi berisiko tinggi sangat signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari sangat signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit '
                ],
                'catatan' => 'Parameter rasio kredit per sektor ekonomi terhadap total kredit yang diberikan (Kualitas Aset)'
            ],

            8 => [
                'threshold' => '5%',
                'descriptions' => [
                    1 => '≤ 5%',
                    2 => "Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang baik, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi tidak signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan tidak signifikan <br>
                    • Sektor ekonomi berisiko tinggi tidak signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari tidak signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain",
                    3 => 'Rasio di atas  ambang batas  peringkat 1, dengan  kondisi pemberian  kredit memiliki kualitas yang cukup baik, namun terdapat potensi penurunan, antara lain ditunjukkan dengan: <br>
                    • Kredit  restrukturisasi cukup signifikan <br>
                    • Penurunan  kualitas kredit  dari Performing Loan ke Non Performing Loan  cukup signifikan <br>
                    • Sektor  ekonomi berisiko tinggi cukup signifikan <br>
                    • Jumlah kredit  lancar yang menunggak >7 hari cukup <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain signifikan',
                    4 => 'Rasio di atas  ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang kurang baik, antara lain ditunjukkan dengan: <br>
                    • Kredit  restrukturisasi signifikan <br>
                    • Penurunan kualitas kreditdari Performing Loan ke Non Performing Loan signifikan <br>
                    • Sektor ekonomi berisiko tinggi signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit',
                    5 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang buruk, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi sangat signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan sangat signifikan <br>
                    • Sektor ekonomi berisiko tinggi sangat signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari sangat signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit'
                ],
                'catatan' => 'Kredit bermasalah neto / total kredit yang diberikan'
            ],

            9 => [
                'threshold' => '7%',
                'descriptions' => [
                    1 => '≤ 7%',
                    2 => "Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang baik, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi tidak signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan tidak signifikan <br>
                    • Sektor ekonomi berisiko tinggi tidak signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari tidak signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain",
                    3 => 'Rasio di atas  ambang batas  peringkat 1, dengan  kondisi pemberian  kredit memiliki kualitas yang cukup baik, namun terdapat potensi penurunan, antara lain ditunjukkan dengan: <br>
                    • Kredit  restrukturisasi cukup signifikan <br>
                    • Penurunan  kualitas kredit  dari Performing Loan ke Non Performing Loan  cukup signifikan <br>
                    • Sektor  ekonomi berisiko tinggi cukup signifikan <br>
                    • Jumlah kredit  lancar yang menunggak >7 hari cukup <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan penempatan pada bank lain signifikan',
                    4 => 'Rasio di atas  ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang kurang baik, antara lain ditunjukkan dengan: <br>
                    • Kredit  restrukturisasi signifikan <br>
                    • Penurunan kualitas kreditdari Performing Loan ke Non Performing Loan signifikan <br>
                    • Sektor ekonomi berisiko tinggi signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit',
                    5 => 'Rasio di atas ambang batas peringkat 1, dengan kondisi pemberian kredit memiliki kualitas yang buruk, antara lain ditunjukkan dengan: <br>
                    • Kredit restrukturisasi sangat signifikan <br>
                    • Penurunan kualitas kredit dari Performing Loan ke Non Performing Loan sangat signifikan <br>
                    • Sektor ekonomi berisiko tinggi sangat signifikan <br>
                    • Jumlah kredit lancar yang menunggak >7 hari sangat signifikan <br>
                    • Komponen aset produktif bermasalah sebagian besar merupakan kredit '
                ],
                'catatan' => 'Rasio aset produktif bermasalah terhadap total aset produktif'
            ],

            10 => [
                'descriptions' => [
                    1 => '• Pertumbuhan kredit di atas rata-rata industri, dan <br>
                        • Seluruhnya disalurkan kepada sektor ekonomi yang dikuasai.',
                    2 => '• Pertumbuhan kredit di atas rata-rata industri, dan <br>
                    • Sebagian besar disalurkan kepada sektor ekonomi yang dikuasai.',
                    3 => '• Pertumbuhan kredit di atas atau sama dengan ratarata industri, dan Sebagian kecilatau tidak sama sekali disalurkan  kepada sektor ekonomi yang dikuasai atau <br>
                    • Pertumbuhan kredit di bawah rata-rata industri, dan <br>
                    • Seluruhnya disalurkan kepada sektor ekonomi yang dikuasai.',
                    4 => '• Pertumbuhan kredit di bawah rata-rata industri, dan <br>
                    • Sebagian besar disalurkan kepada sektor ekonomi yang dikuasai.',
                    5 => '• Pertumbuhan kredit di bawah rata-rata industri, dan <br>
                    • Sebagian kecil atau tidak sama sekali disalurkan kepada sektor ekonomi yang dikuasai.'
                ],
                'catatan' => 'Penilaian berdasarkan kualitas pelaksanaan tata kelola'
            ],

            11 => [
                'descriptions' => [
                    1 => 'Terdapat perubahan faktor eksternal, namun tidak berdampak pada kemampuan debitur untuk membayar kembali pinjaman.',
                    2 => 'Terdapat perubahan faktor eksternal, yang berdampak pada kemampuan debitur untuk membayar kembali pinjaman sehingga terjadi tunggakan pinjaman namun tidak menyebabkan penurunan kualitas kredit debitur.',
                    3 => 'Terdapat perubahan faktor eksternal, yang berdampak pada kinerja bisnis debitur sehingga menyebabkan terjadi tunggakan pinjaman tetapi tidak menurunkan kualitas kredit debitur menjadi NPL.',
                    4 => 'Terdapat perubahan faktor eksternal, yang menyebabkan penurunan kualitas kredit debitur hingga menjadi NPL.',
                    5 => 'Terdapat perubahan faktor eksternal, yang menyebabkan kebangkrutan debitur.'
                ],
                'catatan' => 'Penilaian berdasarkan kualitas pelaksanaan tata kelola'
            ],

            12 => [
                'descriptions' => [
                    1 => 'Sangat Baik',
                    2 => 'Baik',
                    3 => '>Cukup',
                    4 => '>Kurang',
                    5 => '>Buruk'
                ],
                'catatan' => 'Pertumbuhan kredit year-on-year'
            ],
        ];
    }
}