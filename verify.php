<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="HealthRunCare Charity - Support health initiatives through donations and community contributions.">
    <meta name="author" content="HealthRunCare">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://healthruncare.com/login">
    <title>NIIT Digital ID System</title>

    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body style="background-color: white;">
    <div id="wrapper">
        <div id="page">
            <div class="sign-in-wrap">
                <div class="sign-in-box">
                    <div class="left">
                        <div class="content">
                            <h3 class="heading text-Primary mb-8 text-center">Verify Student ID</h3>
                            <div class="sub f14-regular text-GrayDark mb-24 text-center">
                                Fill the form to verify your NIIT Student ID
                            </div>

                            <div class="sign-in-inner">
                                <form id="verify-form" class="form-login flex flex-column gap24" autocomplete="off">

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
                                        Verify & Retrieve Student ID
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


    <div id="verification-modal" class="custom-modal-overlay">
        <div class="custom-modal-box">
            <button class="btn-close-modal" onclick="closeVerifyModal()">&times;</button>
            
            <div class="modal-icon-circle">
                <span class="iconify" data-icon="mdi:check-circle"></span>
            </div>
            
            <h3 class="heading text-Primary mb-8">Verification Successful!</h3>
            <p class="sub f14-regular text-GrayDark mb-24">
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

    <script src="assets/js/ui.js"></script>
    <script src="assets/js/verify.js"></script>
    <script src="https://code.iconify.design/3/3.1.1/iconify.min.js"></script>

</body>
</html>