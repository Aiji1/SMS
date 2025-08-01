<?php
/**
 * Form Edit Siswa
 * File: modules/master-data/siswa/edit.php
 */

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'ID siswa tidak valid';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    // Get existing data
    $siswa = getOne("SELECT * FROM siswa WHERE id = ?", [$id]);
    
    if (!$siswa) {
        $_SESSION['message'] = 'Data siswa tidak ditemukan';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$page_title = 'Edit Siswa - ' . $siswa['nama'];
$breadcrumb = [
    ['title' => 'Master Data', 'url' => '../index.php'],
    ['title' => 'Data Siswa', 'url' => 'index.php'],
    ['title' => 'Edit Siswa']
];

// Include SMS Core layout
include '../../../core/includes/header.php';

// Handle form submission
if ($_POST) {
    try {
        // Validation
        $errors = [];
        
        if (empty($_POST['nis'])) {
            $errors[] = 'NIS wajib diisi';
        } elseif (strlen($_POST['nis']) < 5) {
            $errors[] = 'NIS minimal 5 karakter';
        } else {
            // Check if NIS already exists (exclude current record)
            $check_nis = getOne("SELECT id FROM siswa WHERE nis = ? AND id != ?", [$_POST['nis'], $id]);
            if ($check_nis) {
                $errors[] = 'NIS sudah digunakan oleh siswa lain';
            }
        }
        
        if (empty($_POST['nama'])) {
            $errors[] = 'Nama wajib diisi';
        } elseif (strlen($_POST['nama']) < 2) {
            $errors[] = 'Nama minimal 2 karakter';
        }
        
        if (empty($_POST['jenis_kelamin'])) {
            $errors[] = 'Jenis kelamin wajib dipilih';
        }
        
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        }
        
        if (!empty($_POST['no_hp']) && !preg_match('/^[0-9+\-\s()]{10,15}$/', $_POST['no_hp'])) {
            $errors[] = 'Format nomor HP tidak valid';
        }
        
        if (empty($errors)) {
            $data = [
                'nis' => trim($_POST['nis']),
                'nama' => trim($_POST['nama']),
                'jenis_kelamin' => $_POST['jenis_kelamin'],
                'tempat_lahir' => trim($_POST['tempat_lahir']) ?: null,
                'tanggal_lahir' => $_POST['tanggal_lahir'] ?: null,
                'alamat' => trim($_POST['alamat']) ?: null,
                'no_hp' => trim($_POST['no_hp']) ?: null,
                'email' => trim($_POST['email']) ?: null,
                'nama_ayah' => trim($_POST['nama_ayah']) ?: null,
                'nama_ibu' => trim($_POST['nama_ibu']) ?: null,
                'pekerjaan_ayah' => trim($_POST['pekerjaan_ayah']) ?: null,
                'pekerjaan_ibu' => trim($_POST['pekerjaan_ibu']) ?: null,
                'no_hp_ortu' => trim($_POST['no_hp_ortu']) ?: null,
                'kelas' => trim($_POST['kelas']) ?: null,
                'status' => $_POST['status'] ?? 'aktif',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $set_clause = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($data)));
            $data['id'] = $id;
            
            $sql = "UPDATE siswa SET $set_clause WHERE id = :id";
            $result = execute($sql, $data);
            
            if ($result) {
                $_SESSION['message'] = 'Data siswa berhasil diperbarui';
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                throw new Exception('Gagal memperbarui data');
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Use POST data if available, otherwise use existing data
$data = $_POST ?: $siswa;

// Get available classes
$kelas_list = getAll("SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL ORDER BY kelas");
?>

<!-- Custom CSS -->
<link rel="stylesheet" href="../../../core/assets/css/forms.css">

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-edit mr-2"></i>Edit Data Siswa
                </h3>
                <div class="card-tools">
                    <a href="view.php?id=<?= $id ?>" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            
            <form method="POST" action="" id="formEditSiswa">
                <div class="card-body">
                    <!-- Current Data Info -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Informasi Saat Ini:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>NIS:</strong> <?= htmlspecialchars($siswa['nis']) ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Nama:</strong> <?= htmlspecialchars($siswa['nama']) ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Kelas:</strong> <?= htmlspecialchars($siswa['kelas'] ?: 'Belum ditentukan') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Terdapat kesalahan:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Data Utama -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nis" class="required">NIS <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control <?= !empty($errors) && in_array('NIS wajib diisi', $errors) ? 'is-invalid' : '' ?>" 
                                       id="nis" 
                                       name="nis" 
                                       value="<?= htmlspecialchars($data['nis']) ?>"
                                       placeholder="Masukkan NIS"
                                       required>
                                <small class="form-text text-muted">Nomor Induk Siswa (minimal 5 karakter)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nama" class="required">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control <?= !empty($errors) && in_array('Nama wajib diisi', $errors) ? 'is-invalid' : '' ?>" 
                                       id="nama" 
                                       name="nama" 
                                       value="<?= htmlspecialchars($data['nama']) ?>"
                                       placeholder="Masukkan nama lengkap"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="jenis_kelamin" class="required">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select class="form-control <?= !empty($errors) && in_array('Jenis kelamin wajib dipilih', $errors) ? 'is-invalid' : '' ?>" 
                                        id="jenis_kelamin" 
                                        name="jenis_kelamin" 
                                        required>
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="L" <?= $data['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="P" <?= $data['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tempat_lahir">Tempat Lahir</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="tempat_lahir" 
                                       name="tempat_lahir" 
                                       value="<?= htmlspecialchars($data['tempat_lahir'] ?? '') ?>"
                                       placeholder="Tempat lahir">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tanggal_lahir">Tanggal Lahir</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="tanggal_lahir" 
                                       name="tanggal_lahir" 
                                       value="<?= htmlspecialchars($data['tanggal_lahir'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="kelas">Kelas</label>
                                <div class="input-group">
                                    <select class="form-control" id="kelas" name="kelas">
                                        <option value="">Pilih Kelas</option>
                                        <?php foreach ($kelas_list as $kelas): ?>
                                            <option value="<?= $kelas['kelas'] ?>" <?= $data['kelas'] == $kelas['kelas'] ? 'selected' : '' ?>>
                                                <?= $kelas['kelas'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="toggleKelasCustom()">
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
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : '' ?>>Non-Aktif</option>
                                    <option value="lulus" <?= $data['status'] == 'lulus' ? 'selected' : '' ?>>Lulus</option>
                                    <option value="pindah" <?= $data['status'] == 'pindah' ? 'selected' : '' ?>>Pindah</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Kontak -->
                    <hr>
                    <h5><i class="fas fa-address-book"></i> Informasi Kontak</h5>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea class="form-control" 
                                  id="alamat" 
                                  name="alamat" 
                                  rows="3" 
                                  placeholder="Masukkan alamat lengkap"><?= htmlspecialchars($data['alamat'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="no_hp">No HP Siswa</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="no_hp" 
                                       name="no_hp" 
                                       value="<?= htmlspecialchars($data['no_hp'] ?? '') ?>"
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
                                       value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                                       placeholder="nama@email.com">
                            </div>
                        </div>
                    </div>

                    <!-- Data Orang Tua -->
                    <hr>
                    <h5><i class="fas fa-users"></i> Data Orang Tua</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nama_ayah">Nama Ayah</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nama_ayah" 
                                       name="nama_ayah" 
                                       value="<?= htmlspecialchars($data['nama_ayah'] ?? '') ?>"
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
                                       value="<?= htmlspecialchars($data['nama_ibu'] ?? '') ?>"
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
                                       value="<?= htmlspecialchars($data['pekerjaan_ayah'] ?? '') ?>"
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
                                       value="<?= htmlspecialchars($data['pekerjaan_ibu'] ?? '') ?>"
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
                               value="<?= htmlspecialchars($data['no_hp_ortu'] ?? '') ?>"
                               placeholder="08xxxxxxxxx">
                    </div>

                    <!-- Log Activity -->
                    <hr>
                    <h5><i class="fas fa-history"></i> Informasi Log</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Dibuat Pada</label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?= $siswa['created_at'] ? date('d/m/Y H:i', strtotime($siswa['created_at'])) : '-' ?>" 
                                       readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Terakhir Diubah</label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?= $siswa['updated_at'] ? date('d/m/Y H:i', strtotime($siswa['updated_at'])) : 'Belum pernah diubah' ?>" 
                                       readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <button type="reset" class="btn btn-secondary ml-2" onclick="resetForm()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="view.php?id=<?= $id ?>" class="btn btn-info">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </a>
                            <a href="index.php" class="btn btn-default ml-2">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store original data for reset functionality
const originalData = <?= json_encode($siswa) ?>;

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

// Reset form to original data
function resetForm() {
    // Reset to original values
    for (const [key, value] of Object.entries(originalData)) {
        const element = document.getElementById(key);
        if (element) {
            element.value = value || '';
        }
    }
    
    // Hide custom kelas input
    document.getElementById('kelas_custom').style.display = 'none';
    document.getElementById('kelas_custom').value = '';
}

// Form validation
document.getElementById('formEditSiswa').addEventListener('submit', function(e) {
    const nis = document.getElementById('nis').value.trim();
    const nama = document.getElementById('nama').value.trim();
    const jk = document.getElementById('jenis_kelamin').value;
    
    if (!nis || nis.length < 5) {
        alert('NIS wajib diisi minimal 5 karakter!');
        e.preventDefault();
        return;
    }
    
    if (!nama || nama.length < 2) {
        alert('Nama wajib diisi minimal 2 karakter!');
        e.preventDefault();
        return;
    }
    
    if (!jk) {
        alert('Jenis kelamin wajib dipilih!');
        e.preventDefault();
        return;
    }
    
    // Handle custom class
    const kelasSelect = document.getElementById('kelas').value;
    const kelasCustom = document.getElementById('kelas_custom').value.trim();
    
    if (kelasCustom) {
        document.getElementById('kelas').innerHTML += `<option value="${kelasCustom}" selected>${kelasCustom}</option>`;
    }
    
    // Confirm before submit
    if (confirm('Apakah Anda yakin ingin menyimpan perubahan data siswa ini?')) {
        return true;
    } else {
        e.preventDefault();
    }
});
</script>

<?php include '../../../core/includes/footer.php'; ?>