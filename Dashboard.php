<?php
session_start();
?>  
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="css/dash.css">
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
                <a class="nav-link active" href="Dashboard.php">Dashboard</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="Features.php">Features</a>
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

      <div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
          <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
          <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
          <div class="carousel-item active">
            <img src="img/452231888_10226082640356058_754520079711942189_n.jpg" class="d-block w-100" alt="...">
            <div class="carousel-caption d-none d-md-block">
              <h5>First slide label</h5>
              <p>Some representative placeholder content for the first slide.</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="img/472762541_122169041432266178_2729999805257013591_n.jpg" class="d-block w-100" alt="...">
            <div class="carousel-caption d-none d-md-block">
              <h5>Second slide label</h5>
              <p>Some representative placeholder content for the second slide.</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="img/473326145_122169229328266178_4132470390666578754_n.jpg" class="d-block w-100" alt="...">
            <div class="carousel-caption d-none d-md-block">
              <h5>Third slide label</h5>
              <p>Some representative placeholder content for the third slide.</p>
            </div>
          </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
<div class="features-container mt-4">
  <div class="feature-card">
      <h2 class="mb-4">Popular</h2>
      <div class="row">
          <div class="col-md-6">
              <a href="Topup.php?pcType=standard" class="feature-item mb-4">
                  <img src="img/wallet%20(3).png" class="feature-icon me-3" alt="Online Top-up">
                  <h3 class="feature-title">Online Top-up</h3>
              </a>
              <a href="balance.php" class="feature-item mb-4">
                  <img src="img/bank-transfer.png" class="feature-icon me-3" alt="Balance Transfer">
                  <h3 class="feature-title">Balance Transfer</h3>
              </a>
              <a href="Settings.php#personal-info" class="feature-item">
                  <img src="img/profile-user%20(2).png" class="feature-icon me-3" alt="Manage Profile">
                  <h3 class="feature-title">Manage Profile</h3>
              </a>
          </div>
          
          <div class="col-md-6">
              <a href="Topup.php?pcType=premium" class="feature-item mb-4">
                  <img src="img/premium%20(1).png" class="feature-icon me-3" alt="VIP Rates">
                  <h3 class="feature-title">VIP Rates</h3>
              </a>
              <a href="/Kadiliman/reservation/reservation.php" class="feature-item mb-4">
                  <img src="img/online-booking%20(1).png" class="feature-icon me-3" alt="Reservation">
                  <h3 class="feature-title">Reservation</h3>
              </a>
              <a href="Settings.php#security" class="feature-item">
                  <img src="img/cyber-security.png" class="feature-icon me-3" alt="Change Password">
                  <h3 class="feature-title">Change Password</h3>
              </a>
          </div>
      </div>
  </div>
  
  <div class="see-more-button">
      <a href="Features.php" class="see-more-link">
          See All Features
          <img src="img/right-arrow.png" class="see-more-icon" style="width: 20px; height: 20px; margin-left: 10px;">
      </a>
  </div>
</div>

<div class="bottom-container mt-4 mb-5">
    <div class="row p-4" style="min-height: 800px; background:rgba(26, 26, 26, 0.7); border-style: solid; border-color: var(--bs-light); border-radius: 10px;" data-bs-theme="dark">
        <div class="row mb-5">
            <div class="col-md-6 col-lg-3 mb-4 d-flex justify-content-center">
                <div class="card" style="width: 250px; height: 350px;">
                    <img src="img/471601243_122182297850245795_7207469708443165469_n.jpg" class="card-img-top" alt="Card image 1" style="height: 140px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title">Gaming Events</h5>
                        <p class="card-text">Join our weekly tournaments and special gaming events for exciting prizes.</p>
                        <a href="#" class="btn btn-custom">Learn More</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4 d-flex justify-content-center">
                <div class="card" style="width: 250px; height: 350px;">
                    <img src="img/prem-pc.jpg" class="card-img-top" alt="Card image 2" style="height: 140px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title">Premium Setup</h5>
                        <p class="card-text">Experience gaming on our high-end PCs with latest hardware and peripherals.</p>
                        <a href="#" class="btn btn-custom">See Specs</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4 d-flex justify-content-center">
                <div class="card" style="width: 250px; height: 350px;">
                    <img src="img/download.jpg" class="card-img-top" alt="Card image 3" style="height: 140px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title">Food & Drinks</h5>
                        <p class="card-text">Enjoy our selection of snacks and beverages while gaming with friends.</p>
                        <a href="#" class="btn btn-custom">View Menu</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4 d-flex justify-content-center">
                <div class="card" style="width: 250px; height: 350px;">
                    <img src="img/coupon.png" class="card-img-top" alt="Card image 4" style="height: 140px; object-fit: fill;">
                    <div class="card-body">
                        <h5 class="card-title">Promos</h5>
                        <p class="card-text">Unlock amazing deals and exclusive offers with Promos! Save big on your favorite deals</p>
                        <a href="#" class="btn btn-custom">Avail Now</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h2 class="text-center mb-4">Upcoming Events</h2>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="feature-card-bottom">
                    <h3>Weekend Tournament</h3>
                    <div class="d-flex align-items-center mb-3">
                        <img src="img/471601243_122182297850245795_7207469708443165469_n.jpg" class="me-3" style="width: 50px; height: 50px;" alt="Tournament">
                        <div>
                            <p class="mb-1">Saturday, April 5, 2025 | 2:00 PM</p>
                            <p class="mb-0">Prize Pool: ₱10,000</p>
                        </div>
                    </div>
                    <p>Join our weekend Valorant tournament! Open for all skill levels with special prizes for beginners.</p>
                    <a href="events/tournament.html" class="btn btn-custom">Register Now</a>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="feature-card-bottom">
                    <h3>New Gaming Rigs</h3>
                    <div class="d-flex align-items-center mb-3">
                        <img src="img/prem-pc.jpg" class="me-3" style="width: 50px; height: 50px;" alt="Gaming PC">
                        <div>
                            <p class="mb-1">Coming April 10, 2025</p>
                            <p class="mb-0">RTX 4080 | i9 Processors</p>
                        </div>
                    </div>
                    <p>We're upgrading our premium stations with the latest hardware! Reserve your spot to be among the first to experience the next level of gaming.</p>
                    <a href="reservations/premium.html" class="btn btn-custom">Pre-book Now</a>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-lg-6 mb-4">
                <div class="feature-card-bottom">
                    <h3>Stay Updated</h3>
                    <p>Subscribe to our newsletter for exclusive offers and event updates.</p>
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Your email address" aria-label="Email">
                        <button class="btn btn-custom" type="button">Subscribe</button>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="feature-card-bottom">
                    <h3>Follow Us</h3>
                    <p>Connect with us on social media for daily updates and community highlights.</p>
                    <div class="d-flex justify-content-around mt-3">
                        <a href="#" class="btn btn-custom rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; padding: 8px;">
                            <img src="img/fb.png" alt="Facebook" style="width: 100%; height: 100%; object-fit: contain;">
                        </a>
                        <a href="#" class="btn btn-custom rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; padding: 8px;">
                            <img src="img/messenger.png" alt="Instagram" style="width: 100%; height: 100%; object-fit: contain;">
                        </a>
                        <a href="#" class="btn btn-custom rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; padding: 8px;">
                            <img src="img/tik-tok.png" alt="Discord" style="width: 100%; height: 100%; object-fit: contain;">
                        </a>
                        <a href="#" class="btn btn-custom rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; padding: 8px;">
                            <img src="img/twitter.png" alt="Twitter" style="width: 100%; height: 100%; object-fit: contain;">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
  </body>
</html>