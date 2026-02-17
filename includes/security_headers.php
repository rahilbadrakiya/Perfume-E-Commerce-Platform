<?php
// Security Headers
header("X-Frame-Options: SAMEORIGIN"); // Prevent Clickjacking
header("X-XSS-Protection: 1; mode=block"); // XSS Protection
header("X-Content-Type-Options: nosniff"); // Prevent MIME-type sniffing
header("Referrer-Policy: strict-origin-when-cross-origin"); // Referrer Policy
header("Content-Security-Policy: default-src 'self' https: 'unsafe-inline' 'unsafe-eval'; img-src 'self' data: https:; font-src 'self' https: data:;"); // Basic CSP
?>
