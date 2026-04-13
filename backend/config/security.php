<?php
// Security headers — included at the top of every entry point
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://code.iconify.design; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self' https://api.iconify.design https://api.unisvg.com https://api.simplesvg.com");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
