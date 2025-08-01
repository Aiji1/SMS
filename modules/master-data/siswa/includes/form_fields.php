<?php
/**
 * Reusable Form Fields untuk Siswa
 * File: modules/master-data/siswa/includes/form_fields.php
 */

/**
 * Generate NIS input field
 */
function renderNISField($value = '', $errors = []) {
    $has_error = !empty($errors) && (in_array('NIS wajib diisi', $errors) || in_array('NIS sudah digunakan', $errors));
    ?>
    <div class="form-group">
        <label for="nis" class="required">NIS <span class="text-danger">*</span></label>
        <input type="text" 
               class="form-control <?= $has_error ? 'is-invalid' : '' ?>" 
               id="nis" 
               name="nis" 
               value="<?= htmlspecialchars($value) ?>"
               placeholder="Masukkan NIS"
               required>
        <small class="form-text text-muted">Nomor Induk Siswa (minimal 5 karakter)</small>
        <?php if ($has_error): ?>
            <div class="invalid-feedback">
                <?= array_filter($errors, fn($e) => strpos($e, 'NIS') !== false)[0] ?? 'NIS tidak valid' ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Generate Nama input field
 */
function renderNamaField($value = '', $errors = []) {
    $has_error = !empty($errors) && in_array('Nama wajib diisi', $errors);
    ?>
    <div class="form-group">
        <label for="nama" class="required">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" 
               class="form-control <?= $has_error ? 'is-invalid' : '' ?>" 
               id="nama" 
               name="nama" 
               value="<?= htmlspecialchars($value) ?>"
               placeholder="Masukkan nama lengkap"
               required>
        <?php if ($has_error): ?>
            <div class="invalid-feedback">Nama wajib diisi minimal 2 karakter</div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Generate Jenis Kelamin select field
 */
function renderJenisKelaminField($value = '', $errors = []) {
    $has_error = !empty($errors) && in_array('Jenis kelamin wajib dipilih', $errors);
    ?>
    <div class="form-group">
        <label for="jenis_kelamin" class="required">Jenis Kelamin <span class="text-danger">*</span></label>
        <select class="form-control <?= $has_error ? 'is-invalid' : '' ?>" 
                id="jenis_kelamin" 
                name="jenis_kelamin" 
                required>
            <option value="">Pilih Jenis Kelamin</option>
            <option value="L" <?= $value == 'L' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="P" <?= $value == 'P' ? 'selected' : '' ?>>Perempuan</option>
        </select>
        <?php if ($has_error): ?>
            <div class="invalid-feedback">Jenis kelamin wajib dipilih</div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Generate Kelas select field with custom option
 */
function renderKelasField($value = '', $kelas_list = []) {
    ?>
    <div class="form-group">
        <label for="kelas">Kelas</label>
        <div class="input-group">
            <select class="form-control" id="kelas" name="kelas">
                <option value="">Pilih Kelas</option>
                <?php foreach ($kelas_list as $kelas): ?>
                    <option value="<?= $kelas['kelas'] ?>" <?= $value == $kelas['kelas'] ? 'selected' : '' ?>>
                        <?= $kelas['kelas'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" onclick="toggleKelasCustom()" title="Tambah kelas baru">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <input type="text" 
               class="form-control mt-2" 
               id="kelas_custom" 
               name="kelas_custom" 
               placeholder="Atau masukkan kelas baru"
               style="display: none;">
        <small class="form-text text-muted">Pilih dari daftar atau tambah kelas baru</small>
    </div>
    <?php
}

/**
 * Generate Status select field
 */
function renderStatusField($value = 'aktif') {
    ?>
    <div class="form-group">
        <label for="status">Status</label>
        <select class="form-control" id="status" name="status">
            <option value="aktif" <?= $value == 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= $value == 'nonaktif' ? 'selected' : '' ?>>Non-Aktif</option>
            <option value="lulus" <?= $value == 'lulus' ? 'selected' : '' ?>>Lulus</option>
            <option value="pindah" <?= $value == 'pindah' ? 'selected' : '' ?>>Pindah</option>
        </select>
    </div>
    <?php
}

/**
 * Generate Tempat & Tanggal Lahir fields
 */
function renderTempTglLahirFields($tempat_lahir = '', $tanggal_lahir = '') {
    ?>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="tempat_lahir">Tempat Lahir</label>
                <input type="text" 
                       class="form-control" 
                       id="tempat_lahir" 
                       name="tempat_lahir" 
                       value="<?= htmlspecialchars($tempat_lahir) ?>"
                       placeholder="Tempat lahir">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="tanggal_lahir">Tanggal Lahir</label>
                <input type="date" 
                       class="form-control" 
                       id="tanggal_lahir" 
                       name="tanggal_lahir" 
                       value="<?= htmlspecialchars($tanggal_lahir) ?>">
            </div>
        </div>
    </div>
    <?php
}

/**
 * Generate Contact fields (Alamat, HP, Email)
 */
function renderContactFields($alamat = '', $no_hp = '', $email = '') {
    ?>
    <div class="form-group">
        <label for="alamat">Alamat</label>
        <textarea class="form-control" 
                  id="alamat" 
                  name="alamat" 
                  rows="3" 
                  placeholder="Masukkan alamat lengkap"><?= htmlspecialchars($alamat) ?></textarea>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="no_hp">No HP Siswa</label>
                <input type="text" 
                       class="form-control" 
                       id="no_hp" 
                       name="no_hp" 
                       value="<?= htmlspecialchars($no_hp) ?>"
                       placeholder="08xxxxxxxxx">
                <small class="form-text text-muted">Format: 08xxxxxxxxx</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       value="<?= htmlspecialchars($email) ?>"
                       placeholder="nama@email.com">
            </div>
        </div>
    </div>
    <?php
}

/**
 * Generate Parent fields (Data Orang Tua)
 */
function renderParentFields($nama_ayah = '', $nama_ibu = '', $pekerjaan_ayah = '', $pekerjaan_ibu = '', $no_hp_ortu = '') {
    ?>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="nama_ayah">Nama Ayah</label>
                <input type="text" 
                       class="form-control" 
                       id="nama_ayah" 
                       name="nama_ayah" 
                       value="<?= htmlspecialchars($nama_ayah) ?>"
                       placeholder="Nama ayah">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="nama_ibu">Nama Ibu</label>
                <input type="text" 
                       class="form-control" 
                       id="nama_ibu" 
                       name="nama_ibu" 
                       value="<?= htmlspecialchars($nama_ibu) ?>"
                       placeholder="Nama ibu">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="pekerjaan_ayah">Pekerjaan Ayah</label>
                <input type="text" 
                       class="form-control" 
                       id="pekerjaan_ayah" 
                       name="pekerjaan_ayah" 
                       value="<?= htmlspecialchars($pekerjaan_ayah) ?>"
                       placeholder="Pekerjaan ayah">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="pekerjaan_ibu">Pekerjaan Ibu</label>
                <input type="text" 
                       class="form-control" 
                       id="pekerjaan_ibu" 
                       name="pekerjaan_ibu" 
                       value="<?= htmlspecialchars($pekerjaan_ibu) ?>"
                       placeholder="Pekerjaan ibu">
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="no_hp_ortu">No HP Orang Tua</label>
        <input type="text" 
               class="form-control" 
               id="no_hp_ortu" 
               name="no_hp_ortu" 
               value="<?= htmlspecialchars($no_hp_ortu) ?>"
               placeholder="08xxxxxxxxx">
    </div>
    <?php
}

/**
 * Generate complete student form
 */
function renderStudentForm($data = [], $errors = [], $kelas_list = []) {
    ?>
    <!-- Data Utama -->
    <div class="row">
        <div class="col-md-6">
            <?php renderNISField($data['nis'] ?? '', $errors); ?>
        </div>
        <div class="col-md-6">
            <?php renderNamaField($data['nama'] ?? '', $errors); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <?php renderJenisKelaminField($data['jenis_kelamin'] ?? '', $errors); ?>
        </div>
        <div class="col-md-8">
            <?php renderTempTglLahirFields($data['tempat_lahir'] ?? '', $data['tanggal_lahir'] ?? ''); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?php renderKelasField($data['kelas'] ?? '', $kelas_list); ?>
        </div>
        <div class="col-md-6">
            <?php renderStatusField($data['status'] ?? 'aktif'); ?>
        </div>
    </div>

    <!-- Kontak -->
    <hr>
    <h5><i class="fas fa-address-book"></i> Informasi Kontak</h5>
    <?php renderContactFields($data['alamat'] ?? '', $data['no_hp'] ?? '', $data['email'] ?? ''); ?>

    <!-- Data Orang Tua -->
    <hr>
    <h5><i class="fas fa-users"></i> Data Orang Tua</h5>
    <?php renderParentFields(
        $data['nama_ayah'] ?? '', 
        $data['nama_ibu'] ?? '', 
        $data['pekerjaan_ayah'] ?? '', 
        $data['pekerjaan_ibu'] ?? '', 
        $data['no_hp_ortu'] ?? ''
    ); ?>
    
    <script>
    // Kelas custom toggle function
    function toggleKelasCustom() {
        const select = document.getElementById('kelas');
        const custom = document.getElementById('kelas_custom');
        
        if (custom.style.display === 'none') {
            custom.style.display = 'block';
            custom.focus();
            select.value = '';
        } else {
            custom.style.display = 'none';
            custom.value = '';
        }
    }

    // Handle custom class input
    document.getElementById('kelas_custom').addEventListener('input', function() {
        if (this.value) {
            document.getElementById('kelas').value = '';
        }
    });

    document.getElementById('kelas').addEventListener('change', function() {
        if (this.value) {
            document.getElementById('kelas_custom').value = '';
        }
    });
    </script>
    <?php
}
?>