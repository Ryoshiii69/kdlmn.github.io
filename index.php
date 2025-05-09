<?php
session_start();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="img/EYE LOGO.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="css/index.css">
    <title>Homepage current</title>
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
                <a class="nav-link" href="Features.php">Features</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="contact.php">Contacts</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="branches.php">Branches</a>
              </li>
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

      
      <div class="row" style="margin-top:80px;">
  <div class="col-md-5 text-center d-flex flex-column justify-content-center">
    <img class="img-fluid" src="img/LOGO-removebg-preview.png" style="margin: 0px; transform: scale(1.13); height: 300px; object-fit: contain;">
    <h1 style="color: rgb(255,255,255);font-size: 27px;font-family: Montserrat, sans-serif;transform: translateY(-30px);">Esports Internet Cafe</h1>
    <a class="btn btn-primary mx-auto" role="button" style="background: rgb(0,0,0);border-style: solid;border-color: rgb(255,255,255);width: 250px;border-top-left-radius: 70px;border-bottom-right-radius: 70px;height: 30px;padding: 0px;font-size: 16px;font-weight: bold;font-family: Montserrat, sans-serif;text-align: center;margin: 0px 0px;transform: translateY(-4px);" href="Registration.php">INQUIRE NOW!</a>
  </div>
  
  <div class="col-md-7" style="padding: 50px;">
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
  </div>
