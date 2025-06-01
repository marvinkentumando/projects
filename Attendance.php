<?php
session_start();

// Database connection
$host = 'localhost';
$db   = 'church_db';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Connection failed. Please try again later.");
}

// CSRF token
if (empty($_SESSION['token'])) {
  $_SESSION['token'] = bin2hex(random_bytes(32));
}

// Initialize messages
$successMessage = "";
$errorMessage = "";

// Handle Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
  if (!hash_equals($_SESSION['token'], $_POST['csrf_token'])) {
    die("Invalid CSRF token.");
  }

  $date = $_POST['attendance_date'];
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $errorMessage = "Invalid date format.";
  } else {
    if (isset($_POST['attendance']) && is_array($_POST['attendance'])) {
      foreach ($_POST['attendance'] as $member_id => $status) {
        $status = $status === "Present" ? "Present" : "Absent";
        $member_id = intval($member_id);
        $stmt = $conn->prepare(
          "INSERT INTO attendance (member_id, attendance_date, status) VALUES (?, ?, ?)
          ON DUPLICATE KEY UPDATE status = VALUES(status)"
        );
        $stmt->bind_param("iss", $member_id, $date, $status);
        $stmt->execute();
        $stmt->close();
      }
      $successMessage = "Attendance saved successfully!";
    } else {
      $errorMessage = "No attendance data received.";
    }
  }
}

// Pagination setup
$limit = 20;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

