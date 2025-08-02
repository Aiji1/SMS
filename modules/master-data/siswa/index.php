<?php
/**
 * Data Siswa - List & Display
 * File: modules/master-data/siswa/index.php
 */

$page_title = 'Data Siswa';
$breadcrumb = [
    ['title' => 'Master Data', 'url' => '../index.php'],
    ['title' => 'Data Siswa']
];

// Include SMS Core layout
include '../../../core/includes/header.php';

// Get parameters
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$kelas_filter = $_GET['kelas'] ?? '';

// Handle success/error messages from other pages
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

try {
    // Build query
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(nama LIKE ? OR nis LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($kelas_filter) {
        $where_conditions[] = "kelas = ?";
        $params[] = $kelas_filter;
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get total count
    $total_query = "SELECT COUNT(*) as total FROM siswa $where_clause";
    $total_result = getOne($total_query, $params);
    $total = $total_result ? $total_result['total'] : 0;
    
    // Get data
    $data_query = "SELECT * FROM siswa $where_clause ORDER BY nama ASC LIMIT $limit OFFSET $offset";
    $siswa_data = getAll($data_query, $params);
    
    $total_pages = ceil($total / $limit);
    
    // Get available classes for filter
    $kelas_list = getAll("SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL ORDER BY kelas");
    
} catch (Exception $e) {
    $siswa_data = [];
    $total = 0;
    $total_pages = 0;
    $kelas_list = [];
    $error_message = $e->getMessage();
}
?>

<!-- Custom CSS -->
<link rel="stylesheet" href="assets/css/siswa.css">

<!-- Alert Messages -->
<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
    <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : ($message_type == 'danger' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
    <?= $message ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>

<!-- Page Content -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users mr-2"></i>Data Siswa
                    <span class="badge badge-info ml-2"><?= $total ?> siswa</span>
                </h3>
                <!-- Tombol Aksi -->
                <div class="card-tools">
                    <div class="btn-group" role="group">
                        <!-- Tombol Tambah Siswa -->
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Siswa
                        </a>
                        
                        <!-- Tombol Import - FIXED: Added correct ID and data attributes -->
                        <button type="button" 
                                class="btn btn-success" 
                                id="importButton"
                                data-toggle="modal" 
                                data-target="#importModal">
                            <i class="fas fa-upload"></i> Import
                        </button>
                        
                        <!-- Tombol Template Import -->
                        <a href="template_import.php" class="btn btn-info">
                            <i class="fas fa-file-download"></i> Template Excel
                        </a>
                        
                        <!-- Dropdown Export -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="export.php?type=excel">
                                    <i class="fas fa-file-excel"></i> Excel
                                </a>
                                <a class="dropdown-item" href="export.php?type=pdf">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Search and Filter -->
                <div class="table-actions mb-3">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Pencarian</label>
                            <div class="input-group">
                                <input type="text" 
                                       name="search" 
                                       class="form-control" 
                                       placeholder="Cari nama atau NIS..." 
                                       value="<?= htmlspecialchars($search) ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filter Kelas</label>
                            <select name="kelas" class="form-control" onchange="this.form.submit()">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?= $kelas['kelas'] ?>" <?= $kelas_filter == $kelas['kelas'] ? 'selected' : '' ?>>
                                        <?= $kelas['kelas'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Per Halaman</label>
                            <select name="limit" class="form-control" onchange="this.form.submit()">
                                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-right">
                            <label class="form-label">&nbsp;</label>
                            <div class="btn-group d-block">
                                <button type="button" class="btn btn-warning btn-sm" onclick="toggleBulkActions()">
                                    <i class="fas fa-check-square"></i> Pilih Multiple
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>

                <!-- Bulk Actions (Hidden by default) -->
                <div class="bulk-actions" id="bulkActions" style="display: none;">
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-info-circle"></i>
                                <span id="selectedCount">0</span> siswa dipilih
                            </div>
                            <div>
                                <button type="button" class="btn btn-warning btn-sm" onclick="bulkEditKelas()">
                                    <i class="fas fa-edit"></i> Ubah Kelas
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()">
                                    <i class="fas fa-trash"></i> Hapus Dipilih
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleBulkActions()">
                                    <i class="fas fa-times"></i> Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th width="3%" class="bulk-only" style="display: none;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAll">
                                        <label class="custom-control-label" for="selectAll"></label>
                                    </div>
                                </th>
                                <th width="5%">No</th>
                                <th width="15%">NIS</th>
                                <th width="25%">Nama</th>
                                <th width="12%">Kelas</th>
                                <th width="8%">JK</th>
                                <th width="15%">No HP</th>
                                <th width="17%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($siswa_data)): ?>
                                <?php foreach ($siswa_data as $index => $siswa): ?>
                                <tr>
                                    <td class="bulk-only" style="display: none;">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input row-checkbox" 
                                                   id="check_<?= $siswa['id'] ?>"
                                                   value="<?= $siswa['id'] ?>">
                                            <label class="custom-control-label" for="check_<?= $siswa['id'] ?>"></label>
                                        </div>
                                    </td>
                                    <td><?= $offset + $index + 1 ?></td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($siswa['nis']) ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 35px; height: 35px; font-size: 14px;">
                                                    <?= strtoupper(substr($siswa['nama'], 0, 1)) ?>
                                                </div>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($siswa['nama']) ?></strong>
                                                <?php if ($siswa['alamat']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars(substr($siswa['alamat'], 0, 30)) ?>...</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($siswa['kelas']): ?>
                                            <span class="badge badge-info"><?= htmlspecialchars($siswa['kelas']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $siswa['jenis_kelamin'] == 'L' ? 'primary' : 'pink' ?>">
                                            <?= $siswa['jenis_kelamin'] == 'L' ? 'L' : 'P' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($siswa['no_hp']): ?>
                                            <a href="tel:<?= $siswa['no_hp'] ?>" class="text-decoration-none">
                                                <i class="fas fa-phone text-success"></i>
                                                <?= htmlspecialchars($siswa['no_hp']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view.php?id=<?= $siswa['id'] ?>" class="btn btn-info" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?= $siswa['id'] ?>" class="btn btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $siswa['id'] ?>" 
                                               class="btn btn-danger" 
                                               title="Hapus"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus siswa <?= htmlspecialchars($siswa['nama']) ?>?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <?php if (isset($error_message)): ?>
                                            <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i><br>
                                            <h5>Error Database</h5>
                                            <p><?= htmlspecialchars($error_message) ?></p>
                                        <?php elseif ($search || $kelas_filter): ?>
                                            <i class="fas fa-search fa-3x mb-3"></i><br>
                                            <h5>Tidak Ditemukan</h5>
                                            <p>Tidak ada siswa yang sesuai dengan pencarian</p>
                                            <a href="?" class="btn btn-primary">Reset Filter</a>
                                        <?php else: ?>
                                            <i class="fas fa-users fa-3x mb-3"></i><br>
                                            <h5>Belum Ada Data</h5>
                                            <p>Belum ada siswa yang terdaftar</p>
                                            <a href="add.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Tambah Siswa Pertama
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="dataTables_info">
                            Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total) ?> dari <?= $total ?> siswa
                        </div>
                    </div>
                    <div class="col-md-6">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-end">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&kelas=<?= urlencode($kelas_filter) ?>&limit=<?= $limit ?>">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kelas=<?= urlencode($kelas_filter) ?>&limit=<?= $limit ?>">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php 
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                for ($i = $start; $i <= $end; $i++): 
                                ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kelas=<?= urlencode($kelas_filter) ?>&limit=<?= $limit ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kelas=<?= urlencode($kelas_filter) ?>&limit=<?= $limit ?>">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&kelas=<?= urlencode($kelas_filter) ?>&limit=<?= $limit ?>">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Include Import Modal BEFORE JavaScript -->
<?php 
// Include modal only if file exists
if (file_exists('import.php')) {
    include 'import.php'; 
} else {
    echo '<!-- Import modal file not found -->';
}
?>

<!-- Custom JavaScript - SIMPLIFIED AND FIXED -->
<script>
let bulkMode = false;

// Simple initialization when document is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded');
    
    // Setup bulk actions
    setupBulkActions();
    
    // Simple import button handler
    const importButton = document.getElementById('importButton');
    if (importButton) {
        console.log('Import button found, adding click handler');
        
        // Remove any existing click handlers
        importButton.onclick = null;
        
        // Add simple click handler
        importButton.addEventListener('click', function(e) {
            console.log('Import button clicked');
            // Let Bootstrap handle the modal
        });
    } else {
        console.error('Import button not found!');
    }
    
    // Debug modal
    const modal = document.getElementById('importModal');
    console.log('Import modal found:', modal !== null);
});

function setupBulkActions() {
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedCount();
        });
    }

    // Update selected count for individual checkboxes
    document.querySelectorAll('.row-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
}

function toggleBulkActions() {
    bulkMode = !bulkMode;
    
    const bulkElements = document.querySelectorAll('.bulk-only');
    const bulkActions = document.getElementById('bulkActions');
    
    if (bulkMode) {
        bulkElements.forEach(el => el.style.display = 'table-cell');
        if (bulkActions) bulkActions.style.display = 'block';
    } else {
        bulkElements.forEach(el => el.style.display = 'none');
        if (bulkActions) bulkActions.style.display = 'none';
        // Uncheck all
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        const selectAll = document.getElementById('selectAll');
        if (selectAll) selectAll.checked = false;
        updateSelectedCount();
    }
}

function updateSelectedCount() {
    const selected = document.querySelectorAll('.row-checkbox:checked').length;
    const countElement = document.getElementById('selectedCount');
    if (countElement) {
        countElement.textContent = selected;
    }
}

function bulkEditKelas() {
    const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Pilih siswa terlebih dahulu!');
        return;
    }
    
    const newKelas = prompt('Masukkan kelas baru untuk ' + selected.length + ' siswa:');
    if (newKelas) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process.php';
        
        form.innerHTML = `
            <input type="hidden" name="action" value="bulk_edit_kelas">
            <input type="hidden" name="kelas" value="${newKelas}">
            ${selected.map(id => `<input type="hidden" name="ids[]" value="${id}">`).join('')}
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

function bulkDelete() {
    const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Pilih siswa terlebih dahulu!');
        return;
    }
    
    if (confirm(`Apakah Anda yakin ingin menghapus ${selected.length} siswa yang dipilih?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process.php';
        
        form.innerHTML = `
            <input type="hidden" name="action" value="bulk_delete">
            ${selected.map(id => `<input type="hidden" name="ids[]" value="${id}">`).join('')}
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../../../core/includes/footer.php'; ?>