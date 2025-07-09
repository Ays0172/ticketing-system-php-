<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Administrator') {
    header("Location: ../index.php");
    exit;
}

// Filter logic
$conditions = [];
$params = [];
$types = "";

if (!empty($_GET['dept'])) {
    $conditions[] = "department_id = ?";
    $params[] = $_GET['dept'];
    $types .= "i";
}
if (!empty($_GET['start'])) {
    $conditions[] = "DATE(created_at) >= ?";
    $params[] = $_GET['start'];
    $types .= "s";
}
if (!empty($_GET['end'])) {
    $conditions[] = "DATE(created_at) <= ?";
    $params[] = $_GET['end'];
    $types .= "s";
}
$where = count($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get departments for dropdown
$deptList = $conn->query("SELECT * FROM departments");

// Fetch data for each chart
function getData($conn, $sql, $params = [], $types = "") {
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

$statusData = getData($conn, "SELECT status, COUNT(*) as total FROM tasks $where GROUP BY status", $params, $types);
$deptData = getData($conn, "SELECT d.name, COUNT(*) as total FROM tasks t JOIN departments d ON t.department_id = d.id $where GROUP BY d.name", $params, $types);
$timelineData = getData($conn, "SELECT DATE(created_at) as date, COUNT(*) as total FROM tasks $where GROUP BY DATE(created_at)", $params, $types);
$staffData = getData($conn, "SELECT u.name, COUNT(*) as total FROM tasks t JOIN users u ON t.support_staff_id = u.user_id $where GROUP BY u.name", $params, $types);
?>
<!DOCTYPE html>
<html>
<head>
    <title>📊 Admin Stats</title>
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
<h2>📊 Admin Task Statistics</h2>
<p><a href="dashboard.php">⬅ Back to Dashboard</a></p>

<!-- Filter Form -->
<form method="GET" style="text-align:center;">
    <select name="dept">
        <option value="">All Departments</option>
        <?php while ($d = $deptList->fetch_assoc()): ?>
            <option value="<?= $d['id'] ?>" <?= ($_GET['dept'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
    From: <input type="date" name="start" value="<?= $_GET['start'] ?? '' ?>">
    To: <input type="date" name="end" value="<?= $_GET['end'] ?? '' ?>">
    <button type="submit">Filter</button>
</form>

<!-- Export Controls -->
<div style="text-align:center; margin:20px;">
    <select id="chartSelect">
        <option value="statusChart">Status Chart</option>
        <option value="deptChart">Department Chart</option>
        <option value="timelineChart">Timeline Chart</option>
        <option value="staffChart">Support Staff Chart</option>
    </select>
    <button onclick="downloadChartAsImage()">📷 Export PNG</button>
    <button onclick="downloadChartAsPDF()">📄 Export PDF</button>
</div>

<!-- Charts -->
<div style="width:80%; margin:auto;">
    <canvas id="statusChart"></canvas><br>
    <canvas id="deptChart"></canvas><br>
    <canvas id="timelineChart"></canvas><br>
    <canvas id="staffChart"></canvas>
</div>

<script>
function chartDataFromPHP(rows, keyLabel, keyValue) {
    const labels = [], data = [];
    rows.forEach(r => {
        labels.push(r[keyLabel]);
        data.push(r[keyValue]);
    });
    return { labels, data };
}

const statusData = <?= json_encode($statusData->fetch_all(MYSQLI_ASSOC)) ?>;
const deptData = <?= json_encode($deptData->fetch_all(MYSQLI_ASSOC)) ?>;
const timelineData = <?= json_encode($timelineData->fetch_all(MYSQLI_ASSOC)) ?>;
const staffData = <?= json_encode($staffData->fetch_all(MYSQLI_ASSOC)) ?>;

const ctx1 = document.getElementById('statusChart').getContext('2d');
new Chart(ctx1, {
    type: 'doughnut',
    data: {
        labels: chartDataFromPHP(statusData, 'status', 'total').labels,
        datasets: [{
            label: 'Task Status',
            data: chartDataFromPHP(statusData, 'status', 'total').data,
            backgroundColor: ['#dc3545', '#ffc107', '#198754', '#0d6efd']
        }]
    }
});

const ctx2 = document.getElementById('deptChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: chartDataFromPHP(deptData, 'name', 'total').labels,
        datasets: [{
            label: 'Tasks by Department',
            data: chartDataFromPHP(deptData, 'name', 'total').data,
            backgroundColor: '#0dcaf0'
        }]
    }
});

const ctx3 = document.getElementById('timelineChart').getContext('2d');
new Chart(ctx3, {
    type: 'line',
    data: {
        labels: chartDataFromPHP(timelineData, 'date', 'total').labels,
        datasets: [{
            label: 'Tasks Over Time',
            data: chartDataFromPHP(timelineData, 'date', 'total').data,
            backgroundColor: '#6610f2',
            borderColor: '#6610f2',
            fill: false
        }]
    }
});

const ctx4 = document.getElementById('staffChart').getContext('2d');
new Chart(ctx4, {
    type: 'bar',
    data: {
        labels: chartDataFromPHP(staffData, 'name', 'total').labels,
        datasets: [{
            label: 'Tasks Assigned per Staff',
            data: chartDataFromPHP(staffData, 'name', 'total').data,
            backgroundColor: '#fd7e14'
        }]
    }
});

// Export buttons
function downloadChartAsImage() {
    const canvasId = document.getElementById('chartSelect').value;
    const canvas = document.getElementById(canvasId);
    html2canvas(canvas).then(canvas => {
        const link = document.createElement('a');
        link.href = canvas.toDataURL("image/png");
        link.download = canvasId + ".png";
        link.click();
    });
}

function downloadChartAsPDF() {
    const canvasId = document.getElementById('chartSelect').value;
    const canvas = document.getElementById(canvasId);
    html2canvas(canvas).then(canvas => {
        const imgData = canvas.toDataURL("image/png");
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();
        pdf.addImage(imgData, 'PNG', 10, 10, 180, 90);
        pdf.save(canvasId + ".pdf");
    });
}
</script>
</body>
</html>
