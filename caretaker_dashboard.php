<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'caretaker') {
    header("Location: /Login.php");
    exit();
}

include('db.php');

// Fetch all patients
$patients = [];
$pstmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'patient'");
$pstmt->execute();
$result = $pstmt->get_result();
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}

$success = $error = "";
$uploaded_medicines = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $name = $_POST['name'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $meal_timing = $_POST['meal_timing'];
    $intake_time = $_POST['intake_time'];

    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $safe_filename = uniqid('med_') . '.' . $file_ext;
        $image_path = $upload_dir . $safe_filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $error = "Failed to upload image.";
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO medicines (patient_id, name, dosage, frequency, meal_timing, intake_time, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $patient_id, $name, $dosage, $frequency, $meal_timing, $intake_time, $image_path);
        if ($stmt->execute()) {
            $success = "Medicine uploaded successfully!";
        } else {
            $error = "Database error: " . $conn->error;
        }
    }

    // Fetch medicines for selected patient
    $stmt = $conn->prepare("SELECT * FROM medicines WHERE patient_id = ? ORDER BY intake_time DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $uploaded_medicines[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Caretaker Dashboard - HealthBuddy</title>
    <link rel="stylesheet" href="/CSS/Dashboard.css">
    <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }

    body {
      background-color: #f4f6f9;
      display: flex;
      flex-direction: column;
      height: 100vh;
    }

    .custom-navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #2c3e50;
      padding: 15px 30px;
      color: #fff;
    }

    .custom-navbar .logo {
      font-size: 24px;
      font-weight: 700;
      display: flex;
      align-items: center;
    }

    .custom-navbar .logo img {
      width: 40px;
      margin-right: 10px;
    }

    .custom-navbar .nav-links {
      list-style: none;
      display: flex;
      gap: 20px;
    }

    .custom-navbar .nav-links li a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s;
    }

    .custom-navbar .nav-links li a:hover {
      color: #1abc9c;
    }

    .main-layout {
      display: flex;
      flex: 1;
    }

    .sidebar {
      background-color: #34495e;
      width: 220px;
      padding: 30px 15px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .sidebar a {
      color: white;
      text-decoration: none;
      font-size: 16px;
      padding: 10px 15px;
      border-radius: 8px;
      transition: background 0.3s;
    }

    .sidebar a:hover {
      background-color: #1abc9c;
    }

    .main-content {
      flex: 1;
      padding: 40px;
      overflow-y: auto;
    }

   .container-style {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  max-width: 600px;  /* Limit form width */
  margin: 0 auto;    /* Center it */
}

    h2, h3 {
      margin-bottom: 20px;
    }

    form label {
      font-weight: 500;
    }

    form input, form select, form button {
      width: 100%;
      padding: 10px;
      margin-top: 8px;
      margin-bottom: 20px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 14px;
    }

    form button {
      background-color: #1abc9c;
      color: white;
      border: none;
      cursor: pointer;
      transition: background 0.3s;
    }

    form button:hover {
      background-color: #16a085;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    table, th, td {
      border: 1px solid #ddd;
    }

    th, td {
      padding: 12px;
      text-align: left;
    }

    th {
      background-color: #f1f1f1;
    }

    img {
      max-width: 50px;
      height: auto;
    }

    p.success {
      color: green;
    }

    p.error {
      color: red;
    }

    @media (max-width: 768px) {
      .main-layout {
        flex-direction: column;
      }

      .sidebar {
        width: 100%;
        flex-direction: row;
        justify-content: space-around;
      }
    }
  </style>
</head>
<body>
<nav class="custom-navbar">
    <div class="logo"><img src="/images/logo1.png" alt="">HealthyBuddy</div>
    <ul class="nav-links">
        <li><a href="/patient_dashboard.php">Dashboard</a></li>
        <li><a href="/Logout.php">Logout</a></li>
        <li><a href="/userProfile.php"><?php echo $_SESSION['username']; ?></a></li>
    </ul>
</nav>

<div class="main-layout">
    <div class="sidebar">
        <a href="#"><i class="fa fa-user"></i> Upload Medicine</a>
        <a href="/Logout.php"><i class="fa fa-sign-out"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="container-style">
            <h2>Upload Medicine for Patient</h2>

            <?php if ($success): ?>
                <p style="color: green;"><?php echo $success; ?></p>
            <?php elseif ($error): ?>
                <p style="color: red;"><?php echo $error; ?></p>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
              <label>Choose Patient:</label>
              <select name="patient_id" required>
                <option value="">-- Select Patient --</option>
                <?php foreach ($patients as $pat): ?>
                <option value="<?php echo $pat['id']; ?>"
                  <?php if (!empty($_POST['patient_id']) && $_POST['patient_id'] == $pat['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($pat['username']); ?>
                </option>
                <?php endforeach; ?>
              </select><br><br>

              <label>Medicine Name:</label><br>
              <input type="text" name="name" required><br><br>

              <label>Dosage:</label><br>
              <select name="dosage" required>
                <option value="">-- Select Dosage --</option>
                <option value="1 tablet">1 tablet</option>
                <option value="2 tablets">2 tablets</option>
                <option value="5 ml">5 ml</option>
                <option value="10 ml">10 ml</option>
                <option value="Half tablet">Half tablet</option>
              </select><br><br>


              <label>Frequency:</label><br>
              <select name="frequency" required>
                <option value="">-- Select Frequency --</option>
                <option value="Once a day">Once a day</option>
                <option value="Twice a day">Twice a day</option>
                <option value="Thrice a day">Thrice a day</option>
                <option value="Every 6 hours">Every 6 hours</option>
                <option value="As needed">As needed</option>
              </select><br><br>


              <label>Meal Timing:</label><br>
              <select name="meal_timing" required>
                <option value="">-- Select Timing --</option>
                <option value="Before meal">Before meal</option>
                <option value="After meal">After meal</option>
                <option value="With meal">With meal</option>
                <option value="Empty stomach">Empty stomach</option>
              </select><br><br>


              <label>Intake Time:</label><br>
              <input type="datetime-local" name="intake_time" required><br><br>

              <label>Medicine Image:</label><br>
              <input type="file" name="image"><br><br>

              <button type="submit">Upload Medicine</button>
            </form>

            <?php if (!empty($uploaded_medicines)): ?>
              <h3 style="margin-top: 40px;">Uploaded Medicines for Selected Patient</h3>
              <table border="1" cellpadding="10" cellspacing="0">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Meal Timing</th>
                    <th>Intake Time</th>
                    <th>Status</th>
                    <th>Image</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($uploaded_medicines as $med): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($med['name']); ?></td>
                      <td><?php echo htmlspecialchars($med['dosage']); ?></td>
                      <td><?php echo htmlspecialchars($med['frequency']); ?></td>
                      <td><?php echo htmlspecialchars($med['meal_timing']); ?></td>
                      <td><?php echo date("Y-m-d H:i", strtotime($med['intake_time'])); ?></td>
                      <td><?php echo htmlspecialchars($med['status'] ?? 'Not Updated'); ?></td>
                      <td>
                        <?php if (!empty($med['image_path'])): ?>
                          <img src="<?php echo htmlspecialchars($med['image_path']); ?>" width="50">
                        <?php else: ?>
                          N/A
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
