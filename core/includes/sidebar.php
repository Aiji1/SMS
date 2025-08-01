<?php
/**
 * Sidebar Component
 * File: core/includes/sidebar.php
 */

$user = getUser();
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_path = $_SERVER['REQUEST_URI'];
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?= $base_url ?>/core/index.php" class="brand-link">
        <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" 
             alt="SMS Logo" 
             class="brand-image img-circle elevation-3" 
             style="opacity: .8">
        <span class="brand-text font-weight-light">SMS School</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg" 
                     class="img-circle elevation-2" 
                     alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block"><?= $user['nama'] ?></a>
                <small class="text-light"><?= ucfirst(str_replace('_', ' ', $user['role'])) ?></small>
            </div>
        </div>

        <!-- Sidebar Search Form -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" 
                       type="search" 
                       placeholder="Search" 
                       aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?= $base_url ?>/core/index.php" 
                       class="nav-link <?= ($current_page == 'index' && strpos($current_path, '/core/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Master Data -->
                <?php if (hasRole(['admin', 'kepala_sekolah', 'wali_kelas', 'petugas'])): ?>
                <li class="nav-item <?= (strpos($current_path, '/master-data/') !== false) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= (strpos($current_path, '/master-data/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-database"></i>
                        <p>
                            Master Data
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= $base_url ?>/modules/master-data/" 
                               class="nav-link <?= (strpos($current_path, '/master-data/') !== false && $current_page == 'index') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dashboard Master Data</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $base_url ?>/modules/master-data/siswa/" 
                               class="nav-link <?= (strpos($current_path, '/siswa/') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Data Siswa</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $base_url ?>/modules/master-data/guru/" 
                               class="nav-link <?= (strpos($current_path, '/guru/') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Data Guru</p>
                                <span class="badge badge-info right">Soon</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $base_url ?>/modules/master-data/kelas/" 
                               class="nav-link <?= (strpos($current_path, '/kelas/') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Data Kelas</p>
                                <span class="badge badge-info right">Soon</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- Mata Pelajaran -->
                <?php if (hasRole(['admin', 'kepala_sekolah', 'guru'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/mapel/" 
                       class="nav-link <?= (strpos($current_path, '/mapel/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Mata Pelajaran</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Jurnal Mengajar -->
                <?php if (hasRole(['admin', 'kepala_sekolah', 'guru', 'wali_kelas'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/jurnal/" 
                       class="nav-link <?= (strpos($current_path, '/jurnal/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>Jurnal Mengajar</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Bank Materi -->
                <?php if (hasRole(['admin', 'guru'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/bank-materi/" 
                       class="nav-link <?= (strpos($current_path, '/bank-materi/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-folder-open"></i>
                        <p>Bank Materi</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Media Pembelajaran -->
                <?php if (hasRole(['admin', 'guru'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/media/" 
                       class="nav-link <?= (strpos($current_path, '/media/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-video"></i>
                        <p>Media Pembelajaran</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Tugas -->
                <?php if (hasRole(['admin', 'guru', 'wali_murid', 'murid'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/tugas/" 
                       class="nav-link <?= (strpos($current_path, '/tugas/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>Tugas</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- CBT -->
                <?php if (hasRole(['admin', 'guru', 'murid'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/cbt/" 
                       class="nav-link <?= (strpos($current_path, '/cbt/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-laptop"></i>
                        <p>CBT</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Tahfizh -->
                <?php if (hasRole(['admin', 'guru', 'wali_murid', 'murid'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/tahfizh/" 
                       class="nav-link <?= (strpos($current_path, '/tahfizh/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-quran"></i>
                        <p>Tahfizh</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Adab -->
                <?php if (hasRole(['admin', 'guru', 'wali_murid', 'murid'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/adab/" 
                       class="nav-link <?= (strpos($current_path, '/adab/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-heart"></i>
                        <p>Adab</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Tanse -->
                <?php if (hasRole(['admin', 'wali_kelas'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/tanse/" 
                       class="nav-link <?= (strpos($current_path, '/tanse/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-exclamation-triangle"></i>
                        <p>Tanse</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Keuangan -->
                <?php if (hasRole(['admin', 'kepala_sekolah', 'petugas'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/keuangan/" 
                       class="nav-link <?= (strpos($current_path, '/keuangan/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-money-bill-wave"></i>
                        <p>Keuangan</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Laporan -->
                <?php if (hasRole(['admin', 'kepala_sekolah', 'wali_kelas'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/laporan/" 
                       class="nav-link <?= (strpos($current_path, '/laporan/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Laporan</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Pengumuman -->
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/pengumuman/" 
                       class="nav-link <?= (strpos($current_path, '/pengumuman/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-bullhorn"></i>
                        <p>Pengumuman</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>

                <!-- Pengaturan -->
                <?php if (hasRole(['admin', 'kepala_sekolah'])): ?>
                <li class="nav-item">
                    <a href="<?= $base_url ?>/modules/pengaturan/" 
                       class="nav-link <?= (strpos($current_path, '/pengaturan/') !== false) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Pengaturan</p>
                        <span class="badge badge-info right">Soon</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Divider -->
                <li class="nav-header">DEVELOPMENT</li>

                <!-- API Documentation -->
                <li class="nav-item">
                    <a href="<?= $base_url ?>/core/api/" 
                       target="_blank" 
                       class="nav-link">
                        <i class="nav-icon fas fa-code"></i>
                        <p>
                            API Documentation
                            <i class="fas fa-external-link-alt right text-xs"></i>
                        </p>
                    </a>
                </li>

                <!-- System Info -->
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showSystemInfo()">
                        <i class="nav-icon fas fa-info-circle"></i>
                        <p>System Info</p>
                    </a>
                </li>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

<script>
// System Info Modal
function showSystemInfo() {
    alert(`SMS School Management System
Version: <?= $app_version ?>
PHP Version: <?= phpversion() ?>
Database: MySQL
User: <?= $user['nama'] ?> (<?= $user['role'] ?>)
Login Time: <?= date('d/m/Y H:i:s') ?>`);
}
</script>