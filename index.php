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
                            <h3 class="heading text-Primary mb-8 text-center">Create Student ID Card</h3>
                            <div class="sub f14-regular text-GrayDark mb-24 text-center">
                                Fill the form to generate the NIIT Student ID
                            </div>

                            <div class="sign-in-inner">
                                <form id="create-id-form" class="form-login flex flex-column gap24" autocomplete="off" enctype="multipart/form-data">

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

                                    <fieldset>
                                        <div class="f14-regular mb-6">Semester Code</div>
                                        <input class="flex-grow form-control" type="text" name="semester_code" placeholder="SEM-2025A" required>
                                    </fieldset>

                                    <fieldset>
                                        <div class="f14-regular mb-6">Batch Code</div>
                                        <input class="flex-grow form-control" type="text" name="batch_code" placeholder="BCH-21" required>
                                    </fieldset>

                                    <fieldset>
                                        <div class="f14-regular mb-6">Course</div>
                                        <input class="flex-grow form-control" type="text" name="course" placeholder="Software Engineering" required>
                                    </fieldset>

                                    <fieldset>
                                        <div class="f14-regular mb-6">Duration</div>
                                        <input class="flex-grow form-control" type="text" name="duration" placeholder="6 Months" required>
                                    </fieldset>

                                    <fieldset>
                                        <div class="f14-regular mb-6">Expiry Date</div>
                                        <input class="flex-grow form-control" type="date" name="expiry_date" required>
                                    </fieldset>

                                    <fieldset>
                                        <div class="f14-regular mb-6">Profile Photo</div>
                                        <input class="form-control" type="file" name="photo" accept="image/*" required>
                                    </fieldset>

                                    <fieldset>
                                        <div class="f14-regular mb-6">Signature</div>
                                        <input class="form-control" type="file" name="signature" accept="image/*">
                                    </fieldset>

                                    <button type="button" id="process-btn" class="tf-button style-1 label-01 w-100 bg-Primary text-White">
                                        Generate ID Card
                                    </button>

                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="right d-flex justify-content-center align-items-start">
                    <div class="preview-wrapper flex-column gap-4" style="display: flex; flex-direction: column; gap: 30px;">
                        
                        <div id="id-card-preview" class="id-card shadow-sm">
                            <div class="id-header text-center">
                                <span class="niit-bold">NIIT</span>
                                <span class="niit-location">Port Harcourt</span>
                            </div>

                            <div class="id-subheader text-center">STUDENT IDENTITY CARD</div>

                            <div class="photo-row">
                                <div class="id-expiry">
                                    <div class="rotate-text">
                                        Expiry Date: <span id="preview-expiry">DEC, 2027</span>
                                    </div>
                                </div>

                                <div class="photo-center">
                                    <div class="id-photo">
                                        <img id="preview-photo" src="assets/img/placeholder.png" alt="Profile">
                                    </div>
                                </div>
                            </div>

                            <div class="id-name text-center mt-2" id="preview-fullname">JOHN DOE</div>

                            <div class="id-signature-box">  
                                <img id="preview-signature" src="" class="signature-img">
                                <div class="signature-label">Holder's Signature</div>
                            </div>

                            <div class="id-info-block"><b>STUDENT ID:</b> <span id="preview-studentid">NIIT12345</span></div>
                            <div class="id-info-block"><b>Semester Code:</b> <span id="preview-semester">SEM-2025A</span></div>
                            <div class="id-info-block"><b>Batch Code:</b> <span id="preview-batch">BCH-21</span></div>
                            <div class="id-info-block"><b>Course:</b> <span id="preview-course">Software Engineering</span></div>
                            <div class="id-info-block"><b>Duration:</b> <span id="preview-duration">6 Months</span></div>
                        </div>

                        <div id="id-card-back-preview" class="id-card shadow-sm">
                            <div class="back-content">
                                <div class="disclaimer-section">
                                    <p>This card is issued for identification of the holder whose name, photograph and signature appear on the reverse side.</p>
                                    <p>This card is NIIT Port Harcourt property and remains valid for the period stated overleaf.</p>
                                </div>

                                <div class="address-section">
                                    <h4 class="address-title">NIIT Education & Training Centre</h4>
                                    <p>
                                        1, Kaduna Street, D/Line,<br>
                                        Port Harcourt, Rivers State.<br>
                                        Tel/Fax: 234-084-230997
                                    </p>
                                </div>

                                <div class="auth-signatory-section">
                                    <img src="assets/img/auth_signature_placeholder.png" alt="Auth Signature" class="auth-sig-img">
                                    <div class="auth-label">Authorized Signatory</div>
                                </div>
                            </div>
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

    <div id="loader" class="hidden">
        <div class="line-loader">
            <div></div><div></div><div></div><div></div><div></div>
        </div>
    </div>
    <div id="toast-container"></div>

    <script src="assets/js/ui.js"></script>
    <script src="assets/js/verify.js"></script>
    <script src="assets/js/create.js"></script> 
    <script src="https://code.iconify.design/3/3.1.1/iconify.min.js"></script>

</body>
</html>