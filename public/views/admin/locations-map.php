<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

// Fetch all locations with coordinates
$locations = executeQuery("SELECT id, name, address, latitude, longitude FROM locations WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Locations Map | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        #map {
            height: 600px;
            width: 100%;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/admin-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Locations Map</h1>
                    <a href="view-location.php" class="btn btn-outline">
                        <i data-lucide="arrow-left"></i> Back to Locations List
                    </a>
                </div>

                <div id="map"></div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        var map = L.map('map').setView([0, 0], 2);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var locations = <?php echo json_encode($locations); ?>;

        if (locations.length > 0) {
            var bounds = [];

            locations.forEach(function(location) {
                if (location.latitude && location.longitude) {
                    var marker = L.marker([location.latitude, location.longitude]).addTo(map);
                    marker.bindPopup('<b>' + location.name + '</b><br>' + location.address + '<br><a href="view-location.php?id=' + location.id + '">View Details</a>');
                    bounds.push([location.latitude, location.longitude]);
                }
            });

            if (bounds.length > 0) {
                map.fitBounds(bounds);
            }
        } else {
            alert('No locations with coordinates found.');
        }
    </script>
</body>
</html>
