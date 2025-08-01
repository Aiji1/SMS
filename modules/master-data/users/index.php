<?php
/**
 * User & Akun Management
 * File: modules/master-data/users/index.php
 */

require_once '../../../core/config/database.php';

// Check authorization
if (!hasRole(['admin', 'kepala_sekolah'])) {
    $_SESSION['message'] = 'Anda tidak memiliki akses ke halaman ini';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../../../core/index.php');
    exit;
}

$page_title = 'User & Akun';
$breadcrumb = [
    ['title' => 'Master Data', 'url' => '../index.php'],
    ['title' => 'User & Akun']
];

// Get parameters
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Handle success/error messages
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

try {
    // Build query
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(nama LIKE ? OR username LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($role_filter) {
        $where_conditions[] = "role = ?";
        $params[] = $role_filter;
    }
    
    if ($status_filter) {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get total count
    $total_query = "SELECT COUNT(*) as total FROM users $where_clause";
    $total_result = getOne($total_query, $params);
    $total = $total_result ? $total_result['total'] : 0;
    
    // Get data
    $data_query = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $users_data = getAll($data_query, $params);
    
    $total_pages = ceil($total / $limit);
    
    // Get statistics
    $stats = getAll("SELECT role, status, COUNT(*) as count FROM users GROUP BY role, status ORDER BY role");
    
    // Get available roles
    $roles = ['admin', 'kepala_sekolah', 'guru', 'wali_kelas', 'petugas', 'wali_murid', 'murid'];
    
} catch (Exception $e) {
    $users_data = [];
    $total = 0;
    $total_pages = 0;
    $stats = [];
    $error_message = $e->getMessage();
}

// Include SMS Core layout
include '../../../core/includes/header.php';
?>

<!-- Custom CSS -->
<style>
.role-badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}

.user-avatar {
    width: 32px;
    height: 32px;
    background: #007bff;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 12px;
}

.stats-card {
    border-left: 4px solid #007bff;
}

.stats-card.admin { border-left-color: #dc3545; }
.stats-card.kepala_sekolah { border-left-color: #6f42c1; }
.stats-card.guru { border-left-color: #28a745; }
.stats-card.wali_kelas { border-left-color: #17a2b8; }
.stats-card.petugas { border-left-color: #ffc107; }
.stats-card.wali_murid { border-left-color: #fd7e14; }
.stats-card.murid { border-left-color: #20c997; }
</style>

<!-- Alert Messages -->
<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
    <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : ($message_type == 'danger' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
    <?= $message ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <?php
    $role_stats = [];
    foreach ($stats as $stat) {
        if (!isset($role_stats[$stat['role']])) {
            $role_stats[$stat['role']] = ['active' => 0, 'inactive' => 0, 'total' => 0];
        }
        $role_stats[$stat['role']][$stat['status']] = $stat['count'];
        $role_stats[$stat['role']]['total'] += $stat['count'];
    }
    
    $role_names = [
        'admin' => 'Administrator',
        'kepala_sekolah' => 'Kepala Sekolah', 
        'guru' => 'Guru',
        'wali_kelas' => 'Wali Kelas',
        'petugas' => 'Petugas',
        'wali_murid' => 'Wali Murid',
        'murid' => 'Murid'
    ];
    ?>
    
    <?php foreach ($role_names as $role => $name): ?>
        <?php if (isset($role_stats[$role])): ?>
        <div class="col-lg-3 col-6">
            <div class="small-box stats-card <?= $role ?>">
                <div class="inner">
                    <h3><?= $role_stats[$role]['total'] ?></h3>
                    <p><?= $name ?></p>
                    <small class="text-muted">
                        Aktif: <?= $role_stats[$role]['active'] ?? 0 ?> | 
                        Non-Aktif: <?= $role_stats[$role]['inactive'] ?? 0 ?>
                    </small>
                </div>
                <div class="icon">
                    <i class="fas fa-<?= $role == 'admin' ? 'user-shield' : ($role == 'guru' ? 'chalkboard-teacher' : ($role == 'murid' ? 'user-graduate' : 'user')) ?>"></i>
                </div>
                <a href="?role=<?= $role ?>" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<!-- Main Content -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users-cog mr-2"></i>Manajemen User & Akun
                    <span class="badge badge-info ml-2"><?= $total ?> users</span>
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                        <i class="fas fa-plus"></i> Tambah User
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Search and Filter -->
                <div class="table-actions mb-3">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Pencarian</label>
                            <div class="input-group">
                                <input type="text" 
                                       name="search" 
                                       class="form-control" 
                                       placeholder="Cari nama, username, email..." 
                                       value="<?= htmlspecialchars($search) ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filter Role</label>
                            <select name="role" class="form-control" onchange="this.form.submit()">
                                <option value="">Semua Role</option>
                                <?php foreach ($role_names as $role => $name): ?>
                                    <option value="<?= $role ?>" <?= $role_filter == $role ? 'selected' : '' ?>>
                                        <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Filter Status</label>
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Non-Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Per Halaman</label>
                            <select name="limit" class="form-control" onchange="this.form.submit()">
                                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                            </select>
                        </div>
                        <div class="col-md-2 text-right">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <a href="?" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-sync"></i> Reset
                                </a>
                            </div>
                        </div>
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">User Info</th>
                                <th width="15%">Username</th>
                                <th width="20%">Email</th>
                                <th width="12%">Role</th>
                                <th width="8%">Status</th>
                                <th width="10%">Join Date</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users_data)): ?>
                                <?php foreach ($users_data as $index => $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar mr-3">
                                                <?= strtoupper(substr($user['nama'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($user['nama']) ?></strong>
                                                <br><small class="text-muted">ID: <?= $user['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($user['username']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($user['email']): ?>
                                            <a href="mailto:<?= $user['email'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($user['email']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $role_colors = [
                                            'admin' => 'danger',
                                            'kepala_sekolah' => 'purple',
                                            'guru' => 'success', 
                                            'wali_kelas' => 'info',
                                            'petugas' => 'warning',
                                            'wali_murid' => 'orange',
                                            'murid' => 'teal'
                                        ];
                                        $badge_color = $role_colors[$user['role']] ?? 'secondary';
                                        ?>
                                        <span class="badge badge-<?= $badge_color ?> role-badge">
                                            <?= $role_names[$user['role']] ?? ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $user['status'] == 'active' ? 'success' : 'secondary' ?>">
                                            <?= $user['status'] == 'active' ? 'Aktif' : 'Non-Aktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y', strtotime($user['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-info" onclick="viewUser(<?= $user['id'] ?>)" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-warning" onclick="editUser(<?= $user['id'] ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-<?= $user['status'] == 'active' ? 'secondary' : 'success' ?>" 
                                                    onclick="toggleStatus(<?= $user['id'] ?>, '<?= $user['status'] ?>')" 
                                                    title="<?= $user['status'] == 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="fas fa-<?= $user['status'] == 'active' ? 'user-slash' : 'user-check' ?>"></i>
                                            </button>
                                            <?php if ($user['role'] != 'admin' || hasRole(['admin'])): ?>
                                            <button type="button" class="btn btn-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama']) ?>')" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
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
                                        <?php elseif ($search || $role_filter || $status_filter): ?>
                                            <i class="fas fa-search fa-3x mb-3"></i><br>
                                            <h5>Tidak Ditemukan</h5>
                                            <p>Tidak ada user yang sesuai dengan filter</p>
                                            <a href="?" class="btn btn-primary">Reset Filter</a>
                                        <?php else: ?>
                                            <i class="fas fa-users fa-3x mb-3"></i><br>
                                            <h5>Belum Ada User</h5>
                                            <p>Belum ada user yang terdaftar</p>
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
                            Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total) ?> dari <?= $total ?> users
                        </div>
                    </div>
                    <div class="col-md-6">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-end">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>&limit=<?= $limit ?>">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>&limit=<?= $limit ?>">
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
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>&limit=<?= $limit ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>&limit=<?= $limit ?>">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>&limit=<?= $limit ?>">
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Tambah User Baru
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addUserForm" method="POST" action="add_user.php">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <small class="form-text text-muted">Username untuk login (unik)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nama">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama" name="nama" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role">Role <span class="text-danger">*</span></label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="">Pilih Role</option>
                                    <?php foreach ($role_names as $role => $name): ?>
                                        <option value="<?= $role ?>"><?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Minimal 6 karakter</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Non-Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user"></i> Detail User
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="userDetailContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// View user detail
function viewUser(userId) {
    fetch(`get_user.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('userDetailContent').innerHTML = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 24px;">
                                ${user.nama.substring(0, 2).toUpperCase()}
                            </div>
                            <h5>${user.nama}</h5>
                            <span class="badge badge-primary">${user.role}</span>
                        </div>
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr><td><strong>Username:</strong></td><td>${user.username}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>${user.email || '-'}</td></tr>
                                <tr><td><strong>Role:</strong></td><td>${user.role}</td></tr>
                                <tr><td><strong>Status:</strong></td><td>
                                    <span class="badge badge-${user.status == 'active' ? 'success' : 'secondary'}">
                                        ${user.status == 'active' ? 'Aktif' : 'Non-Aktif'}
                                    </span>
                                </td></tr>
                                <tr><td><strong>Bergabung:</strong></td><td>${new Date(user.created_at).toLocaleDateString('id-ID')}</td></tr>
                            </table>
                        </div>
                    </div>
                `;
                $('#viewUserModal').modal('show');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error loading user data');
            console.error(error);
        });
}

// Edit user (redirect to edit page)
function editUser(userId) {
    window.location.href = `edit_user.php?id=${userId}`;
}

// Toggle user status
function toggleStatus(userId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'mengaktifkan' : 'menonaktifkan';
    
    if (confirm(`Apakah Anda yakin ingin ${action} user ini?`)) {
        fetch('toggle_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: userId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

// Delete user
function deleteUser(userId, userName) {
    if (confirm(`Apakah Anda yakin ingin menghapus user "${userName}"?\n\nData yang sudah dihapus tidak dapat dikembalikan.`)) {
        fetch('delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

<?php include '../../../core/includes/footer.php'; ?><?= $offset + $index + 1 ?></td>