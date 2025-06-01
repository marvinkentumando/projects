<?php
$host = 'localhost';
$db   = 'church_db';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Handle Add Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_member'])) {
  $first_name = $_POST['first_name'];
  $last_name = $_POST['last_name'];
  $age = $_POST['age'];
  $gender = $_POST['gender'];
  $phone = $_POST['phone'];
  $email = $_POST['email'];
  $birthday = $_POST['birthday']; // changed from membership_date
  $status = $_POST['status'];

  $stmt = $conn->prepare("INSERT INTO members (first_name, last_name, age, gender, phone, email, birthday, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssisssss", $first_name, $last_name, $age, $gender, $phone, $email, $birthday, $status);

  if ($stmt->execute()) {
    echo "<script>alert('Member added successfully!');window.location='Members.php';</script>";
    exit();
  } else {
    echo "<script>alert('Error: " . $stmt->error . "');</script>";
  }
  $stmt->close();
}

// Handle Delete
if (isset($_GET['delete'])) {
  $member_id = intval($_GET['delete']);
  $conn->query("DELETE FROM members WHERE member_id=$member_id");
  echo "<script>window.location='Members.php';</script>";
  exit();
}

// Handle Edit (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_member'])) {
  $member_id = intval($_POST['member_id']);
  $first_name = $_POST['first_name'];
  $last_name = $_POST['last_name'];
  $age = $_POST['age'];
  $gender = $_POST['gender'];
  $phone = $_POST['phone'];
  $email = $_POST['email'];
  $birthday = $_POST['birthday']; // changed from membership_date
  $status = $_POST['status'];

  $stmt = $conn->prepare("UPDATE members SET first_name=?, last_name=?, age=?, gender=?, phone=?, email=?, birthday=?, status=? WHERE member_id=?");
  $stmt->bind_param("ssisssssi", $first_name, $last_name, $age, $gender, $phone, $email, $birthday, $status, $member_id);

  if ($stmt->execute()) {
    echo "<script>alert('Member updated successfully!');window.location='Members.php';</script>";
    exit();
  } else {
    echo "<script>alert('Error: " . $stmt->error . "');</script>";
  }
  $stmt->close();
}

// For edit form display
$edit_member = null;
if (isset($_GET['edit'])) {
  $member_id = intval($_GET['edit']);
  $result = $conn->query("SELECT * FROM members WHERE member_id=$member_id");
  $edit_member = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Church Membership Management System</title>
  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    body { font-family: 'Montserrat', sans-serif; }
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

  <!-- Main Content -->
  <main class="flex-grow container mx-auto px-4 py-6">

    <!-- Add Member Form Section -->
    <section id="add-member" class="mb-10">
      <h2 class="text-3xl font-semibold text-gray-800 mb-6">Add New Member</h2>
      <form id="add-member-form" method="POST" class="bg-white rounded-lg shadow p-6 max-w-3xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="firstName" class="block text-gray-700 font-medium mb-1">First Name</label>
            <input type="text" id="firstName" name="first_name" placeholder="John" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
          </div>
          <div>
            <label for="lastName" class="block text-gray-700 font-medium mb-1">Last Name</label>
            <input type="text" id="lastName" name="last_name" placeholder="Doe" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
          </div>
          <div>
            <label for="age" class="block text-gray-700 font-medium mb-1">Age</label>
            <input type="number" id="age" name="age" min="0" max="120" placeholder="35" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
          </div>
          <div>
            <label for="gender" class="block text-gray-700 font-medium mb-1">Gender</label>
            <select id="gender" name="gender" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="" disabled selected>Select gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div>
            <label for="phone" class="block text-gray-700 font-medium mb-1">Phone Number</label>
            <input type="tel" id="phone" name="phone" pattern="^\+?[0-9\s\-]{7,15}$" placeholder="+1234567890" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
          </div>
          <div>
            <label for="email" class="block text-gray-700 font-medium mb-1">Email</label>
            <input type="email" id="email" name="email" placeholder="john.doe@example.com" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
          </div>
          <div>
            <label for="birthday" class="block text-gray-700 font-medium mb-1">Birthday</label>
            <input type="date" id="birthday" name="birthday" max="" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
          </div>
          <div>
            <label for="status" class="block text-gray-700 font-medium mb-1">Status</label>
            <select id="status" name="status" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="" disabled selected>Select status</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="mt-6 flex justify-end space-x-4">
          <button type="reset" class="px-5 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 transition">Reset</button>
          <button type="submit" name="submit_member" class="px-5 py-2 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Add Member</button>
        </div>
      </form>
    </section>

    <!-- Members Table Section -->
<section class="container mx-auto px-4 py-8">
  <h2 class="text-xl font-semibold mb-4">Member List</h2>
  <table class="min-w-full bg-white border border-gray-200 shadow rounded-lg">
    <thead class="bg-gray-100 text-left">
      <tr>
        <th class="py-2 px-4 border-b">ID</th>
        <th class="py-2 px-4 border-b">First Name</th>
        <th class="py-2 px-4 border-b">Last Name</th>
        <th class="py-2 px-4 border-b">Age</th>
        <th class="py-2 px-4 border-b">Gender</th>
        <th class="py-2 px-4 border-b">Phone</th>
        <th class="py-2 px-4 border-b">Email</th>
        <th class="py-2 px-4 border-b">Birthday</th>
        <th class="py-2 px-4 border-b">Status</th>
        <th class="py-2 px-4 border-b">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT * FROM members ORDER BY member_id DESC");
      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          echo "<tr class='hover:bg-gray-50'>";
          echo "<td class='py-2 px-4 border-b'>" . $row['member_id'] . "</td>";
          echo "<td class='py-2 px-4 border-b'>" . $row['first_name'] . "</td>";
          echo "<td class='py-2 px-4 border-b'>" . $row['last_name'] . "</td>";
          echo "<td class='py-2 px-4 border-b'>" . $row['age'] . "</td>";
          echo "<td class='py-2 px-4 border-b'>" . $row['gender'] . "</td>";
          echo "<td class='py-2 px-4 border-b'>" . $row['phone'] . "</td>";
          echo "<td class='py-2 px-4 border-b'>" . $row['email'] . "</td>";
          echo "<td class='py-2 px-4 border-b'>" . $row['birthday'] . "</td>";
          echo "<td class='py-2 px-4 border-b'>" . $row['status'] . "</td>";
          echo "<td class='py-2 px-4 border-b text-center space-x-2'>
                  <a href='?edit=" . $row['member_id'] . "' 
                     class=' items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 transition'
                     title='Edit'>
                  <i class='fas fa-edit'></i>
                </a>
                <a href='?delete=" . $row['member_id'] . "' 
                   class=' items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-600 hover:bg-red-200 transition'
                   title='Delete'
                   onclick='return confirm(\"Are you sure you want to delete this member?\");'>
                  <i class='fas fa-trash-alt'></i>
                </a>
              </td>";
          echo "</tr>";
        }
      } else {
        echo "<tr><td colspan='10' class='text-center py-4'>No members found.</td></tr>";
      }
      ?>
    </tbody>
  </table>
