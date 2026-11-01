<?php
if (!defined('BASE_URL')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/config/database.php';
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/security.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/functions.php';

$csrf_token = getCsrfToken();

// Logo auto-detect
$headerLogoPath = '/assets/images/snhs-logo.png';
$headerLogoFull = $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system' . $headerLogoPath;
if (!file_exists($headerLogoFull)) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/assets/images/snhs-logo.jpg')) {
        $headerLogoPath = '/assets/images/snhs-logo.jpg';
    }
}
$headerLogoUrl = BASE_URL . $headerLogoPath;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>SNHS Wellness | Mental Health & Counseling Support</title>
    
    <link rel="icon" type="image/png" href="<?php echo $headerLogoUrl; ?>?v=5">
    <link rel="shortcut icon" type="image/png" href="<?php echo $headerLogoUrl; ?>?v=5">
    <link rel="apple-touch-icon" href="<?php echo $headerLogoUrl; ?>?v=5">
    
    <meta name="description" content="Sulu National High School - Student Mental Health & Counseling Support System.">
    <meta name="author" content="Sulu National High School">
    <meta name="theme-color" content="#7C3AED">
    
    <meta property="og:title" content="SNHS Wellness - Mental Health & Counseling Support">
    <meta property="og:description" content="A safe and supportive space for Sulu National High School students.">
    <meta property="og:image" content="<?php echo $headerLogoUrl; ?>">
    <meta property="og:type" content="website">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=5">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/sidebar.css?v=5">
</head>
<body>
<div class="app-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/student-mental-health-system/includes/sidebar.php'; ?>
    <div class="app-content">
        <div class="main-wrapper">