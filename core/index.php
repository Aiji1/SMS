<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include config/database.php FIRST
try {
    require_once 'config/database.php';
} catch (Exception $e) {
    die("Error loading database config: " . $e->getMessage());
}

$page_title = 'Dashboard';

// Initialize default values
$total_siswa = 0;
$total_guru = 0;
$total_mapel = 0;
$total_jurnal = 0;
$recent_siswa = [];
$recent_jurnal = [];

// Get statistics with error handling
try {
    // Test database connection first
    $test = getOne("SELECT 1 as test");
    if (!$test) {
        throw new Exception("Database connection failed");
    }
    
    // Get statistics
    $siswa_result = getOne("SELECT COUNT(*) as total FROM siswa");
    $total_siswa = $siswa_result ? (int)$siswa_result['total'] : 0;
    
    $guru_result = getOne("SELECT COUNT(*) as total FROM guru");
    $total_guru = $guru_result ? (int)$guru_result['total'] : 0;
    
    $mapel_result = getOne("SELECT COUNT(*) as total FROM mata_pelajaran");
    $total_mapel = $mapel_result ? (int)$mapel_result['total'] : 0;
    
    $jurnal_result = getOne("SELECT COUNT(*) as total FROM jurnal_mengajar WHERE DATE(tanggal) = CURDATE()");
    $total_jurnal = $jurnal_result ? (int)$jurnal_result['total'] : 0;

    // Get recent activities
    $recent_siswa = getAll("SELECT * FROM siswa ORDER BY created_at DESC LIMIT 5");
    if (!$recent_siswa) $recent_siswa = [];
    
    $recent_jurnal = getAll("
        SELECT jm.*, 
               COALESCE(g.nama, 'Unknown') as nama_guru, 
               COALESCE(mp.nama, 'Unknown') as nama_mapel 
        FROM jurnal_mengajar jm 
        LEFT JOIN guru g ON jm.guru_id = g.id 
        LEFT JOIN mata_pelajaran mp ON jm.mapel_id = mp.id 
        ORDER BY jm.created_at DESC LIMIT 5
    ");
    if (!$recent_jurnal) $recent_jurnal = [];
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Dashboard Error: " . $e->getMessage());
    
    // Show error in development mode
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "<strong>Debug Error:</strong> " . $e->getMessage();
    echo "<br><strong>File:</strong> " . __FILE__;
    echo "<br><a href='debug.php'>Run Debug Test</a>";
    echo "</div>";
}

// Include header
try {
    include 'includes/header.php';
} catch (Exception $e) {
    die("Error loading header: " . $e->getMessage());
}
?>

<!-- Small boxes (Stat box) -->
<div class="row">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-info">
      <div class="inner">
        <h3><?= $total_siswa ?></h3>
        <p>Total Siswa</p>
      </div>
      <div class="icon">
        <i class="fas fa-users"></i>
      </div>
      <a href="<?= $base_url ?>/modules/master-data/siswa/" class="small-box-footer">
        More info <i class="fas fa-arrow-circle-right"></i>
      </a>
    </div>
  </div>
  
  <div class="col-lg-3 col-6">
    <div class="small-box bg-success">
      <div class="inner">
        <h3><?= $total_guru ?></h3>
        <p>Total Guru</p>
      </div>
      <div class="icon">
        <i class="fas fa-chalkboard-teacher"></i>
      </div>
      <a href="#" class="small-box-footer">
        More info <i class="fas fa-arrow-circle-right"></i>
      </a>
    </div>
  </div>
  
  <div class="col-lg-3 col-6">
    <div class="small-box bg-warning">
      <div class="inner">
        <h3><?= $total_mapel ?></h3>
        <p>Mata Pelajaran</p>
      </div>
      <div class="icon">
        <i class="fas fa-book"></i>
      </div>
      <a href="#" class="small-box-footer">
        More info <i class="fas fa-arrow-circle-right"></i>
      </a>
    </div>
  </div>
  
  <div class="col-lg-3 col-6">
    <div class="small-box bg-danger">
      <div class="inner">
        <h3><?= $total_jurnal ?></h3>
        <p>Jurnal Hari Ini</p>
      </div>
      <div class="icon">
        <i class="fas fa-clipboard-list"></i>
      </div>
      <a href="#" class="small-box-footer">
        More info <i class="fas fa-arrow-circle-right"></i>
      </a>
    </div>
  </div>
</div>

<div class="row">
  <!-- Left col -->
  <section class="col-lg-7">
    <!-- Jurnal Mengajar Terbaru -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-clipboard-list mr-1"></i>
          Jurnal Mengajar Terbaru
        </h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Guru</th>
                <th>Mata Pelajaran</th>
                <th>Kelas</th>
                <th>Jam Ke</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($recent_jurnal)): ?>
                <?php foreach ($recent_jurnal as $jurnal): ?>
                <tr>
                  <td><?= $jurnal['tanggal'] ? date('d/m/Y', strtotime($jurnal['tanggal'])) : '-' ?></td>
                  <td><?= htmlspecialchars($jurnal['nama_guru'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($jurnal['nama_mapel'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($jurnal['kelas'] ?? '-') ?></td>
                  <td><?= $jurnal['jam_ke'] ?? '-' ?></td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
              <tr>
                <td colspan="5" class="text-center text-muted">Belum ada jurnal mengajar</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-chart-pie mr-1"></i>
          Statistik Siswa per Kelas
        </h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <canvas id="siswaChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
      </div>
    </div>
  </section>
  
  <!-- right col -->
  <section class="col-lg-5">
    <!-- Siswa Terbaru -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-users mr-1"></i>
          Siswa Terbaru
        </h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php if (!empty($recent_siswa)): ?>
            <?php foreach ($recent_siswa as $siswa): ?>
            <li class="list-group-item">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <img src="https://via.placeholder.com/40" alt="User Avatar" class="img-size-50 img-circle">
                </div>
                <div class="flex-grow-1 ml-3">
                  <h6 class="mb-1"><?= htmlspecialchars($siswa['nama']) ?></h6>
                  <p class="mb-1 text-muted small">
                    NIS: <?= htmlspecialchars($siswa['nis']) ?> | 
                    Kelas: <?= htmlspecialchars($siswa['kelas'] ?? '-') ?>
                  </p>
                  <small class="text-muted">
                    <?= $siswa['created_at'] ? date('d/m/Y', strtotime($siswa['created_at'])) : '-' ?>
                  </small>
                </div>
              </div>
            </li>
            <?php endforeach; ?>
          <?php else: ?>
          <li class="list-group-item text-center text-muted">
            Belum ada data siswa
          </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-bolt mr-1"></i>
          Quick Actions
        </h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-6">
            <a href="<?= $base_url ?>/modules/master-data/siswa/" class="btn btn-primary btn-block">
              <i class="fas fa-users"></i><br>
              Kelola Siswa
            </a>
          </div>
          <div class="col-6">
            <a href="<?= $base_url ?>/modules/master-data/" class="btn btn-success btn-block">
              <i class="fas fa-database"></i><br>
              Master Data
            </a>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col-6">
            <a href="<?= $base_url ?>/core/api/" target="_blank" class="btn btn-warning btn-block">
              <i class="fas fa-code"></i><br>
              API Docs
            </a>
          </div>
          <div class="col-6">
            <a href="debug.php" class="btn btn-info btn-block">
              <i class="fas fa-bug"></i><br>
              Debug
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php 
// Custom JavaScript for charts
$total_siswa_safe = max($total_siswa, 6); // Prevent division by zero
$custom_js = "
// Chart for siswa per kelas
if (document.getElementById('siswaChart')) {
    var ctx = document.getElementById('siswaChart').getContext('2d');
    var siswaChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['X-A', 'X-B', 'XI-A', 'XI-B', 'XII-A', 'XII-B'],
            datasets: [{
                data: [" . (int)($total_siswa_safe/6) . ", " . (int)($total_siswa_safe/6) . ", " . (int)($total_siswa_safe/6) . ", " . (int)($total_siswa_safe/6) . ", " . (int)($total_siswa_safe/6) . ", " . (int)($total_siswa_safe/6) . "],
                backgroundColor: [
                    '#f56954',
                    '#00a65a', 
                    '#f39c12',
                    '#00c0ef',
                    '#3c8dbc',
                    '#d2d6de'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}
";

try {
    include 'includes/footer.php';
} catch (Exception $e) {
    die("Error loading footer: " . $e->getMessage());
}
?>