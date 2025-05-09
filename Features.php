<?php
session_start();
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="css/Features.css">
    <link rel="icon" href="img/EYE LOGO.png" type="image/x-icon">
    <title>Dashboard</title>
  </head>
  <body>
    
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
          <a class="navbar-brand" href="index.php">
            <img src="img/eye-removebg-preview.png" alt="Logo" height="40">
          </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav center-nav">
              <li class="nav-item">
                <a class="nav-link" href="Dashboard.php">Dashboard</a>
              </li>
              <li class="nav-item">
                <a class="nav-link active" href="Features.php">Features</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="contact.php">Contacts</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="branches.php">Branches</a>
              </li>
            </ul>
            <?php if (isset($_SESSION['username'])): ?>
                <div class="ms-auto dropdown">
                  <button class="btn btn-sign-in dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo $_SESSION['username']; ?>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="/KADILIMAN/register/logout.php">Log Out</a></li>
                  </ul>
                </div>
            <?php else: ?>
                <div class="ms-auto">
                  <a href="Registration.php" class="btn btn-sign-in">Sign In</a>
                </div>
            <?php endif; ?>
          </div>
        </div>
      </nav>
      <!-- Features Grid Section -->
<div class="features-container">
  <!-- Top-up Section -->
  <div class="section mb-5">
      <h2 class="section-title mb-4">Top-up</h2>
      <div class="feature-grid">
          <a href="Topup.php?pcType=standard" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/computer.png" class="feature-icon" alt="Standard PC">
              </div>
              <div class="feature-title">Standard PC</div>
          </a>
          <a href="Topup.php?pcType=premium" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/crown (3).png" class="feature-icon" alt="Premium PC">
              </div>
              <div class="feature-title">Premium PC</div>
          </a>
          <a href="balance.php" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/save-time.png" class="feature-icon" alt="Check Balance">
              </div>
              <div class="feature-title">Balance</div>
          </a>
          <a href="/Kadiliman/reservation/reservation.php" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/online-booking (1).png" class="feature-icon" alt="Transfer Balance">
              </div>
              <div class="feature-title">Reservation</div>
          </a>
      </div>
  </div>

  <!-- User Settings Section -->
  <div class="section mb-5">
      <h2 class="section-title mb-4">User Settings</h2>
      <div class="feature-grid">
          <a href="Settings.php#personal-info" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/profile-user%20(2).png" class="feature-icon" alt="Profile">
              </div>
              <div class="feature-title">Profile</div>
          </a>
          <a href="Settings.php#security" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/secure.png" class="feature-icon" alt="Settings">
              </div>
              <div class="feature-title">Security</div>
          </a>
          <a href="Settings.php#two-factor" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/authentication.png" class="feature-icon" alt="Security">
              </div>
              <div class="feature-title">Authentication</div>
          </a>
          <a href="Settings.php#login-management" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/profile.png" class="feature-icon" alt="Help">
              </div>
              <div class="feature-title">Management</div>
          </a>
      </div>
  </div>

  <!-- Services Section -->
  <div class="section mb-5">
      <h2 class="section-title mb-4">Services</h2>
      <div class="feature-grid">
          <a href="gaming.html" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/games.png" class="feature-icon" alt="Gaming">
              </div>
              <div class="feature-title">Gaming</div>
          </a>
          <a href="printing.html" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/printer.png" class="feature-icon" alt="Printing">
              </div>
              <div class="feature-title">Printing</div>
          </a>
          <a href="food.html" class="feature-item-card">
              <div class="feature-icon-container">
                  <img src="img/fast-food.png" class="feature-icon" alt="Food">
              </div>
              <div class="feature-title">Food</div>
          </a>
      </div>
  </div>
</div> 

    <!-- Optional JavaScript; choose one of the two! -->

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

    <!-- Option 2: Separate Popper and Bootstrap JS -->
    <!--
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js" integrity="sha384-7+zCNj/IqJ95wo16oMtfsKbZ9ccEh31eOz1HGyDuCQ6wgnyJNSYdrPa03rtR1zdB" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js" integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13" crossorigin="anonymous"></script>
    -->
  </body>
</html>