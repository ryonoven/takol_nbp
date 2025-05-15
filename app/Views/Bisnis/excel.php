<?php
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Form_Bisnis.xls");
?>

<html>
    <body>
        <table border="1">
            <!-- Table header -->
            <thead>
                <tr>
                    <th>NO</th>
                    <th>FORM BISNIS</th>
                    <th>KETERKAITAN DENGAN UNIT KERJA LAIN</th>
                    <th>KETERKAITAN DENGAN PIHAK KETIGA</th>
                    <th>DATA YANG DIHASILKAN</th>
                    <th>APLIKASI YANG DIGUNAKAN</th>
                </tr>
            </thead>
            <!-- Table body -->
            <tbody>
                <?php if (empty($bisnis)) { ?>
                    <tr>
                        <td colspan="6">No data available.</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($bisnis as $row) : ?>
                        <tr>
                            <td><?= $row['id']; ?></td>
                            <td><?= $row['bis']; ?></td>
                            <td><?= $row['lain']; ?></td>
                            <td><?= $row['ketiga']; ?></td>
                            <td><?= $row['hasil']; ?></td>
                            <td><?= $row['apps']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </body>

</html>
