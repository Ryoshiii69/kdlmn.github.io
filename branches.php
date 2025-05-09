<?php
session_start();
if (!empty($_SESSION['username'])) {
    $displayName = $_SESSION['username'];
} elseif (!empty($_SESSION['firstname'])) {
    $displayName = $_SESSION['usernamname']; 
} else {
    $displayName = "Guest";
}
?>  
<!DOCTYPE html>
<html data-bs-theme="light" lang="en" data-bss-forced-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kadiliman - All Locations</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Titillium+Web:400,600,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/Kadiliman/css/dash.css">
    <link rel="stylesheet" href="/Kadiliman/css/branch.css">
    <link rel="icon" href="img/EYE LOGO.png" type="image/x-icon">

</head>
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
                <a class="nav-link active"  href="branches.php">Branches</a>
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

    <div class="page-container">
        <h1 class="section-title">Our Locations</h1>
        <div class="featured-branch">
            <div class="featured-content">
                <div class="featured-label">NEWEST BRANCH</div>
                <h2 class="featured-title">Makati - Ayala Triangle</h2>
                <p class="featured-desc">Our flagship location with 80 premium gaming stations, VR zone, and 24/7 service. Experience gaming at its finest with our latest top-tier equipment.</p>
                <a href="#" class="featured-btn">Explore Branch</a>
            </div>
        </div>
        <h2 class="section-subtitle">Find Us Near You</h2>
        <div id="map-container" style="height: 450px;">
        </div>
        <div class="branches-container" id="branches-container">

        </div>
        <div>
            <h3 class="section-title">Location Information</h3>
            <div class="card" style="background-color: rgba(26, 26, 26, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <ul style="list-style-type: none; padding-left: 0;">
                    <li style="margin-bottom: 10px;">• All our branches offer both Standard and Premium gaming stations</li>
                    <li style="margin-bottom: 10px;">• Your account and unused time can be used at any branch</li>
                    <li style="margin-bottom: 10px;">• Free Wi-Fi is available for all customers</li>
                    <li style="margin-bottom: 10px;">• Tournament bookings require at least 48 hours advance notice</li>
                    <li style="margin-bottom: 10px;">• Group discounts are available for parties of 5 or more</li>
                </ul>
            </div>
        </div>
    </div>
    <script>
        const branchData = [
            {
                id: 1,
                name: "Manila - Taft Avenue",
                address: "Kadiliman Esports Cafe Taft, University Mall, 2507 Taft Ave, Malate, Manila, 1004 Metro Manila",
                hours: "24/7",
                features: ["Premium PCs", "Tournament Area", "Snack Bar"],
                coordinates: {lat: 14.5833, lng: 120.9825},
                image: "/api/placeholder/400/180",
                status: "Open",
                openSince: "2024-05-15"
            },
            {
                id: 2,
                name: "Anonas - Katipunan",
                address: "Kadiliman Esports Cafe Anonas, 2b Molave, Project 3, Quezon City, 1102 Metro Manila",
                hours: "24/7",
                features: ["Premium PCs", "Basic PCs", "Café"],
                coordinates: {lat: 14.6285292, lng: 121.0632741},
                image: "/api/placeholder/400/180",
                status: "Open",
                openSince: "2022-02-10"
            },
            {
                id: 3,
                name: "Valenzuela - Marulas",
                address: "MXHJ+26G Kadiliman Esports Cafe Valenzuela, Valenzuela, Metro Manila",
                hours: "24/7",
                features: ["Premium PCs", "Tournament Area", "Café"],
                coordinates: {lat: 14.6775606, lng: 120.976063},
                image: "/api/placeholder/400/180",
                status: "Open",
                openSince: "2023-11-20"
            },
            {
                id: 4,
                name: "España - Arsenio H",
                address: "Kadiliman Esports Cafe España, 910 Lacson Ave, Sampaloc, Manila, 1008 Metro Manila",
                hours: "24/7",
                features: ["Premium PCs", "Snack Bar"],
                coordinates: {lat: 14.610068, lng: 120.992712},
                image: "/api/placeholder/400/180",
                status: "Open",
                openSince: "2022-07-05"
            },
            {
                id: 5,
                name: "Gastambide - Dalupan",
                address: "Kadiliman Esports Cafe Gastambide, 622 Dalupan St, Sampaloc, Manila, 1008 Metro Manila",
                hours: "24/7",
                features: ["Premium PCs", "Tournament Area", "Café"],
                coordinates: {lat: 14.602432, lng: 120.990599},
                image: "/api/placeholder/400/180",
                status: "Open",
                openSince: "2023-03-15"
            },
            {
                id: 6,
                name: "Taguig - Comembo",
                address: "Kadiliman Esports Cafe Comembo, Sweetlab Bldg., 22 Sampaguita St, Taguig, 1217 Metro Manila",
                hours: "24/7",
                features: ["Premium PCs", "Tournament Area", "Café"],
                coordinates: {lat: 14.54846481822575, lng: 121.06366808269493},
                image: "/api/placeholder/400/180",
                status: "Open",
                openSince: "2023-03-15"
            }
        ];  

        document.addEventListener('DOMContentLoaded', function() {
        
            const mapContainer = document.getElementById('map-container');
            const map = L.map(mapContainer).setView([14.561175, 121.057985], 12); 
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);


            branchData.forEach(branch => {
                const marker = L.marker([branch.coordinates.lat, branch.coordinates.lng]).addTo(map);
                marker.bindPopup(`
                    <strong>${branch.name}</strong><br>
                    ${branch.address}<br>
                    <i class="fas fa-clock"></i> ${branch.hours}<br>
                    <a href="#" onclick="getDirections(${branch.id})">Get Directions</a>
                `);
            });
        });


        function getDirections(branchId) {
            const branch = branchData.find(b => b.id === branchId);
            if (branch) {
                const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(branch.address)}`;
                window.open(mapsUrl, '_blank');
            }
        }

    
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
        });

        
        function renderBranchCards(branches) {
            const container = document.getElementById('branches-container');
            container.innerHTML = '';

            branches.forEach(branch => {
            
                const branchCard = document.createElement('div');
                branchCard.className = 'branch-card';
                branchCard.innerHTML = `
                    <div class="branch-details">
                        <h3 class="branch-name">${branch.name}</h3>
                        <div class="branch-info">
                            <div><i class="fas fa-map-marker-alt"></i> ${branch.address}</div>
                            <div><i class="fas fa-clock"></i> ${branch.hours}</div>
                        </div>
                        <div class="branch-features">
                            ${branch.features.map(feature => `<span class="feature-badge">${feature}</span>`).join('')}
                        </div>
                        <div class="branch-actions">
                            <a href="#" class="branch-btn btn-directions" onclick="getDirections(${branch.id})">
                                <i class="fas fa-directions"></i> Directions
                            </a>
                        </div>
                    </div>
                `;
                container.appendChild(branchCard);
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
        
            renderBranchCards(branchData);
            
            document.getElementById('branch-search').addEventListener('input', function() {
                renderBranchCards(filterBranches());
            });
            
            document.getElementById('facility-filter').addEventListener('change', function() {
                renderBranchCards(filterBranches());
            });
            
            document.getElementById('sort-filter').addEventListener('change', function() {
                renderBranchCards(filterBranches());
            });
        });

    </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>