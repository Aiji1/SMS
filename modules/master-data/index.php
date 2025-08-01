<?php
/**
 * Master Data Dashboard - Simple Working Version
 * File: modules/master-data/index.php
 */

// Set page variables
$page_title = 'Master Data Dashboard';
$breadcrumb = [
    ['title' => 'Master Data']
];

// Include header
include '../../core/includes/header.php';

// Get data with fallback
try {
    $total_siswa = getOne("SELECT COUNT(*) as total FROM siswa")['total'] ?? 0;
    $total_guru = getOne("SELECT COUNT(*) as total FROM guru")['total'] ?? 0;
    $recent_siswa = getAll("SELECT * FROM siswa ORDER BY created_at DESC LIMIT 5");
    $recent_guru = getAll("SELECT * FROM guru ORDER BY created_at DESC LIMIT 5");
} catch (Exception $e) {
    $total_siswa = 0;
    $total_guru = 0;
    $recent_siswa = [];
    $recent_guru = [];
}
?>

<!-- Custom CSS -->
<style>
.module-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
}

.quick-action-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.quick-action-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.quick-action-card .card-body {
    padding: 30px;
    text-align: center;
}

.quick-action-card .icon {
    font-size: 3rem;
    margin-bottom: 20px;
}

.stat-box {
    border: none;
    border-radius: 15px;
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin: 10px 0;
}

.btn-action {
    border-radius: 25px;
    padding: 10px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
</style>

<!-- Module Header -->
<div class="module-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-3">
                <i class="fas fa-database me-3"></i>
                Master Data Management
            </h1>
            <p class="mb-0 lead">Kelola semua data master sekolah secara terpusat dan efisien</p>
        </div>
        <div class="col-md-4 text-end">
            <i class="fas fa-database" style="font-size: 5rem; opacity: 0.2;"></i>
        </div>
    </div>
</div>

<!-- Statistics Overview -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-box text-center">
            <div class="text-primary">
                <i class="fas fa-users fa-3x"></i>
            </div>
            <div class="stat-number text-primary"><?= $total_siswa ?></div>
            <h6 class="text-muted">Total Siswa</h6>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-box text-center">
            <div class="text-success">
                <i class="fas fa-chalkboard-teacher fa-3x"></i>
            </div>
            <div class="stat-number text-success"><?= $total_guru ?></div>
            <h6 class="text-muted">Total Guru</h6>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-box text-center">
            <div class="text-warning">
                <i class="fas fa-door-open fa-3x"></i>
            </div>
            <div class="stat-number text-warning">12</div>
            <h6 class="text-muted">Total Kelas</h6>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-box text-center">
            <div class="text-info">
                <i class="fas fa-book fa-3x"></i>
            </div>
            <div class="stat-number text-info">8</div>
            <h6 class="text-muted">Mata Pelajaran</h6>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card quick-action-card h-100">
            <div class="card-body">
                <div class="icon text-primary">
                    <i class="fas fa-users"></i>
                </div>
                <h4>Data Siswa</h4>
                <p class="text-muted">Kelola data siswa, tambah siswa baru, edit informasi siswa</p>
                <a href="siswa/" class="btn btn-primary btn-action">
                    <i class="fas fa-arrow-right me-2"></i>Kelola Siswa
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card quick-action-card h-100">
            <div class="card-body">
                <div class="icon text-success">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h4>Data Guru</h4>
                <p class="text-muted">Kelola data guru, informasi mengajar, dan data kepegawaian</p>
                <button class="btn btn-success btn-action" onclick="alert('Modul Guru akan segera tersedia!')">
                    <i class="fas fa-clock me-2"></i>Coming Soon
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card quick-action-card h-100">
            <div class="card-body">
                <div class="icon text-warning">
                    <i class="fas fa-door-open"></i>
                </div>
                <h4>Data Kelas</h4>
                <p class="text-muted">Atur kelas, wali kelas, dan pembagian siswa per kelas</p>
                <button class="btn btn-warning btn-action" onclick="alert('Modul Kelas akan segera tersedia!')">
                    <i class="fas fa-clock me-2"></i>Coming Soon
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Recent Data -->
<div class="row">
    <!-- Recent Siswa -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Siswa Terbaru
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_siswa)): ?>
                    <?php foreach ($recent_siswa as $siswa): ?>
                    <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                        <div class="me-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?= htmlspecialchars($siswa['nama']) ?></h6>
                            <small class="text-muted">
                                NIS: <?= htmlspecialchars($siswa['nis']) ?> | 
                                Kelas: <?= htmlspecialchars($siswa['kelas'] ?? '-') ?>
                            </small>
                        </div>
                        <div>
                            <small class="text-muted">
                                <?= date('d/m/Y', strtotime($siswa['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-3">
                        <a href="siswa/" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>Lihat Semua
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <p>Belum ada data siswa</p>
                        <a href="siswa/" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Tambah Siswa Pertama
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Guru -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    Guru Terbaru
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_guru)): ?>
                    <?php foreach ($recent_guru as $guru): ?>
                    <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                        <div class="me-3">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?= htmlspecialchars($guru['nama']) ?></h6>
                            <small class="text-muted">
                                NIP: <?= htmlspecialchars($guru['nip'] ?? '-') ?> | 
                                Mapel: <?= htmlspecialchars($guru['mapel'] ?? '-') ?>
                            </small>
                        </div>
                        <div>
                            <small class="text-muted">
                                <?= date('d/m/Y', strtotime($guru['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-3">
                        <button class="btn btn-outline-success btn-sm" onclick="alert('Modul Guru coming soon!')">
                            <i class="fas fa-eye me-1"></i>Lihat Semua
                        </button>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chalkboard-teacher fa-3x mb-3"></i>
                        <p>Belum ada data guru</p>
                        <button class="btn btn-success btn-sm" onclick="alert('Modul Guru coming soon!')">
                            <i class="fas fa-plus me-1"></i>Tambah Guru Pertama
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="mb-3">Aksi Cepat</h5>
                <div class="btn-group-vertical btn-group-lg" role="group">
                    <a href="siswa/" class="btn btn-primary mb-2">
                        <i class="fas fa-plus me-2"></i>Tambah Siswa Baru
                    </a>
                    <button class="btn btn-success mb-2" onclick="alert('Modul Guru coming soon!')">
                        <i class="fas fa-plus me-2"></i>Tambah Guru Baru
                    </button>
                    <button class="btn btn-warning mb-2" onclick="alert('Modul Kelas coming soon!')">
                        <i class="fas fa-plus me-2"></i>Buat Kelas Baru
                    </button>
                    <a href="../../core/api/" target="_blank" class="btn btn-info">
                        <i class="fas fa-code me-2"></i>Lihat API Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../../core/includes/footer.php';
?>