<?php
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Data Inventaris.xls");
?>

<html>
    <body>
        <table border="1">
            <!-- Table header -->
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA DATA</th>
                    <th>MEDIA PENYIMPANAN</th>
                    <th>LOKASI PENYIMPANAN</th>
                    <th>UNIT KERJA PENANGGUNG JAWAB</th>
                    <th>KETERANGAN</th>
                </tr>
            </thead>
            <!-- Table body -->
            <tbody>
                <?php if (empty($inv)) { ?>
                    <tr>
                        <td colspan="6">No data available.</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($inv as $row) : ?>
                        <tr>
                            <td><?= $row['id']; ?></td>
                            <td><?= $row['namadat']; ?></td>
                            <td><?= $row['media']; ?></td>
                            <td><?= $row['lokasi']; ?></td>
                            <td><?= $row['utgjawab']; ?></td>
                            <td><?= $row['keterangan']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </body>

</html>
