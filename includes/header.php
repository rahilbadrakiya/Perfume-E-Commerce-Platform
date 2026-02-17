<?php
require_once dirname(__FILE__) . '/security_headers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Count cart items
$cart_count = 0;
if(isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo isset($page_title) ? $page_title . ' | K.M. Fragrances' : 'K.M. Fragrances | Luxury Perfumes'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600&family=Marcellus&display=swap" rel="stylesheet">
    <!-- Updated Path -->
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>

    <!-- Header / Navbar -->
    <header class="site-header">
        <div class="header-inner">
            <!-- Mobile Menu Toggle -->
            <button class="menu-toggle" onclick="toggleMobileMenu()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <!-- Desktop Nav -->
            <nav class="nav-left desktop-only">
                <a href="index.php">Home</a>
                <a href="shop.php">Shop All</a>
                <a href="shop.php?category=Men">Men</a>
                <a href="shop.php?category=Women">Women</a>
                <a href="shop.php?category=Unisex">Unisex</a>
            </nav>

            <div class="logo-center">
                <a href="index.php">K.M. FRAGRANCES</a>
            </div>

            <div class="icons-right">
                <!-- Search Icon -->
                <div class="icon-btn search-icon" onclick="toggleSearch()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>

                <!-- Cart Icon -->
                <div class="icon-btn cart-icon-wrapper" onclick="toggleCart()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Drawer -->
        <div class="mobile-nav" id="mobileNav">
            <a href="index.php" onclick="toggleMobileMenu()">Home</a>
            <a href="shop.php" onclick="toggleMobileMenu()">Shop All</a>
            <a href="shop.php?category=Men" onclick="toggleMobileMenu()">Men</a>
            <a href="shop.php?category=Women" onclick="toggleMobileMenu()">Women</a>
            <a href="contact.php" onclick="toggleMobileMenu()">Contact</a>
        </div>
        
        <!-- Search Bar Overlay (Hidden by default) -->
        <div id="searchOverlay" style="display:none; position:absolute; top:70px; left:0; width:100%; background:#fff; padding:15px; border-bottom:1px solid #eee; justify-content:center;">
             <form action="shop.php" method="GET" style="display:flex; width:100%; max-width:600px; gap:10px;">
                <input type="text" name="search" placeholder="Search for perfumes..." style="flex:1; padding:10px; border:1px solid #ccc;">
                <button type="submit" style="padding:10px 20px; background:#000; color:#fff; border:none;">SEARCH</button>
             </form>
        </div>
    </header>
