<?php
require_once __DIR__ . '/backend/config/security.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/backend/config/csrf.php';

$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="NIIT Port Harcourt — Verify your Student ID Card.">
    <meta name="author" content="NIIT Port Harcourt / Lymora Labs">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://niit-ph.com/verify">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0B73CF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Verify Student ID — NIIT ID System</title>

    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body style="background-color: white;">
    <div id="wrapper">
        <div id="page">
            <div class="sign-in-wrap">
                <div class="sign-in-box" style="max-width:520px; margin:0 auto;">
                    <div class="left" style="width:100%;">
                        <div class="content">
                            <h3 class="heading text-Primary mb-8 text-center">Verify Student ID</h3>
                            <div class="sub f14-regular text-GrayDark mb-24 text-center">
                                Enter your details to verify your NIIT Student ID
                            </div>

                            <div class="sign-in-inner">
                                <form id="verify-form" class="form-login flex flex-column gap24" autocomplete="off">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                                    <fieldset>
                                        <div class="f14-regular mb-6">First Name</div>
                                        <input class="flex-grow form-control" type="text" name="first_name" placeholder="John" required>
                                    </fieldset>

                                    <fieldset>
                                        <div class="f14-regular mb-6">Last Name</div>
                                        <input class="flex-grow form-control" type="text" name="last_name" placeholder="Doe" required>
                                    </fieldset>

                                    <fieldset>
                                        <div class="f14-regular mb-6">Student ID</div>
                                        <input class="flex-grow form-control" type="text" name="student_id" placeholder="NIIT12345" required>
                                    </fieldset>

                                    <button type="submit" id="verify-btn" class="tf-button style-1 label-01 w-100 bg-Primary text-White">
                                        Verify Student ID
                                    </button>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-stamp" aria-hidden="true">
        <span>Powered by <a href="https://lymora.tech" target="_blank">Lymora</a></span>
    </div>

    <!-- Verification Result Modal -->
    <div id="verification-modal" class="custom-modal-overlay"
         role="dialog" aria-modal="true" aria-labelledby="modal-title" aria-hidden="true">
        <div class="custom-modal-box">
            <button class="btn-close-modal" onclick="closeVerifyModal()" aria-label="Close">&times;</button>

            <div class="modal-icon-circle" id="modal-icon-wrap">
                <span class="iconify" id="modal-icon" data-icon="mdi:check-circle"></span>
            </div>

            <h3 class="heading mb-8 text-center" id="modal-title">Verification Successful!</h3>

            <!-- Expiry status badge -->
            <div id="modal-expiry-badge" class="expiry-badge hidden"></div>

            <!-- Student details grid -->
            <div id="modal-student-details" class="student-details-grid"></div>

            <p class="sub f14-regular text-GrayDark mb-24 text-center" id="modal-subtext">
                We found a valid student record matching these details.
            </p>

            <button id="modal-download-btn" class="tf-button style-1 label-01 w-100 bg-Primary text-White">
                Download ID Card PDF
            </button>
        </div>
    </div>

    <div id="loader" class="hidden">
        <div class="line-loader">
            <div></div><div></div><div></div><div></div><div></div>
        </div>
    </div>
    <div id="toast-container"></div>

    <!-- Dark mode toggle -->
    <div class="theme-toggle-wrap">
        <label class="switch" title="Toggle dark mode">
            <input type="checkbox" id="dark-mode-toggle">
            <span class="slider round"></span>
        </label>
    </div>

    <script src="assets/js/ui.js"></script>
    <script src="assets/js/verify.js"></script>
    <script src="https://code.iconify.design/3/3.1.1/iconify.min.js"></script>
</body>
</html>