$totalResult = $conn->query("SELECT COUNT(*) as total FROM members");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch members
$members = $conn->query(
  "SELECT member_id, first_name, last_name FROM members ORDER BY last_name ASC LIMIT $limit OFFSET $offset"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Attendance - Church System</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <style>
    body { font-family: 'Montserrat', sans-serif; }
    thead th { position: sticky; top: 0; background-color: #f9fafb; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

<!-- Header -->
<header class="bg-white shadow-md">
  <div class="container mx-auto px-4 py-4 flex items-center justify-between">
    <div class="flex items-center space-x-3">
      <img src="https://storage.googleapis.com/a1aa/image/fd95b878-4225-439f-9f97-68dd2e12cb39.jpg" alt="Church logo" class="w-12 h-12 rounded" width="48" height="48"/>
      <h1 class="text-2xl font-semibold text-gray-800">Lahi Foursquare Gospel Church</h1>
    </div>
    <nav class="hidden md:flex space-x-6 text-gray-700 font-medium">
      <a href="#dashboard" class="hover:text-blue-600 transition">Dashboard</a>
      <a href="Members.php" class="hover:text-blue-600 transition">Members</a>
      <a href="Attendance.php" class="hover:text-blue-600 transition">Attendance</a>
      <a href="Finance.php" class="hover:text-blue-600 transition">Finance</a>
    </nav>
    <button id="mobile-menu-button" aria-label="Toggle menu" class="md:hidden text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-600">
      <i class="fas fa-bars fa-lg"></i>
    </button>
  </div>
  <nav id="mobile-menu" class="md:hidden bg-white border-t border-gray-200 hidden">
    <a href="#dashboard" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">Dashboard</a>
    <a href="Members.php" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">Members</a>
    <a href="Attendance.php" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">Attendance</a>
    <a href="Finance.php" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">Finance</a>
  </nav>
</header>

<main class="container mx-auto px-4 py-6 flex-grow">
  <h2 class="text-3xl font-semibold text-gray-800 mb-4">Attendance Sheet</h2>

  <?php if ($successMessage): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      <?php echo htmlspecialchars($successMessage); ?>
    </div>
  <?php elseif ($errorMessage): ?>
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      <?php echo htmlspecialchars($errorMessage); ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="bg-white p-6 rounded shadow-lg">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['token']; ?>">
    
    <!-- Attendance Date Picker -->
    <div class="mb-6">
      <label for="attendance_date" class="block text-gray-800 font-semibold mb-2">
      <i class="far fa-calendar-alt mr-2 text-blue-500"></i>
      Attendance Date
      </label>
      <div class="relative">
      <input
        type="date"
        id="attendance_date"
        name="attendance_date"
        required
        value="<?php echo isset($_POST['attendance_date']) ? htmlspecialchars($_POST['attendance_date']) : ''; ?>"
        class="w-full border border-blue-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg px-4 py-2 text-gray-700 shadow-sm transition">

      </div>
      <p class="text-xs text-gray-500 mt-1">Please select the date for attendance entry.</p>
    </div>

    <div class="flex justify-end mb-4 space-x-3">
      <button type="button"
      onclick="markAll('Present')"
      class="inline-flex items-center px-4 py-2 bg-green-500 text-white text-sm font-semibold rounded shadow hover:bg-green-600 transition focus:outline-none focus:ring-2 focus:ring-green-400">
      <i class="fas fa-user-check mr-2"></i> Mark All Present
      </button>
      <button type="button"
      onclick="markAll('Absent')"
      class="inline-flex items-center px-4 py-2 bg-red-500 text-white text-sm font-semibold rounded shadow hover:bg-red-600 transition focus:outline-none focus:ring-2 focus:ring-red-400">
      <i class="fas fa-user-times mr-2"></i> Mark All Absent
      </button>
    </div>
    <script>
    function markAll(status) {
      document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
      if (radio.value === status) {
        radio.checked = true;
        // Trigger change event if needed
        radio.dispatchEvent(new Event('change', { bubbles: true }));
      }
      });
    }
    </script>

    <div class="overflow-x-auto">
      <table class="min-w-full bg-white border border-gray-200 rounded-lg">
        <thead class="bg-gray-100">
        <tr>
          <th class="py-2 px-4 border-b text-left">Name</th>
          <th class="py-2 px-4 border-b text-center">Status</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $members->fetch_assoc()): ?>
          <tr class="hover:bg-gray-50">
            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
            <td class="py-2 px-4 border-b text-center">
                <div class="flex items-center justify-center space-x-4">
                <label class="inline-flex items-center cursor-pointer">
                  <input type="radio" name="attendance[<?php echo $row['member_id']; ?>]" value="Present"
                  class="form-radio text-green-600 focus:ring-green-500"
                  <?php echo (isset($_POST['attendance'][$row['member_id']]) && $_POST['attendance'][$row['member_id']] === 'Present') ? 'checked' : ''; ?>>
                  <span class="ml-2 text-green-700 font-medium">Present</span>
                </label>
                <label class="inline-flex items-center cursor-pointer">
                  <input type="radio" name="attendance[<?php echo $row['member_id']; ?>]" value="Absent"
                  class="form-radio text-red-600 focus:ring-red-500"
                  <?php echo (isset($_POST['attendance'][$row['member_id']]) && $_POST['attendance'][$row['member_id']] === 'Absent') ? 'checked' : ''; ?>>
                  <span class="ml-2 text-red-700 font-medium">Absent</span>
                </label>
                </div>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-6 flex justify-between items-center">
      <div>
        <a href="export_attendance_csv.php?date=<?php echo urlencode($_POST['attendance_date'] ?? ''); ?>" class="text-sm text-green-600 hover:underline">Export as CSV</a>
      </div>
      <button type="submit" name="submit_attendance" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">Save Attendance</button>
    </div>
  </form>

  <?php
  // Attendance Summary
  if (!empty($_POST['attendance_date'])):
    $selectedDate = $_POST['attendance_date'];
    $attendanceListQuery = "
      SELECT m.first_name, m.last_name, a.status
      FROM attendance a
      JOIN members m ON a.member_id = m.member_id
      WHERE a.attendance_date = ?
      ORDER BY m.last_name ASC
    ";
    $stmt = $conn->prepare($attendanceListQuery);
    $stmt->bind_param("s", $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $presentList = [];
    $absentList = [];

    while ($row = $result->fetch_assoc()) {
      $fullName = htmlspecialchars($row['last_name'] . ', ' . $row['first_name']);
      if ($row['status'] === 'Present') {
        $presentList[] = $fullName;
      } else {
        $absentList[] = $fullName;
      }
    }
    $stmt->close();
  ?>
  <!-- Attendance Summary Table -->
  <div class="mt-10 bg-white p-6 rounded shadow">
    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Attendance Summary for <?php echo htmlspecialchars($selectedDate); ?></h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Present -->
      <div>
        <h4 class="text-xl font-semibold text-green-700 mb-2">Present (<?php echo count($presentList); ?>)</h4>
        <ul class="list-disc list-inside space-y-1 text-gray-700">
          <?php if (count($presentList)): ?>
            <?php foreach ($presentList as $name): ?>
              <li><?php echo $name; ?></li>
            <?php endforeach; ?>
          <?php else: ?>
            <li>No one marked Present.</li>
          <?php endif; ?>
        </ul>
      </div>
      <!-- Absent -->
      <div>
        <h4 class="text-xl font-semibold text-red-700 mb-2">Absent (<?php echo count($absentList); ?>)</h4>
        <ul class="list-disc list-inside space-y-1 text-gray-700">
          <?php if (count($absentList)): ?>
            <?php foreach ($absentList as $name): ?>
              <li><?php echo $name; ?></li>
            <?php endforeach; ?>
          <?php else: ?>
            <li>No one marked Absent.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>

<footer class="bg-white border-t border-gray-200 py-4 text-center text-gray-600 text-sm">
  © 2025 Church Attendance System. All rights reserved.
</footer>
</body>
</html>
