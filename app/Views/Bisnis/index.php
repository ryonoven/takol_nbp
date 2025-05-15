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
                    <a href="/bisnis/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i class="fa fa-file-excel"></i></a>
                </div>
            </div>
            
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <!-- Table header -->
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>SUB ORDINAT</th>
                        <th>FORM BISNIS</th>
                        <th>KETERKAITAN DENGAN UNIT KERJA LAIN</th>
                        <th>KETERKAITAN DENGAN PIHAK KETIGA</th>
                        <th>DATA YANG DIHASILKAN</th>
                        <th>APLIKASI YANG DIGUNAKAN</th>
                        <th>ACTION</th>
                    </tr>
                </thead>

                <!-- Table body -->
                <tbody>
                    <?php if(empty($bisnis)) { ?>
                        <tr>
                            <td scope="row"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php } else { ?>
                    <?php foreach ($bisnis as $row) : ?>
                        <tr>
                            <td scope="row"><?= $row['id']; ?></td>
                            <td><?= $row['ordinat'] ?></td>
                            <td><?= $row['bis'] ?></td>
                            <td><?= $row['lain'] ?></td>
                            <td><?= $row['ketiga'] ?></td>
                            <td><?= $row['hasil'] ?></td>
                            <td><?= $row['apps'] ?></td>
                            <td>
                                <!-- Button to trigger confirmation modal -->
                                <div class="d-flex">
                                    <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit" class="btn btn-sm btn-warning;">
                                        <i class="fa fa-edit"></i>&nbsp;
                                    </button>
                                    <button type="button" data-toggle="modal" data-target="#modalHapus" id="btn-hapus" class="btn btn-sm btn-danger;">
                                        <i class="fa fa-trash-alt"></i>&nbsp;
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<!-- End Page Content -->

<!--edit data-->
<?php if(!empty($bisnis)) { ?>
<div class="modal fade" id="modalUbah">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah <?= $judul;?> </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('bisnis/ubah'); ?>" method="post">
                <input type="hidden" name="id" id="id-bisnis">
                <div class="form-group">
                    <label for="ordinat">Input Sub Ordinat:</label>
                    <input type="text" name="ordinat" id="ordinat" class="form-control" value="<?= $row['ordinat']?>">
                </div>
                <div class="form-group">
                    <label for="bis">Input Form Bisnis:</label>
                    <input type="text" name="bis" id="bis" class="form-control" value="<?= $row['bis']?>">
                </div>
                <div class="form-group">
                    <label for="lain">Input Keterkaitan dengan unit kerja lain: </label>
                    <input type="text" name="lain" id="lain" class="form-control" value="<?= $row['lain']?>">
                </div>
                <div class="form-group">
                    <label for="ketiga">Input keterkaitan dengan pihak ketiga: </label>
                    <input type="text" name="ketiga" id="ketiga" class="form-control" value="<?= $row['ketiga']?>">
                </div>
                <div class="form-group">
                    <label for="hasil">Input data yang dihasilkan: </label>
                    <input type="text" name="hasil" id="hasil" class="form-control" value="<?= $row['hasil']?>">
                </div>
                <div class="form-group">
                    <label for="apps">Input aplikasi yang digunakan: </label>
                    <input type="text" name="apps" id="apps" class="form-control" value="<?= $row['apps']?>">
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
                <form action="<?= base_url('bisnis/tambah'); ?>" method="post">
                <div class="form-group">
                    <label for="ordinat">Input Sub Ordinat:</label>
                    <input type="text" name="ordinat" id="ordinat" class="form-control">
                </div>
                <div class="form-group">
                    <label for="bis">Input Form Bisnis:</label>
                    <input type="text" name="bis" id="bis" class="form-control">
                </div>
                <div class="form-group">
                    <label for="lain">Input Keterkaitan dengan unit kerja lain: </label>
                    <input type="text" name="lain" id="lain" class="form-control">
                </div>
                <div class="form-group">
                    <label for="ketiga">Input keterkaitan dengan pihak ketiga: </label>
                    <input type="text" name="ketiga" id="ketiga" class="form-control">
                </div>
                <div class="form-group">
                    <label for="hasil">Input data yang dihasilkan: </label>
                    <input type="text" name="hasil" id="hasil" class="form-control">
                </div>
                <div class="form-group">
                    <label for="apps">Input aplikasi yang digunakan: </label>
                    <input type="text" name="apps" id="apps" class="form-control">
                </div>
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