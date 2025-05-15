<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800"><?= $judul; ?></h1>
    
    <!-- Display success message -->
    <?php if (session()->get('message')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong><?= session()->getFlashdata('message'); ?></strong>
        </div>
    <?php endif; ?>

    <!-- Display error message -->
    <div class="row">
        <div class="col-md-6">
            <?php if (session()->get('err')) : ?>
                <div class="alert alert-danger" role="alert"><?= session()->get('err'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-reader">
            <div class="row">
                <div class="col-md"> 
                    <button type="button" class="btn btn-primary ml-3 mt-3" data-toggle="modal" data-target="#modalTambah">
                        <i class="fa fa-plus"> Tambah Data </i>
                    </button>
                </div>
                <div class="col-md">
                    <button onclick="window.print()" class="btn btn-outline-secondary shadow float-right mr-3 ml-2 mt-3">Print <i class="fa fa-print"></i> </button>
                    <a href="/bia2/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i class="fa fa-file-excel"></i></a>
                </div>
            </div>
            
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <!-- Table header -->
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>APLIKASI TI YANG DIGUNAKAN</th>
                        <th>RTO</th>
                        <th>DATA YANG DIHASILKAN</th>
                        <th>RPO</th>
                        <th>MTD</th>
                        <th>PEAKTIME</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <!-- Table body -->
                <tbody>
                    <?php if(empty($bia2)) { ?>
                        <tr>
                            <td scope="row"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php } else { ?>
                    <?php foreach ($bia2 as $row) : ?>
                        <tr>
                            <td scope="row"><?= $row['id']; ?></td>
                            <td><?= $row['appsti'] ?></td>
                            <td><?= $row['rto'] ?></td>
                            <td><?= $row['datahasil'] ?></td>
                            <td><?= $row['rpo'] ?></td>
                            <td><?= $row['mtd'] ?></td>
                            <td><?= $row['puncak'] ?></td>
                            <td>
                                <!-- Button to trigger confirmation modal -->
                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit" class="btn btn-sm btn-warning;" data-id="<?= $row['id']; ?>" data-appsti="<?= $row['appsti']; ?>"data-rto="<?= $row['rto']; ?>" data-datahasil="<?= $row['datahasil']; ?>" data-rpo="<?= $row['rpo']; ?>" data-mtd="<?= $row['mtd']; ?>" data-puncak="<?= $row['puncak']; ?>" ><i class="fa fa-edit"></i>&nbsp;</button>
                                <button type="button" data-toggle="modal" data-target="#modalHapus" id="btn-hapus" class="btn btn-sm btn-danger;" data-id="<?= $row['id']; ?>"> <i class="fa fa-trash-alt"></i>&nbsp;</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalHapus">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin hendak menghapus data ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapus">Yakin</button>
            </div>
        </div>
    </div>
</div>

</div>
<!-- End Page Content -->

<!--edit data-->
<?php if(!empty($bia2)) { ?>
    <div class="modal fade" id="modalUbah">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah<?= $judul;?> </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="<?= base_url('bia2/ubah'); ?>" method="post">
                    <input type="hidden" name="id" id="id-bia2">
                    <div class="form-group">
                    <label for="appsti">Input Aplikasi TI yang digunakan:</label>
                    <select name="appsti" id="appsti">
                        <?php foreach ($bisnis as $row) : ?>
                            <option value="<?= $row['apps']; ?>"><?= $row['apps']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="rto">RTO: </label>
                    <select name="rto" id="rto">
                        <option value="< 1 Jam">< 1 Jam</option>
                        <option value="< 4 Jam">< 4 Jam</option>
                        <option value="< 1 Hari">< 1 Hari</option>
                        <option value="< 7 Hari">< 7 Hari</option>
                        <option value="< 30 Hari">< 30 Hari</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="datahasil">Input Data yang dihasilkan:</label>
                    <select name="datahasil" id="datahasil">
                        <?php foreach ($bisnis as $row) : ?>
                            <option value="<?= $row['hasil']; ?>"><?= $row['hasil']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="rpo">RPO: </label>
                    <select name="rpo" id="rpo">
                        <option value="< 1 Jam">< 1 Jam</option>
                        <option value="< 4 Jam">< 4 Jam</option>
                        <option value="< 1 Hari">< 1 Hari</option>
                        <option value="< 7 Hari">< 7 Hari</option>
                        <option value="< 30 Hari">< 30 Hari</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mtd">MTD: </label>
                    <select name="mtd" id="mtd">
                        <option value="< 1 Jam">< 1 Jam</option>
                        <option value="< 4 Jam">< 4 Jam</option>
                        <option value="< 1 Hari">< 1 Hari</option>
                        <option value="< 7 Hari">< 7 Hari</option>
                        <option value="< 30 Hari">< 30 Hari</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="puncak">Input Peaktime:</label>
                    <input type="text" name="puncak" id="puncak" class="form-control">
                </div>

                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="ubah" class="btn btn-primary">Ubah Data</button>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<!-- Modal Tambah data bisnis -->
<!-- Add your modalTambah code here -->
<div class="modal fade" id="modalTambah">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah <?= $judul;?> </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('bia2/tambah'); ?>" method="post">
                <div class="form-group">
                    <label for="appsti">Input Aplikasi TI yang digunakan:</label>
                    <select name="appsti" id="appsti" class="form-control">
                        <?php foreach ($bisnis as $row) : ?>
                            <option value="<?= $row['apps']; ?>"><?= $row['apps']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>  
                <div class="form-group">
                    <label for="rto">RTO: </label>
                    <select name="rto" id="rto" class="form-control">
                        <option value="< 1 Jam">< 1 Jam</option>
                        <option value="< 4 Jam">< 4 Jam</option>
                        <option value="< 1 Hari">< 1 Hari</option>
                        <option value="< 7 Hari">< 7 Hari</option>
                        <option value="< 30 Hari">< 30 Hari</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="datahasil">Input Data yang dihasilkan:</label>
                    <select name="datahasil" id="datahasil" class="form-control">
                        <?php foreach ($bisnis as $row) : ?>
                            <option value="<?= $row['hasil']; ?>"><?= $row['hasil']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rpo">RPO: </label>
                    <select name="rpo" id="rpo" class="form-control">
                        <option value="< 1 Jam">< 1 Jam</option>
                        <option value="< 4 Jam">< 4 Jam</option>
                        <option value="< 1 Hari">< 1 Hari</option>
                        <option value="< 7 Hari">< 7 Hari</option>
                        <option value="< 30 Hari">< 30 Hari</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="mtd">MTD: </label>
                    <select name="mtd" id="mtd" class="form-control">
                        <option value="< 1 Jam">< 1 Jam</option>
                        <option value="< 4 Jam">< 4 Jam</option>
                        <option value="< 1 Hari">< 1 Hari</option>
                        <option value="< 7 Hari">< 7 Hari</option>
                        <option value="< 30 Hari">< 30 Hari</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="puncak">Input Peaktime:</label>
                    <input type="text" name="puncak" id="puncak" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Tambah Data</button>
                </div>
            
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus data bisnis -->
<div class="modal fade" id="modalHapus">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin hendak menghapus data ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapus">Yakin</button>
            </div>
        </div>
    </div>
</div>