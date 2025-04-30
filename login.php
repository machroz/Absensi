<?php
session_start();

// config database
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'absensi_lokasi';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Koneksi gagal: ' . $conn->connect_error);
}

// proses login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM siswa WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['siswa_id'] = $row['id'];
            $_SESSION['nama'] = $row['nama'];
            header("Location: ?page=absen");
            exit;
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Username tidak ditemukan.";
    }
}

// proses logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// proses absen
if (isset($_POST['absen'])) {
    $id_siswa = $_SESSION['siswa_id'];
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    $jarak = $_POST['jarak'];
    $date = date('Y-m-d');
    $time = date('H:i:s');

    $stmt = $conn->prepare("INSERT INTO absensi (id_siswa, tanggal, jam, latitude, longitude, jarak) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdd", $id_siswa, $date, $time, $lat, $lng, $jarak);
    $stmt->execute();

    echo "<script>alert('Absensi berhasil!');window.location='?page=absen';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Absensi Lokasi Siswa</title>
    <style>
        body { font-family: Arial; padding: 20px; background-color: #f9f9f9; }
        h2 { color: #333; }
        input, button { margin: 5px 0; padding: 10px; width: 100%; max-width: 300px; }
        button { background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        a { text-decoration: none; color: red; }
    </style>
</head>
<body>
<?php if (!isset($_SESSION['siswa_id'])): ?>
    <h2>Login Siswa</h2>
    <?php if (isset($error)) echo '<p style="color:red">'.$error.'</p>'; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit" name="login">Login</button>
		<?php
echo password_hash('123456', PASSWORD_BCRYPT);
?>

    </form>
<?php else: ?>
    <h2>Halo, <?php echo $_SESSION['nama']; ?>!</h2>
    <button onclick="getLocation()">Absen Sekarang</button>
    <p id="status"></p>

    <form id="absenForm" method="POST">
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">
        <input type="hidden" name="jarak" id="jarak">
        <input type="hidden" name="absen" value="1">
    </form>
    <br>
    <a href="?logout">Logout</a>

    <script>
        const latSekolah = -6.905977;
        const lngSekolah = 107.613144;

        function getDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3;
            const φ1 = lat1 * Math.PI/180;
            const φ2 = lat2 * Math.PI/180;
            const Δφ = (lat2-lat1) * Math.PI/180;
            const Δλ = (lon2-lon1) * Math.PI/180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            return R * c;
        }

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    const distance = getDistance(latSekolah, lngSekolah, lat, lon);

                    if (distance <= 200) {
                        document.getElementById("latitude").value = lat;
                        document.getElementById("longitude").value = lon;
                        document.getElementById("jarak").value = distance.toFixed(2);
                        document.getElementById("absenForm").submit();
                    } else {
                        document.getElementById("status").innerText = "Lokasi terlalu jauh dari sekolah.";
                    }
                }, function() {
                    document.getElementById("status").innerText = "Gagal mengambil lokasi.";
                });
            } else {
                document.getElementById("status").innerText = "Browser tidak mendukung Geolokasi.";
            }
        }
    </script>
<?php endif; ?>
</body>
</html>
