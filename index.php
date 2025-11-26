<?php
session_start();

/* ============================================================
   1. BÖLÜM: VERİTABANI BAĞLANTISI VE OTOMATİK KURULUM
   (Bu kısım veritabanı yoksa oluşturur, varsa bağlanır)
   ============================================================ */
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "josephware_tek_dosya"; // Yeni bir isim verdim karışmasın diye

// Önce sunucuya bağlan
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) die("Bağlantı hatası: " . $conn->connect_error);

// Veritabanını oluştur (Eğer yoksa)
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);
$conn->set_charset("utf8");

// Tabloları oluştur (Eğer yoksa)
$sql_users = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(50)
)";
$conn->query($sql_users);

// Varsayılan admini ekle (Eğer yoksa)
$check_admin = $conn->query("SELECT * FROM admins");
if ($check_admin->num_rows == 0) {
    $conn->query("INSERT INTO admins (username, password) VALUES ('admin', '123456')");
}

$sql_products = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    price DECIMAL(10,2),
    icon VARCHAR(50)
)";
$conn->query($sql_products);


/* ============================================================
   2. BÖLÜM: İŞLEM MOTORU (EKLE, SİL, GİRİŞ, ÇIKIŞ)
   ============================================================ */

// GİRİŞ YAPMA
if (isset($_POST['login'])) {
    $u = $_POST['user'];
    $p = $_POST['pass'];
    $res = $conn->query("SELECT * FROM admins WHERE username='$u' AND password='$p'");
    if ($res->num_rows > 0) {
        $_SESSION['admin'] = true;
        header("Location: index.php?sayfa=admin"); // Sayfayı yenile
        exit;
    } else {
        $error = "Hatalı şifre!";
    }
}

// ÇIKIŞ YAPMA
if (isset($_GET['islem']) && $_GET['islem'] == 'cikis') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// ÜRÜN SİLME (Sadece Admin)
if (isset($_GET['sil']) && isset($_SESSION['admin'])) {
    $id = $_GET['sil'];
    $conn->query("DELETE FROM products WHERE id=$id");
    header("Location: index.php?sayfa=admin");
    exit;
}

// ÜRÜN EKLEME (Sadece Admin)
if (isset($_POST['ekle']) && isset($_SESSION['admin'])) {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $icon = $_POST['icon'];
    $conn->query("INSERT INTO products (title, price, icon) VALUES ('$title', '$price', '$icon')");
    header("Location: index.php?sayfa=admin");
    exit;
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Josephware Tek Dosya Sistemi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .container { max-width: 1000px; margin: auto; padding: 20px; }
        header { background: #004085; color: white; padding: 15px 0; text-align: center; }
        .btn { padding: 8px 15px; border-radius: 4px; text-decoration: none; color: white; display: inline-block; cursor: pointer; border: none;}
        .btn-green { background: #28a745; }
        .btn-red { background: #dc3545; }
        .btn-blue { background: #007bff; }
        
        /* Kart Tasarımı */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card i { font-size: 3rem; color: #ccc; margin-bottom: 10px; }
        
        /* Admin Formu */
        .admin-panel { background: white; padding: 20px; border-radius: 8px; margin-top: 20px; }
        input, select { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>

    <header>
        <div class="container" style="display:flex; justify-content:space-between; align-items:center;">
            <h2>CopyCenter Pro</h2>
            <div>
                <?php if (isset($_SESSION['admin'])): ?>
                    <span>Hoşgeldin Admin | </span>
                    <a href="index.php" style="color:white;">Siteyi Gör</a> | 
                    <a href="index.php?sayfa=admin" style="color:white;">Yönetim</a> | 
                    <a href="index.php?islem=cikis" style="color:#ff9999;">Çıkış</a>
                <?php else: ?>
                    <a href="index.php?sayfa=login" style="color:white;"><i class="fas fa-lock"></i> Giriş</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        
        <?php if (isset($_GET['sayfa']) && $_GET['sayfa'] == 'login'): ?>
            <div style="max-width:300px; margin:50px auto; background:white; padding:30px; border-radius:8px; text-align:center;">
                <h3>Admin Girişi</h3>
                <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
                <form method="POST">
                    <input type="text" name="user" placeholder="Kullanıcı Adı (admin)" style="width:90%"><br>
                    <input type="password" name="pass" placeholder="Şifre (123456)" style="width:90%"><br>
                    <button type="submit" name="login" class="btn btn-blue" style="width:100%; margin-top:10px;">Giriş Yap</button>
                </form>
            </div>

        <?php elseif (isset($_GET['sayfa']) && $_GET['sayfa'] == 'admin' && isset($_SESSION['admin'])): ?>
            <div class="admin-panel">
                <h3>+ Yeni Ürün Ekle</h3>
                <form method="POST">
                    <input type="text" name="title" placeholder="Ürün Adı" required>
                    <input type="number" name="price" placeholder="Fiyat" required>
                    <select name="icon">
                        <option value="fa-box">Kutu</option>
                        <option value="fa-print">Yazıcı</option>
                        <option value="fa-copy">Kağıt</option>
                    </select>
                    <button type="submit" name="ekle" class="btn btn-green">Kaydet</button>
                </form>
            </div>

            <h3 style="margin-top:30px;">Ürün Listesi (Silmek için)</h3>
            <table style="width:100%; background:white; border-collapse:collapse;">
                <tr style="background:#eee; text-align:left;"><th>ID</th><th>Ad</th><th>Fiyat</th><th>İşlem</th></tr>
                <?php
                $res = $conn->query("SELECT * FROM products ORDER BY id DESC");
                while($row = $res->fetch_assoc()) {
                    echo "<tr>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>{$row['id']}</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>{$row['title']}</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'>{$row['price']} ₺</td>
                        <td style='padding:10px; border-bottom:1px solid #eee;'><a href='index.php?sil={$row['id']}' class='btn btn-red' onclick=\"return confirm('Sil?')\">Sil</a></td>
                    </tr>";
                }
                ?>
            </table>

        <?php else: ?>
            <div class="hero" style="text-align:center; padding:50px 0;">
                <h1>Hoşgeldiniz</h1>
                <p>En kaliteli toner ve baskı ürünleri.</p>
            </div>

            <div class="grid">
                <?php
                $res = $conn->query("SELECT * FROM products");
                if ($res->num_rows > 0) {
                    while($row = $res->fetch_assoc()) {
                        echo "<div class='card'>
                            <i class='fas {$row['icon']}'></i>
                            <h3>{$row['title']}</h3>
                            <div style='color:#004085; font-weight:bold; font-size:1.2rem;'>{$row['price']} ₺</div>
                            <button class='btn btn-blue' style='width:100%; margin-top:10px;'>Sepete Ekle</button>
                        </div>";
                    }
                } else {
                    echo "<p>Henüz ürün eklenmemiş. Admin panelinden ekleyin.</p>";
                }
                ?>
            </div>
        <?php endif; ?>
        
    </div>

</body>
</html>
