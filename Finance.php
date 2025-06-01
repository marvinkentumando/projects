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

// Handle Finance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tithe'])) {
  if (!hash_equals($_SESSION['token'], $_POST['csrf_token'])) {
    die("Invalid CSRF token.");
  }

  $date = $_POST['collection_date'];
  $amount = floatval($_POST['amount']);

  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $amount <= 0) {
    $errorMessage = "Invalid date format or amount.";
  } else {
    $stmt = $conn->prepare("INSERT INTO tithes (collection_date, amount) VALUES (?, ?) ON DUPLICATE KEY UPDATE amount = VALUES(amount)");
    $stmt->bind_param("sd", $date, $amount);
    if ($stmt->execute()) {
      $successMessage = "Tithe collection recorded!";
    } else {
      $errorMessage = "Error saving data.";
    }
    $stmt->close();
  }
}

// Fetch tithe records
$tithes = $conn->query("SELECT * FROM tithes ORDER BY collection_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <title>Finance - Tithes Collection</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <style>body { font-family: 'Montserrat', sans-serif; }</style>
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
  <h2 class="text-3xl font-semibold text-gray-800 mb-4">Weekly Tithe Collection</h2>

  <?php if ($successMessage): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      <?= htmlspecialchars($successMessage); ?>
    </div>
  <?php elseif ($errorMessage): ?>
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      <?= htmlspecialchars($errorMessage); ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="bg-white p-6 rounded shadow mb-6">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['token']; ?>">
    <div class="mb-4">
      <label for="collection_date" class="block font-semibold text-gray-800 mb-2">Collection Date</label>
      <input type="date" name="collection_date" id="collection_date" required
             class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-300"/>
    </div>
    <div class="mb-4">
      <label for="amount" class="block font-semibold text-gray-800 mb-2">Amount (₱)</label>
      <input type="number" step="0.01" name="amount" id="amount" required
             class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-300"/>
    </div>
    <button type="submit" name="submit_tithe"
            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
      Submit Tithe
    </button>
  </form>

  <div class="bg-white p-6 rounded shadow">
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Tithe Collection History</h3>
    <table class="min-w-full border border-gray-200 text-left">
      <thead class="bg-gray-100">
        <tr>
          <th class="py-2 px-4 border-b">Date</th>
          <th class="py-2 px-4 border-b">Amount (₱)</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $tithes->fetch_assoc()): ?>
          <tr class="hover:bg-gray-50">
            <td class="py-2 px-4 border-b"><?= htmlspecialchars($row['collection_date']); ?></td>
            <td class="py-2 px-4 border-b font-medium text-green-700">₱<?= number_format($row['amount'], 2); ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

<footer class="bg-white border-t border-gray-200 py-4 text-center text-gray-600 text-sm">
  © 2025 Church Finance System. All rights reserved.
</footer>
</body>
</html>
