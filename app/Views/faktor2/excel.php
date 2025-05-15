<?php
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Template TataKelola BPRK 12312024.xls");
?>

<html>
    <body>
        <table border="1">
            <!-- Table header -->
            <thead>
                <tr>
                    <th>No</th>
                    <th>Include dalam file teks</th>
                    <th>Flag Detail</th>
                    <th>Penggunaan</th>
                    <th>+/-</th>
                    <th>Kode Komponen</th>
                    <th>Kriteria / Indikator</th>
                    <th>Skala Penerapan*</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <!-- Table body -->
            <tbody>
                <?php if (empty($faktor2)) { ?>
                    <tr>
                        <td colspan="6">No data available.</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($faktor2 as $row) : ?>
                        <tr>
                            <td><?= $row['id']; ?></td>
                            <td><?= $row['masuktxt']; ?></td>
                            <td><?= $row['flagdetail']; ?></td>
                            <td><?= $row['penggunaan']; ?></td>
                            <td><?= $row['plusmin']; ?></td>
                            <td><?= $row['number2']; ?></td>
                            <td><?= $row['sub_category2']; ?></td>
                            <td><?= $row['nilai2']; ?></td>
                            <td><?= $row['keterangan2']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </body>

</html>