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
                    <a href="/inv/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i class="fa fa-file-excel"></i></a>
                </div>
            </div>
            
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <!-- Table header -->
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>NAMA DATA</th>
                        <th>MEDIA PENYIMPANAN</th>
                        <th>LOKASI PENYIMPANAN</th>
                        <th>UNIT KERJA PENANGGUNG JAWAB</th>
                        <th>KETERANGAN</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <!-- Table body -->
                <tbody>
                    <?php if(empty($inv)) { ?>
                        <tr>
                            <td scope="row"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php } else { ?>
                    <?php foreach ($inv as $row) : ?>
                        <tr>
                            <td scope="row"><?= $row['id']; ?></td>
                            <td><?= $row['namadat'] ?></td>
                            <td><?= $row['media'] ?></td>
                            <td><?= $row['lokasi'] ?></td>
                            <td><?= $row['utgjawab'] ?></td>
                            <td><?= $row['keterangan'] ?></td>
                            <td>
                                <!-- Button to trigger confirmation modal -->
                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"class="btn btn-sm btn-warning" style="font-weight: 600;" data-id="<?= $row['id']; ?>" data-namadat="<?= $row['namadat']; ?>" data-media="<?= $row['media']; ?>" data-lokasi="<?= $row['lokasi']; ?>" data-utgjawab="<?= $row['utgjawab']; ?>" data-keterangan="<?= $row['keterangan']; ?>" ><i class="fa fa-edit"></i>&nbsp;Edit</button>
                                <button type="button" data-toggle="modal" data-target="#modalHapus" id="btn-hapus" class="btn btn-sm btn-danger" style="font-weight: 600;" data-id="<?= $row['id']; ?>"> <i class="fa fa-trash-alt"></i>&nbsp;Hapus</button>
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
<?php if(!empty($inv)) { ?>
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
                <form action="<?= base_url('inv/ubah'); ?>" method="post">
                <input type="hidden" name="id" id="id-inv">
                <div class="form-group">
                    <label for="namadat">Input Nama Data:</label>
                    <input type="text" name="namadat" id="namadat" class="form-control" value="<?= $row['namadat']?>">
                </div>
                <div class="form-group">
                    <label for="media">Input Media Penyimpanan: </label>
                    <input type="text" name="media" id="media" class="form-control" value="<?= $row['media']?>">
                </div>
                <div class="form-group">
                    <label for="lokasi">Input Lokasi Peyimpanan: </label>
                    <input type="text" name="lokasi" id="lokasi" class="form-control" value="<?= $row['lokasi']?>">
                </div>
                <div class="form-group">
                    <label for="utgjawab">Input Unit Kerja Penanggung Jawab: </label>
                    <input type="text" name="utgjawab" id="utgjawab" class="form-control" value="<?= $row['utgjawab']?>">
                </div>
                <div class="form-group">
                    <label for="keterangan">Input keterangan: </label>
                    <input type="text" name="keterangan" id="keterangan" class="form-control" value="<?= $row['keterangan']?>">
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
                <form action="<?= base_url('inv/tambahI'); ?>" method="post">
                <div class="form-group">
                    <label for="inv">Input Nama Data:</label>
                    <input type="text" name="namadat" id="namadat" class="form-control">
                </div>
                <div class="form-group">
                    <label for="media">Input Media Penyimpanan: </label>
                    <input type="text" name="media" id="media" class="form-control">
                </div>
                <div class="form-group">
                    <label for="lokasi">Input Lokasi Penyimpanan: </label>
                    <input type="text" name="lokasi" id="lokasi" class="form-control">
                </div>
                <div class="form-group">
                    <label for="utgjawab">Input Unit Kerja Penanggung Jawab: </label>
                    <input type="text" name="utgjawab" id="utgjawab" class="form-control">
                </div>
                <div class="form-group">
                    <label for="keterangan">Input Keterangan: </label>
                    <input type="text" name="keterangan" id="keterangan" class="form-control">
                </div>
                
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" name="tambahI" class="btn btn-primary">Tambah Data</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus data bisnis -->
<div class="modal fade" id="modalHapusI">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                Apakah anda yakin ingin menghapus data no-<span id="idDataI"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnHapusI">Yakin</button>
            </div>
        </div>
    </div>
</div>