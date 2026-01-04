<?php
include_once __DIR__ . '/config.php';

// Cek apakah user sudah login
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Cek role user
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isOwner()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'owner';
}

function isKurir()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'kurir';
}

function isPegawai()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'pegawai';
}

// Redirect jika belum login
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

// Redirect jika bukan admin
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

// Redirect jika bukan owner
function requireOwner()
{
    requireLogin();
    if (!isOwner()) {
        header('Location: ../index.php');
        exit();
    }
}


// Login user
function login($email, $password)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

// Register user baru (REVISI: gunakan whatsapp)
function register($username, $email, $password, $whatsapp)
{
    global $pdo;

    // Cek email sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        return "Email sudah terdaftar";
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user baru + whatsapp (bukan phone!)
    $stmt = $pdo->prepare("INSERT INTO users (username, email, whatsapp, password) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$username, $email, $whatsapp, $hashedPassword])) {
        return true;
    }

    return "Terjadi kesalahan saat registrasi";
}
