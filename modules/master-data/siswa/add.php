<?php
/**
 * Form Tambah Siswa dengan Pembuatan Akun User Otomatis
 * File: modules/master-data/siswa/add.php
 */

// Debug mode - HAPUS INI SETELAH TESTING
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection (sudah ada session_start di dalamnya)
require_once '../../../core/config/database.php';

$page_title = 'Tambah Siswa';
$breadcrumb = [
    ['title' => 'Master Data', 'url' => '../index.php'],
    ['title' => 'Data Siswa', 'url' => 'index.php'],
    ['title' => 'Tambah Siswa']
];

// Handle form submission SEBELUM include header
if ($_POST) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Validation
        $errors = [];
        
        if (empty($_POST['nis'])) {
            $errors[] = 'NIS wajib diisi';
        } elseif (strlen($_POST['nis']) < 5) {
            $errors[] = 'NIS minimal 5 karakter';
        } else {
            // Check if NIS already exists
            $check_nis = getOne("SELECT id FROM siswa WHERE nis = ?", [$_POST['nis']]);
            if ($check_nis) {
                $errors[] = 'NIS sudah digunakan';
            }
            
            // Check if username (NIS) already exists in users table
            $check_username = getOne("SELECT id FROM users WHERE username = ?", [$_POST['nis']]);
            if ($check_username) {
                $errors[] = 'NIS sudah digunakan sebagai username';
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
        } else if (!empty($_POST['email'])) {
            // Check if email already exists
            $check_email = getOne("SELECT id FROM users WHERE email = ?", [$_POST['email']]);
            if ($check_email) {
                $errors[] = 'Email sudah digunakan';
            }
        }
        
        if (!empty($_POST['no_hp']) && !preg_match('/^[0-9+\-\s()]{10,15}$/', $_POST['no_hp'])) {
            $errors[] = 'Format nomor HP tidak valid';
        }
        
        // Password validation (optional, will be auto-generated if empty)
        $auto_password = '';
        if (empty($_POST['password'])) {
            // Generate default password: nama depan + NIS (4 digit terakhir)
            $nama_parts = explode(' ', trim($_POST['nama']));
            $nama_depan = strtolower($nama_parts[0]);
            $nis_suffix = substr($_POST['nis'], -4);
            $auto_password = $nama_depan . $nis_suffix;
        } else {
            if (strlen($_POST['password']) < 6) {
                $errors[] = 'Password minimal 6 karakter';
            }
            $auto_password = $_POST['password'];
        }
        
        if (empty($errors)) {
            // Handle custom kelas
            $kelas_value = trim($_POST['kelas']) ?: null;
            if (!empty($_POST['kelas_custom'])) {
                $kelas_value = trim($_POST['kelas_custom']);
            }
            
            // 1. Insert data siswa
            $siswa_data = [
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
                'kelas' => $kelas_value,
                'status' => $_POST['status'] ?? 'aktif'
            ];
            
            $columns = implode(', ', array_keys($siswa_data));
            $placeholders = ':' . implode(', :', array_keys($siswa_data));
            
            $sql_siswa = "INSERT INTO siswa ($columns) VALUES ($placeholders)";
            $result_siswa = execute($sql_siswa, $siswa_data);
            
            if (!$result_siswa) {
                throw new Exception('Gagal menyimpan data siswa');
            }
            
            // Get the inserted siswa ID
            $siswa_id = $pdo->lastInsertId();
            
            // 2. Create user account
            $user_data = [
                'username' => trim($_POST['nis']),
                'password' => password_hash($auto_password, PASSWORD_DEFAULT),
                'nama' => trim($_POST['nama']),
                'email' => trim($_POST['email']) ?: null,
                'role' => 'murid',
                'status' => 'active'
            ];
            
            $user_columns = implode(', ', array_keys($user_data));
            $user_placeholders = ':' . implode(', :', array_keys($user_data));
            
            $sql_user = "INSERT INTO users ($user_columns) VALUES ($user_placeholders)";
            $result_user = execute($sql_user, $user_data);
            
            if (!$result_user) {
                throw new Exception('Gagal membuat akun user');
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Set success message with login info
            $_SESSION['message'] = "Data siswa berhasil ditambahkan dan akun user telah dibuat!<br>
                                   <strong>Username:</strong> {$_POST['nis']}<br>
                                   <strong>Password:</strong> $auto_password<br>
                                   <small class='text-muted'>Siswa dapat login dengan kredensial di atas</small>";
            $_SESSION['message_type'] = 'success';
            
            // REDIRECT LANGSUNG TANPA OUTPUT APAPUN
            header('Location: index.php');
            exit();
            
        } else {
            // Rollback transaction if validation failed
            $pdo->rollBack();
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
        
        // Debug: tampilkan error detail
        error_log("Error in add.php: " . $e->getMessage());
    }
}

// Include SMS Core layout SETELAH proses form
include '../../../core/includes/header.php';

// Get available classes
try {
    $kelas_list = getAll("SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL ORDER BY kelas");
} catch (Exception $e) {
    $kelas_list = [];
}
?>

<!-- Custom CSS -->
<link rel="stylesheet" href="../../../core/assets/css/forms.css">

<!-- Remove preloader/loading animation -->
<style>
.preloader {
    display: none !important;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-plus mr-2"></i>Tambah Siswa Baru
                </h3>
                <div class="card-tools">
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            
            <form method="POST" action="" id="formTambahSiswa" name="formTambahSiswa">
                <div class="card-body">
                    <!-- Info Alert -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Informasi Pembuatan Akun:</h6>
                        <ul class="mb-0">
                            <li>Sistem akan otomatis membuat akun user untuk siswa</li>
                            <li><strong>Username:</strong> NIS siswa</li>
                            <li><strong>Password default:</strong> nama depan + 4 digit terakhir NIS</li>
                            <li><strong>Role:</strong> murid (dapat login ke sistem)</li>
                            <li>Password dapat diubah oleh siswa setelah login pertama</li>
                        </ul>
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
                                       value="<?= htmlspecialchars($_POST['nis'] ?? '') ?>"
                                       placeholder="Masukkan NIS"
                                       required>
                                <small class="form-text text-muted">
                                    <i class="fas fa-user"></i> NIS akan digunakan sebagai <strong>username</strong> untuk login
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nama" class="required">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control <?= !empty($errors) && in_array('Nama wajib diisi', $errors) ? 'is-invalid' : '' ?>" 
                                       id="nama" 
                                       name="nama" 
                                       value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                                       placeholder="Masukkan nama lengkap"
                                       required>
                                <small class="form-text text-muted">
                                    <i class="fas fa-key"></i> Nama depan akan digunakan untuk <strong>password default</strong>
                                </small>
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
                                    <option value="L" <?= ($_POST['jenis_kelamin'] ?? '') == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="P" <?= ($_POST['jenis_kelamin'] ?? '') == 'P' ? 'selected' : '' ?>>Perempuan</option>
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
                                       value="<?= htmlspecialchars($_POST['tempat_lahir'] ?? '') ?>"
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
                                       value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '') ?>">
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
                                            <option value="<?= $kelas['kelas'] ?>" <?= ($_POST['kelas'] ?? '') == $kelas['kelas'] ? 'selected' : '' ?>>
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
                                    <option value="aktif" <?= ($_POST['status'] ?? 'aktif') == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="nonaktif" <?= ($_POST['status'] ?? '') == 'nonaktif' ? 'selected' : '' ?>>Non-Aktif</option>
                                    <option value="lulus" <?= ($_POST['status'] ?? '') == 'lulus' ? 'selected' : '' ?>>Lulus</option>
                                    <option value="pindah" <?= ($_POST['status'] ?? '') == 'pindah' ? 'selected' : '' ?>>Pindah</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- User Account Section -->
                    <hr>
                    <h5><i class="fas fa-user-cog"></i> Pengaturan Akun User <small class="text-muted">(Opsional)</small></h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password Custom</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Kosongkan untuk password otomatis">
                                <small class="form-text text-muted">
                                    Jika kosong, password = nama depan + 4 digit terakhir NIS
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Preview Password Default</label>
                                <input type="text" 
                                       class="form-control bg-light" 
                                       id="password_preview" 
                                       placeholder="Password akan tampil otomatis" 
                                       readonly>
                                <small class="form-text text-muted">
                                    Preview password yang akan dibuat otomatis
                                </small>
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
                                  placeholder="Masukkan alamat lengkap"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="no_hp">No HP Siswa</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="no_hp" 
                                       name="no_hp" 
                                       value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>"
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
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       placeholder="nama@email.com">
                                <small class="form-text text-muted">
                                    <i class="fas fa-envelope"></i> Email akan digunakan untuk akun user (opsional)
                                </small>
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
                                       value="<?= htmlspecialchars($_POST['nama_ayah'] ?? '') ?>"
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
                                       value="<?= htmlspecialchars($_POST['nama_ibu'] ?? '') ?>"
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
                                       value="<?= htmlspecialchars($_POST['pekerjaan_ayah'] ?? '') ?>"
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
                                       value="<?= htmlspecialchars($_POST['pekerjaan_ibu'] ?? '') ?>"
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
                               value="<?= htmlspecialchars($_POST['no_hp_ortu'] ?? '') ?>"
                               placeholder="08xxxxxxxxx">
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-8">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" name="submitBtn">
                                <i class="fas fa-save"></i> Simpan Data Siswa & Buat Akun
                            </button>
                            <button type="reset" class="btn btn-secondary ml-2" id="resetBtn" name="resetBtn">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                        <div class="col-md-4 text-right">
                            <a href="index.php" class="btn btn-default">
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

// Auto generate password preview
function updatePasswordPreview() {
    const nama = document.getElementById('nama').value.trim();
    const nis = document.getElementById('nis').value.trim();
    
    if (nama && nis && nis.length >= 4) {
        const namaDepan = nama.split(' ')[0].toLowerCase();
        const nisSuffix = nis.slice(-4);
        const autoPassword = namaDepan + nisSuffix;
        
        document.getElementById('password_preview').value = autoPassword;
    } else {
        document.getElementById('password_preview').value = '';
    }
}

// Event listeners for auto password
document.getElementById('nama').addEventListener('input', updatePasswordPreview);
document.getElementById('nis').addEventListener('input', updatePasswordPreview);

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

// Form validation dan submit handling
document.getElementById('formTambahSiswa').addEventListener('submit', function(e) {
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
    
    // Disable submit button untuk prevent double submission
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    
    // Auto submit tanpa konfirmasi untuk menghindari loading
    return true;
});
</script>

<?php include '../../../core/includes/footer.php'; ?>