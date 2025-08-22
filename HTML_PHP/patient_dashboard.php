<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: /HTML_PHP/Login.php");
    exit();
}

include('db.php'); // DB connection

$patient_id = $_SESSION['user_id'];

// Fetch today's medicines for the patient
$sql = "SELECT * FROM medicines WHERE patient_id = ? AND DATE(intake_time) = CURDATE() ORDER BY intake_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$medicines = [];
while ($row = $result->fetch_assoc()) {
    $medicines[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Patient Dashboard - HealthBuddy</title>
  <link rel="stylesheet" href="/CSS/Dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
  <style>
    .medicine-image {
      width: 150px;
      height: 150px;
      object-fit: contain;
      border-radius: 8px;
      border: 1px solid #ddd;
      margin-bottom: 10px;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="custom-navbar">
    <div class="logo"><img src="/images/logo1.png" alt="">HealthyBuddy</div>
    <ul class="nav-links">
      <li><a href="/HTML_PHP/patient_dashboard.php">Dashboard</a></li>
      <li><a href="/HTML_PHP/index.php">Logout</a></li>
      <li><a href="/HTML_PHP/userProfile.php"><?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
    </ul>
  </nav>

  <div class="main-layout">
    <!-- Sidebar -->
    <div class="sidebar">
      <a href="#"><i class="fa fa-book" style="color: #3c8dbc;"></i> Today Medicines</a>
      <a href="/HTML_PHP/UploadMedi.html"><i class="fa fa-upload" style="color: #f39c12;"></i> Upload Medicines</a>
      <a href="/HTML_PHP/voiceNotification.html"><i class="fa fa-bell" style="color: #00c0ef;"></i> Voice/Timer</a>
      <a href="/HTML_PHP/History.html"><i class="fa fa-history" style="color: #d9534f;"></i> History</a>
      <a href="/HTML_PHP/Logout.php"><i class="fa fa-sign-out"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="container-style">
        <div class="headDesign">
          <img src="/images/images.png" alt="">
          <h2 style="padding-top: 50px;"><u>Today's Medicines Overview</u></h2>
        </div>

        <?php if (empty($medicines)) : ?>
          <p style="text-align: center; font-size: 18px; margin-top: 50px;">No medicines scheduled for today.</p>
        <?php else : ?>
          <?php foreach ($medicines as $medicine) : ?>
            <div class="card-row" style="margin: 5%; align-items: center;">
              <!-- Medicine Image -->
              <div class="card-box" style="flex: 1; max-width: 180px;">
                <?php if (!empty($medicine['image_path']) && file_exists($medicine['image_path'])): ?>
                  <img src="<?php echo htmlspecialchars($medicine['image_path']); ?>" alt="Medicine Image" class="medicine-image">
                <?php else: ?>
                  <img src="/images/aspirin.jpeg" alt="Medicine Image" class="medicine-image">
                <?php endif; ?>
                <div class="card-content" style="display: flex; justify-content: space-between; font-size: 18px;">
                  <span>Status:</span>
                  <span class="status-taken"><?php echo htmlspecialchars($medicine['status'] ?: 'Not Updated'); ?></span>
                </div>
              </div>

              <!-- Medicine Details -->
              <div class="side-container" style="flex: 3; padding-left: 20px;">
                <h2><span style="color: #d9534f;">Drug name:</span> <?php echo htmlspecialchars($medicine['name']); ?></h2><br>
                <hr>
                <div class="card-row space" style="display: flex; gap: 40px;">
                  <h4>Meal Timing</h4>
                  <h4>Frequency</h4>
                  <h4>Dosage</h4>
                </div>
                <div class="card-row space" style="display: flex; gap: 40px;">
                  <blockquote><?php echo htmlspecialchars($medicine['meal_timing']); ?></blockquote>
                  <blockquote><?php echo htmlspecialchars($medicine['frequency']); ?></blockquote>
                  <blockquote><?php echo htmlspecialchars($medicine['dosage']); ?></blockquote>
                </div>

                <!-- Update Status Form -->
                <div>
                  <h4 style="color: #d9534f; margin-top: 20px;">Update Your Status:</h4>
                </div>
                <form action="/HTML_PHP/update_status.php" method="post" class="btnRem" style="display: flex; gap: 10px;">
                  <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                  <button class="btn btnTaken" name="status" value="Taken" type="submit">Taken</button>
                  <button class="btn btnMissed" name="status" value="Missed" type="submit">Missed</button>
                </form>
              </div>
            </div>
            <hr style="margin: 20px 0;">
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>

</html>