</section>
   

  </main>

  <!-- Footer -->
  <footer class="bg-white border-t border-gray-200 py-4 text-center text-gray-600 text-sm">
    © 2024 Church Membership Management System. All rights reserved.
  </footer>

  <!-- Scripts -->
  <?php if ($edit_member): ?>
<div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full p-8 relative">
    <button onclick="closeEditModal()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold mb-6">Edit Member</h2>
    <form method="POST">
      <input type="hidden" name="member_id" value="<?php echo $edit_member['member_id']; ?>">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-gray-700 font-medium mb-1">First Name</label>
          <input type="text" name="first_name" value="<?php echo htmlspecialchars($edit_member['first_name']); ?>" required class="w-full border border-gray-300 rounded px-3 py-2"/>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Last Name</label>
          <input type="text" name="last_name" value="<?php echo htmlspecialchars($edit_member['last_name']); ?>" required class="w-full border border-gray-300 rounded px-3 py-2"/>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Age</label>
          <input type="number" name="age" value="<?php echo htmlspecialchars($edit_member['age']); ?>" required class="w-full border border-gray-300 rounded px-3 py-2"/>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Gender</label>
          <select name="gender" required class="w-full border border-gray-300 rounded px-3 py-2">
            <option value="Male" <?php if($edit_member['gender']=='Male') echo 'selected'; ?>>Male</option>
            <option value="Female" <?php if($edit_member['gender']=='Female') echo 'selected'; ?>>Female</option>
            <option value="Other" <?php if($edit_member['gender']=='Other') echo 'selected'; ?>>Other</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Phone</label>
          <input type="tel" name="phone" value="<?php echo htmlspecialchars($edit_member['phone']); ?>" required class="w-full border border-gray-300 rounded px-3 py-2"/>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Email</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($edit_member['email']); ?>" required class="w-full border border-gray-300 rounded px-3 py-2"/>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Birthday</label>
          <input type="date" name="birthday" value="<?php echo htmlspecialchars($edit_member['birthday']); ?>" required class="w-full border border-gray-300 rounded px-3 py-2"/>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Status</label>
          <select name="status" required class="w-full border border-gray-300 rounded px-3 py-2">
            <option value="Active" <?php if($edit_member['status']=='Active') echo 'selected'; ?>>Active</option>
            <option value="Inactive" <?php if($edit_member['status']=='Inactive') echo 'selected'; ?>>Inactive</option>
          </select>
        </div>
      </div>
      <div class="mt-6 flex justify-end space-x-4">
        <a href="Members.php" class="px-5 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 transition">Cancel</a>
        <button type="submit" name="update_member" class="px-5 py-2 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<script>
  // Prevent scrolling when modal is open
  document.body.classList.add('overflow-hidden');
  function closeEditModal() {
    window.location = 'Members.php';
  }
</script>
<?php endif; ?>
</body>
</html>