</div>
    
        <div class="row" style="margin-top:200px;">
            <div class="col-md-6 text-center"><img class="img-fluid" data-aos="fade-up" src="img/473326145_122169229328266178_4132470390666578754_n.jpg" style="border-radius:20px;border:1px solid rgb(255,255,255);" width="485" height="364"></div>
            <div class="col-md-6 col-lg-4">
                <h1 style="font-size: 50px;font-family: Montserrat, sans-serif;font-weight: bold;">Who We Are</h1>
                <p style="font-size: 15px;font-family: Aldrich, sans-serif;text-align: justify;"><br>Kadiliman Esports Café is one of the leading gaming hubs in the Philippines, providing a <strong>premium esports experience</strong> for casual and competitive gamers alike. With multiple branches across the country, we are committed to delivering <strong>top-tier gaming setups, high-speed internet, and a thriving esports community.</strong></p>
            </div>
        </div>
    
        <div class="adjustable-section" style="margin-top:100px;">
        <h1 class="text-center" style="font-size: 30px;transform: translateY(100px);">PC Specifications – Choose Your Setup!</h1>
        <p class="text-center" style="font-size: 12px;transform: translateY(120px);">At <strong>Kadiliman Esports Café</strong>, we offer <strong>high-performance gaming setups</strong> tailored to your needs. <br>Whether you're a casual gamer or a competitive pro, we've got the perfect rig for you!</p>
        <div class="row justify-content-center" style="transform: translateY(150px);">
            <div class="col-md-5 mb-4" style="width: 400px;">
                <div class="container" data-aos="fade-up" style="border-radius: 24px;border-width: 2px;border-style: solid;">
                    <div class="row">
                        <div class="col">
                            <h1 data-aos="fade-up" style="font-family: Aldrich, sans-serif;text-align: center;"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewbox="0 0 16 16" class="bi bi-controller"><path d="M11.5 6.027a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m-1.5 1.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1m2.5-.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m-1.5 1.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1m-6.5-3h1v1h1v1h-1v1h-1v-1h-1v-1h1v-1"></path><path d="M3.051 3.26a.5.5 0 0 1 .354-.613l1.932-.518a.5.5 0 0 1 .62.39c.655-.079 1.35-.117 2.043-.117.72 0 1.443.041 2.12.126a.5.5 0 0 1 .622-.399l1.932.518a.5.5 0 0 1 .306.729c.14.09.266.19.373.297.408.408.78 1.05 1.095 1.772.32.733.599 1.591.805 2.466.206.875.34 1.78.364 2.606.024.816-.059 1.602-.328 2.21a1.42 1.42 0 0 1-1.445.83c-.636-.067-1.115-.394-1.513-.773-.245-.232-.496-.526-.739-.808-.126-.148-.25-.292-.368-.423-.728-.804-1.597-1.527-3.224-1.527-1.627 0-2.496.723-3.224 1.527-.119.131-.242.275-.368.423-.243.282-.494.575-.739.808-.398.38-.877.706-1.513.773a1.42 1.42 0 0 1-1.445-.83c-.27-.608-.352-1.395-.329-2.21.024-.826.16-1.73.365-2.606.206-.875.486-1.733.805-2.466.315-.722.687-1.364 1.094-1.772a2.34 2.34 0 0 1 .433-.335.504.504 0 0 1-.028-.079zm2.036.412c-.877.185-1.469.443-1.733.708-.276.276-.587.783-.885 1.465a13.748 13.748 0 0 0-.748 2.295 12.351 12.351 0 0 0-.339 2.406c-.022.755.062 1.368.243 1.776a.42.42 0 0 0 .426.24c.327-.034.61-.199.929-.502.212-.202.4-.423.615-.674.133-.156.276-.323.44-.504C4.861 9.969 5.978 9.027 8 9.027s3.139.942 3.965 1.855c.164.181.307.348.44.504.214.251.403.472.615.674.318.303.601.468.929.503a.42.42 0 0 0 .426-.241c.18-.408.265-1.02.243-1.776a12.354 12.354 0 0 0-.339-2.406 13.753 13.753 0 0 0-.748-2.295c-.298-.682-.61-1.19-.885-1.465-.264-.265-.856-.523-1.733-.708-.85-.179-1.877-.27-2.913-.27-1.036 0-2.063.091-2.913.27z"></path></svg>REGULAR</h1>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col" style="background:#222222;border-radius:30px;">
                            <p data-aos="fade-up" style="text-align:center;font-size:30px;font-weight:bold;font-style:italic;font-family:Montserrat, sans-serif;border-radius:12px;"><br><span style="color:rgb(236, 236, 236);background-color:rgb(16, 18, 24);">₱20 per Hour</span><br><br></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col" data-aos="fade-up">
                            <p><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewbox="0 0 16 16" class="bi bi-cpu"><path d="M5 0a.5.5 0 0 1 .5.5V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2A2.5 2.5 0 0 1 14 4.5h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14a2.5 2.5 0 0 1-2.5 2.5v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14A2.5 2.5 0 0 1 2 11.5H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2A2.5 2.5 0 0 1 4.5 2V.5A.5.5 0 0 1 5 0m-.5 3A1.5 1.5 0 0 0 3 4.5v7A1.5 1.5 0 0 0 4.5 13h7a1.5 1.5 0 0 0 1.5-1.5v-7A1.5 1.5 0 0 0 11.5 3zM5 6.5A1.5 1.5 0 0 1 6.5 5h3A1.5 1.5 0 0 1 11 6.5v3A1.5 1.5 0 0 1 9.5 11h-3A1.5 1.5 0 0 1 5 9.5zM6.5 6a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z"></path></svg>&nbsp;AMD Ryzen 5 5600G</p>
                            <p><i class="icon ion-monitor"></i>&nbsp;180Hz Monitor</p>
                            <p><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewbox="0 0 24 24" fill="none"><path d="M5 4C5 4.55228 4.55228 5 4 5C3.44772 5 3 4.55228 3 4C3 3.44772 3.44772 3 4 3C4.55228 3 5 3.44772 5 4Z" fill="currentColor"></path><path d="M9 4C9 4.55228 8.55228 5 8 5C7.44772 5 7 4.55228 7 4C7 3.44772 7.44772 3 8 3C8.55228 3 9 3.44772 9 4Z" fill="currentColor"></path><path d="M12 5C12.5523 5 13 4.55228 13 4C13 3.44772 12.5523 3 12 3C11.4477 3 11 3.44772 11 4C11 4.55228 11.4477 5 12 5Z" fill="currentColor"></path><path d="M17 4C17 4.55228 16.5523 5 16 5C15.4477 5 15 4.55228 15 4C15 3.44772 15.4477 3 16 3C16.5523 3 17 3.44772 17 4Z" fill="currentColor"></path><path d="M20 5C20.5523 5 21 4.55228 21 4C21 3.44772 20.5523 3 20 3C19.4477 3 19 3.44772 19 4C19 4.55228 19.4477 5 20 5Z" fill="currentColor"></path><path d="M5 20C5 20.5523 4.55228 21 4 21C3.44772 21 3 20.5523 3 20C3 19.4477 3.44772 19 4 19C4.55228 19 5 19.4477 5 20Z" fill="currentColor"></path><path d="M9 20C9 20.5523 8.55228 21 8 21C7.44772 21 7 20.5523 7 20C7 19.4477 7.44772 19 8 19C8.55228 19 9 19.4477 9 20Z" fill="currentColor"></path><path d="M12 21C12.5523 21 13 20.5523 13 20C13 19.4477 12 19 12 19C11.4477 19 11 19.4477 11 20C11 20.5523 11.4477 21 12 21Z" fill="currentColor"></path><path d="M17 20C17 20.5523 16.5523 21 16 21C15.4477 21 15 20.5523 15 20C15 19.4477 15.4477 19 16 19C16.5523 19 17 19.4477 17 20Z" fill="currentColor"></path><path d="M20 21C20.5523 21 21 20.5523 21 20C21 19.4477 20.5523 19 20 19C19.4477 19 19 19.4477 19 20C19 20.5523 19.4477 21 20 21Z" fill="currentColor"></path><path d="M5 12C5.55228 12 6 11.5523 6 11C6 10.4477 5.55228 10 5 10C4.44772 10 4 10.4477 4 11C4 11.5523 4.44772 12 5 12Z" fill="currentColor"></path><path d="M20 13C20 13.5523 19.5523 14 19 14C18.4477 14 18 13.5523 18 13C18 12.4477 18.4477 12 19 12C19.5523 12 20 12.4477 20 13Z" fill="currentColor"></path><path fill-rule="evenodd" d="M0 9C0 7.34315 1.34315 6 3 6H21C22.6569 6 24 7.34315 24 9V15C24 16.6569 22.6569 18 21 18H3C1.34315 18 0 16.6569 0 15V9ZM3 8H21C21.5523 8 22 8.44772 22 9V15C22 15.5523 21.5523 16 21 16H3C2.44772 16 2 15.5523 2 15V9C2 8.44772 2.44772 8 3 8Z" fill="currentColor"></path></svg>&nbsp;16GB DDR4 RAM</p>
                            <p><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewbox="0 0 16 16" class="bi bi-gpu-card"><path d="M4 8a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0m7.5-1.5a1.5.5 0 1 0 0-1 .5.5 0 0 0 0 1m-6.5-3h1v1h1v1h-1v1h-1v-1h-1v-1h1v-1"></path><path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .5.5V4h13.5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5H2v2.5a.5.5 0 0 1-1 0V2H.5a.5.5 0 0 1-.5-.5m5.5 4a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M9 8a2.5 2.5 0 1 0 5 0 2.5 2.5 0 0 0-5 0"></path><path d="M3 12.5h3.5v1a.5.5 0 0 1-.5.5H3.5a.5.5 0 0 1-.5-.5zm4 1v-1h4v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5"></path></svg>&nbsp;No Dedicated GPU</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 mb-4" style="width: 400px;">
                <div class="container" data-aos="fade-up" style="box-shadow: 0px 0px 13px 8px var(--bs-warning);border-radius: 24px;">
                    <div class="row">
                        <div class="col">
                            <h1 data-aos="fade-up" style="font-weight: bold;font-family: Aldrich, sans-serif;text-align: center;color: var(--bs-warning);"><i class="la la-star"></i>VIP</h1>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col" style="background:#222222;border-radius:30px;">
                            <p data-aos="fade-up" style="text-align:center;font-size:30px;font-family:Montserrat, sans-serif;font-style:italic;font-weight:bold;color:rgb(255,255,255);"><br><span style="color:rgb(236, 236, 236);background-color:rgb(16, 18, 24);">₱30 per Hour</span><br><br></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col" data-aos="fade-up" style="font-size: 15px;">
                            <p><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewbox="0 0 16 16" class="bi bi-cpu"><path d="M5 0a.5.5 0 0 1 .5.5V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2A2.5 2.5 0 0 1 14 4.5h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14a2.5 2.5 0 0 1-2.5 2.5v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14A2.5 2.5 0 0 1 2 11.5H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2A2.5 2.5 0 0 1 4.5 2V.5A.5.5 0 0 1 5 0m-.5 3A1.5 1.5 0 0 0 3 4.5v7A1.5 1.5 0 0 0 4.5 13h7a1.5 1.5 0 0 0 1.5-1.5v-7A1.5 1.5 0 0 0 11.5 3zM5 6.5A1.5 1.5 0 0 1 6.5 5h3A1.5 1.5 0 0 1 11 6.5v3A1.5 1.5 0 0 1 9.5 11h-3A1.5 1.5 0 0 1 5 9.5zM6.5 6a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z"></path></svg>&nbsp;AMD Ryzen 5 5600X</p>
                            <p><i class="icon ion-monitor"></i>&nbsp;240Hz Monitor&nbsp;</p>
                            <p><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewbox="0 0 24 24" fill="none"><path d="M5 4C5 4.55228 4.55228 5 4 5C3.44772 5 3 4.55228 3 4C3 3.44772 3.44772 3 4 3C4.55228 3 5 3.44772 5 4Z" fill="currentColor"></path><path d="M9 4C9 4.55228 8.55228 5 8 5C7.44772 5 7 4.55228 7 4C7 3.44772 7.44772 3 8 3C8.55228 3 9 3.44772 9 4Z" fill="currentColor"></path><path d="M12 5C12.5523 5 13 4.55228 13 4C13 3.44772 12.5523 3 12 3C11.4477 3 11 3.44772 11 4C11 4.55228 11.4477 5 12 5Z" fill="currentColor"></path><path d="M17 4C17 4.55228 16.5523 5 16 5C15.4477 5 15 4.55228 15 4C15 3.44772 15.4477 3 16 3C16.5523 3 17 3.44772 17 4Z" fill="currentColor"></path><path d="M20 5C20.5523 5 21 4.55228 21 4C21 3.44772 20.5523 3 20 3C19.4477 3 19 3.44772 19 4C19 4.55228 19.4477 5 20 5Z" fill="currentColor"></path><path d="M5 20C5 20.5523 4.55228 21 4 21C3.44772 21 3 20.5523 3 20C3 19.4477 3.44772 19 4 19C4.55228 19 5 19.4477 5 20Z" fill="currentColor"></path><path d="M9 20C9 20.5523 8.55228 21 8 21C7.44772 21 7 20.5523 7 20C7 19.4477 7.44772 19 8 19C8.55228 19 9 19.4477 9 20Z" fill="currentColor"></path><path d="M12 21C12.5523 21 13 20.5523 13 20C13 19.4477 12 19 12 19C11.4477 19 11 19.4477 11 20C11 20.5523 11.4477 21 12 21Z" fill="currentColor"></path><path d="M17 20C17 20.5523 16.5523 21 16 21C15.4477 21 15 20.5523 15 20C15 19.4477 15.4477 19 16 19C16.5523 19 17 19.4477 17 20Z" fill="currentColor"></path><path d="M20 21C20.5523 21 21 20.5523 21 20C21 19.4477 20.5523 19 20 19C19.4477 19 19 19.4477 19 20C19 20.5523 19.4477 21 20 21Z" fill="currentColor"></path><path d="M5 12C5.55228 12 6 11.5523 6 11C6 10.4477 5.55228 10 5 10C4.44772 10 4 10.4477 4 11C4 11.5523 4.44772 12 5 12Z" fill="currentColor"></path><path d="M20 13C20 13.5523 19.5523 14 19 14C18.4477 14 18 13.5523 18 13C18 12.4477 18.4477 12 19 12C19.5523 12 20 12.4477 20 13Z" fill="currentColor"></path><path fill-rule="evenodd" d="M0 9C0 7.34315 1.34315 6 3 6H21C22.6569 6 24 7.34315 24 9V15C24 16.6569 22.6569 18 21 18H3C1.34315 18 0 16.6569 0 15V9ZM3 8H21C21.5523 8 22 8.44772 22 9V15C22 15.5523 21.5523 16 21 16H3C2.44772 16 2 15.5523 2 15V9C2 8.44772 2.44772 8 3 8Z" fill="currentColor"></path></svg>&nbsp;16GB DDR4 RAM</p>
                            <p><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewbox="0 0 16 16" class="bi bi-gpu-card"><path d="M4 8a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0m7.5-1.5a1.5.5 0 1 0 0-1 .5.5 0 0 0 0 1m-6.5-3h1v1h1v1h-1v1h-1v-1h-1v-1h1v-1"></path><path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .5.5V4h13.5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5H2v2.5a.5.5 0 0 1-1 0V2H.5a.5.5 0 0 1-.5-.5m5.5 4a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M9 8a2.5 2.5 0 1 0 5 0 2.5 2.5 0 0 0-5 0"></path><path d="M3 12.5h3.5v1a.5.5 0 0 1-.5.5H3.5a.5.5 0 0 1-.5-.5zm4 1v-1h4v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5"></path></svg>&nbsp;NVIDIA RTX 3050 GPU</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <section class="py-4 py-xl-5" style="height: 673px;transform: translateY(182px);padding: 0px;margin: 0px;text-align: center;">
            <div class="container" style="height: 18px;text-align: center;padding: 0px;">
                <div class="row" style="padding-top: 0px;text-align: center;padding-left: 133px;margin: -30px;">
                    <div class="col-xxl-2" data-aos="fade-up" data-aos-once="true" style="border-style: solid;border-radius: 28px;background: #111111;padding: 0px;margin: 15px;height: 419.3px;width: 240px;"><img src="img/471601243_122182297850245795_7207469708443165469_n.jpg" width="226" height="166" style="border-radius: 30px;border-style: solid;width: 230px;height: 170px;">
                        <p style="font-size: 19px;font-family: Aldrich, sans-serif;">GAMING EVENTS</p>
                        <p data-bss-hover-animate="bounce" style="text-align: justify;font-family: Montserrat, sans-serif;font-size: 20px;padding: 17px;">Join our weekly&nbsp;Tournaments and special gaming evens for exciting prizes</p>
                    </div>
                    <div class="col-xxl-2" data-aos="fade-up" data-aos-once="true" style="border-style: solid;border-radius: 28px;background: #111111;margin: 15px;padding: 0px;width: 240px;"><img src="img/prem-pc.jpg" width="222" height="166" style="border-style: solid;border-radius: 30px;width: 230px;height: 170px;">
                        <p style="font-size: 19px;font-family: Aldrich, sans-serif;">PREMIUM SETUP</p>
                        <p style="font-family: Montserrat, sans-serif;text-align: justify;font-size: 20px;padding: 17px;">Experience gaming on our high-end PCs with latest hardware and peripherals</p>
                    </div>
                    <div class="col-xxl-2" data-aos="fade-up" data-aos-once="true" style="border-style: solid;border-radius: 28px;background: #111111;margin: 15px;padding: 0px;width: 240px;"><img src="img/download.jpg" width="300" height="200" style="border-style: solid;border-radius: 30px;width: 230px;height: 170px;">
                        <p style="font-size: 19px;font-family: Aldrich, sans-serif;">FOODS &amp; DRINKS</p>
                        <p style="font-family: Montserrat, sans-serif;text-align: justify;font-size: 20px;padding: 17px;">Enjoy our selection of snacks and beverages while gaming with friends</p>
                    </div>
                    <div class="col-xxl-2" data-aos="fade-up" data-aos-once="true" style="border-style: solid;border-radius: 28px;background: #111111;padding: 0px;margin: 15px;width: 240px;"><img src="img/member-card.png" width="300" height="200" style="background: #00000000;border-style: solid;border-radius: 30px;width: 230px;height: 170px;">
                        <p style="font-size: 19px;font-family: Aldrich, sans-serif;">MEMBERSHIPS</p>
                        <p style="font-family: Montserrat, sans-serif;text-align: justify;font-size: 20px;padding: 17px;">Become a member for exclusive discounts and priority reservation</p>
                    </div>
                </div>
                <div class="row">
                  <div class="col">
                      <footer style="text-align: center;height: 45px;transform: translateY(0px);margin: 50px;">
                          <a href="Registration.php" class="btn btn-primary" style="background: rgb(0,0,0);border-style: solid;border-color: rgb(255,255,255);width: 333px;border-top-left-radius: 70px;border-bottom-right-radius: 70px;height: 42px;padding: 0px;font-size: 16px;font-weight: bold;font-family: Montserrat, sans-serif;text-align: center;margin: 0px 0px;display: inline-block;line-height: 42px;text-decoration: none;">BE A MEMBER NOW!!</a>
                      </footer>
                  </div>
              </div>
            </div>
            <main></main>
        </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

  </body>
</html>