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
                    <a href="/bia/excel" class="btn btn-outline-success shadow float-right mt-3">Excel <i class="fa fa-file-excel"></i></a>
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
                        <th>PROSES BISNIS</th>
                        <th>Kredit</th>
                        <th>Pasar</th>
                        <th>Likuiditas</th>
                        <th>Operasional</th>
                        <th>Kepatuhan</th>
                        <th>Hukum</th>
                        <th>Reputasi</th>
                        <th>Strategi</th>
                        <th>Total</th>
                        <th>Action</th>
                        <th>Action</th>                        
                        <th>Action</th>                         
                    </tr>
                </thead>
                <!-- Table body -->
                <tbody>
                    <?php if(empty($bia)) { ?>
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
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php } else { ?>
                    <?php foreach ($bia as $row) : ?>
                        <tr>
                            <td scope="row"><?= $row['id']; ?></td>
                            <td><?= $row['sub_ordinat'] ?></td>
                            <td><?= $row['proses_bisnis'] ?></td>
                            <td><?= $row['kredit'] ?></td>
                            <td><?= $row['pasar'] ?></td>
                            <td><?= $row['liquiditas'] ?></td>
                            <td><?= $row['operasional'] ?></td>
                            <td><?= $row['kepatuhan'] ?></td>
                            <td><?= $row['hukum'] ?></td>
                            <td><?= $row['reputasi'] ?></td>
                            <td><?= $row['strategi'] ?></td>
                            <td><?= $row['total'] ?></td>
                            <td>
                                <!-- Button to trigger confirmation modal -->
                                <button type="button" data-toggle="modal" data-target="#modalUbah" id="btn-edit"class="btn btn-sm btn-warning" style="font-weight: 600;" data-id="<?= $row['id']; ?>" data-subordinat="<?= $row['sub_ordinat']; ?>"data-prosesbisnis="<?= $row['proses_bisnis']; ?>" data-kredit="<?= $row['kredit']; ?>" data-pasar="<?= $row['pasar']; ?>" data-liquiditas="<?= $row['liquiditas']; ?>" data-operasional="<?= $row['operasional']; ?>" data-kepatuhan="<?= $row['kepatuhan']; ?>" data-hukum="<?= $row['hukum']; ?>" data-reputasi="<?= $row['reputasi']; ?>" data-strategi="<?= $row['strategi']; ?>" ><i class="fa fa-edit"></i>&nbsp;Edit</button>
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
<?php if(!empty($bia)) { ?>
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
                <form action="<?= base_url('bia/ubah'); ?>" method="post">
                <input type="hidden" name="id" id="id-bia">
                <div class="form-group">
                    <label for="subordinat">Input Sub Ordinat:</label>
                    <select name="sub_ordinat" id="subordinat">
                        <?php foreach ($bisnis as $row) : ?>
                            <option value="<?= $row['ordinat']; ?>"><?= $row['ordinat']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="proses">Input Form Bisnis:</label>
                    <select name="proses_bisnis" id="proses_bisnis">
                        <?php foreach ($bisnis as $row) : ?>
                            <option value="<?= $row['bis']; ?>"><?= $row['bis']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="kredit">Input Kredit: </label>
                    <select name="kredit" id="kredit">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pasar">Input Pasar: </label>
                    <select name="pasar" id="pasar">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="liquiditas">Input Likuiditas: </label>
                    <select name="liquiditas" id="liquiditas">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="operasional">Input Operasional: </label>
                    <select name="operasional" id="operasional">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="kepatuhan">Input Kepatuhan: </label>
                    <select name="kepatuhan" id="kepatuhan">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="hukum">Input Hukum: </label>
                    <select name="hukum" id="hukum">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reputasi">Input Reputasi: </label>
                    <select name="reputasi" id="reputasi">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="strategi">Input Strategi: </label>
                    <select name="strategi" id="strategi">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                    </select>
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

<!-- Modal Tambah data bia -->
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
                <form action="<?= base_url('bia/tambah'); ?>" method="post">
                <div class="form-group">
                    <label for="sub_ordinat">Input Sub Ordinat:</label>
                    <select name="sub_ordinat" id="sub_ordinat">
                        <?php foreach ($bisnis as $row) : ?>
                            <option value="<?= $row['ordinat']; ?>"><?= $row['ordinat']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="proses_bisnis">Input Proses Bisnis: </label>
                    <select name="proses_bisnis" id="proses_bisnis">
                        <?php foreach ($bisnis as $row) : ?>
                            <option value="<?= $row['bis']; ?>"><?= $row['bis']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="kredit">Input Kredit: </label>
                    <select name="kredit" id="kredit">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pasar">Input Pasar: </label>
                    <select name="pasar" id="pasar">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="likuiditas">Input Likuiditas: </label>
                    <select name="likuiditas" id="likuiditas">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="operasional">Input Operasional: </label>
                    <select name="operasional" id="operasional">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="kepatuhan">Input Kepatuhan: </label>
                    <select name="kepatuhan" id="kepatuhan">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="hukum">Input Hukum: </label>
                    <select name="hukum" id="hukum">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reputasi">Input Reputasi: </label>
                    <select name="reputasi" id="reputasi">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="strategi">Input Strategi: </label>
                    <select name="strategi" id="strategi">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
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
                <button type="button" class="btn btn-primary" id="btnHapusB">Yakin</button>
            </div>
        </div>
    </div>
</div>