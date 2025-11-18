<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta -->
    <meta charset="UTF-8">
    <meta name="description" content="HealthRunCare Charity - Support health initiatives through donations and community contributions.">
    <meta name="author" content="HealthRunCare">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://healthruncare.com/login">
    <title>HealthRunCare – Login</title>

    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
</head>

<body style="background-color: white;">
    <div id="wrapper">
        <div id="page">
            <div class="sign-in-wrap">
                <div class="sign-in-box">
                    <!-- LEFT SIDE -->
                    <div class="left">
                        <div class="content">
                            <h3 class="heading text-Primary mb-8 text-center">Create Student ID Card</h3>
                            <div class="sub f14-regular text-GrayDark mb-24 text-center">
                                Fill the form to generate the NIIT Student ID
                            </div>

                            <div class="sign-in-inner">
                                <form id="create-id-form" class="form-login flex flex-column gap24" autocomplete="off" enctype="multipart/form-data">

                                    <!-- First Name -->
                                    <fieldset>
                                        <div class="f14-regular mb-6">First Name</div>
                                        <input class="flex-grow form-control" type="text" name="first_name" placeholder="John" required>
                                    </fieldset>

                                    <!-- Last Name -->
                                    <fieldset>
                                        <div class="f14-regular mb-6">Last Name</div>
                                        <input class="flex-grow form-control" type="text" name="last_name" placeholder="Doe" required>
                                    </fieldset>

                                    <!-- Student ID -->
                                    <fieldset>
                                        <div class="f14-regular mb-6">Student ID</div>
                                        <input class="flex-grow form-control" type="text" name="student_id" placeholder="NIIT12345" required>
                                    </fieldset>

                                    <!-- Semester Code -->
                                    <fieldset>
                                        <div class="f14-regular mb-6">Semester Code</div>
                                        <input class="flex-grow form-control" type="text" name="semester_code" placeholder="SEM-2025A" required>
                                    </fieldset>

                                    <!-- Batch Code -->
                                    <fieldset>
                                        <div class="f14-regular mb-6">Batch Code</div>
                                        <input class="flex-grow form-control" type="text" name="batch_code" placeholder="BCH-21" required>
                                    </fieldset>

                                    <!-- Course -->
                                    <fieldset>
                                        <div class="f14-regular mb-6">Course</div>
                                        <input class="flex-grow form-control" type="text" name="course" placeholder="Software Engineering" required>
                                    </fieldset>

                                    <!-- Duration -->
                                    <fieldset>
                                        <div class="f14-regular mb-6">Duration</div>
                                        <input class="flex-grow form-control" type="text" name="duration" placeholder="6 Months" required>
                                    </fieldset>

                                    <!-- Expiry Date -->
                                    <fieldset>
                                        <div class="f14-regular mb-6">Expiry Date</div>
                                        <input class="flex-grow form-control" type="date" name="expiry_date" required>
                                    </fieldset>

                                    <!-- Profile Photo Upload -->
                                    <fieldset>
                                        <div class="f14-regular mb-6">Profile Photo</div>
                                        <input class="form-control" type="file" name="photo" accept="image/*" required>
                                    </fieldset>

                                    <!-- Signature Upload -->
                                    <fieldset>
                                        <div class="f14-regular mb-6">Signature</div>
                                        <input class="form-control" type="file" name="signature" accept="image/*">
                                    </fieldset>

                                    <!-- Buttons -->
                                    <button type="button" id="preview-btn" class="tf-button style-1 label-01 w-100 bg-Primary text-White">
                                        Preview ID Card
                                    </button>

                                    <button type="button" id="download-btn" class="tf-button style-1 label-01 w-100 bg-Gray disabled" disabled>
                                        Download PDF
                                    </button>

                                </form>
                            </div>
                        </div>
                    </div>


                    <!-- RIGHT SIDE -->
                    <div class="right">
                        <img src="../../assets/images/signin.png" alt="AI Health Illustration">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loader" class="hidden">
    <div class="line-loader">
        <div></div><div></div><div></div><div></div><div></div>
    </div>
    </div>
    <!-- Toast Notifications -->
    <div id="toast-container"></div>


    <!-- Scripts -->
    <script src="../../assets/js/api.js" defer></script>
    <!-- Iconify CDN -->
    <script src="https://code.iconify.design/3/3.1.1/iconify.min.js"></script>

</body>
</html>