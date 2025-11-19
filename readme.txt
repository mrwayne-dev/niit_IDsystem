===========================================================
NIIT STUDENT ID VERIFICATION & ID CARD GENERATION SYSTEM
Built by: Lymora Labs
===========================================================

PROJECT OVERVIEW
----------------
This system is designed to verify NIIT student records and 
automatically generate a professional, dual-sided Student ID 
Card in PDF format. It includes a frontend verification form, 
a modal-based confirmation interface, and a backend ID card 
renderer powered by PHP, GD Library, and FPDF.

The system is lightweight, fast, secure, and optimized for 
both web and local deployments.

--------------------------------
TECHNOLOGIES & DEPENDENCIES
--------------------------------
Frontend:
- HTML5 / CSS3 (custom responsive styling)
- JavaScript (ES6)
- Iconify Icons
- AJAX (fetch API)

Backend:
- PHP 8+
- FPDF Library (for PDF generation)
- PDO (MySQL Prepared Statements)

Database:
- MySQL (students table with profile + signature paths)

Assets:
- Custom fonts (Host Grotesk Regular, Bold, Italic)
- Student photo uploads
- Student signature uploads
- Auto-generated ID Card PDF

--------------------------------
PROJECT STRUCTURE
--------------------------------

root/
│
├── assets/
│   ├── css/              → Stylesheets
│   ├── js/               → Main frontend scripts
│   ├── uploads/          → Uploaded student photos/signatures
│   ├── fonts/            → Host Grotesk font family
│   └── img/              → System images (e.g., placeholder signature)
│
├── backend/
│   └── api/
│       ├── verify_id.php → Verifies student's first name, last name, ID
│       ├── download.php   → Generates ID Card PNGs + PDF
│       └── create_id.php → Creates Student Data
│
├── config/
│   ├── database.php      → PDO connection
│   ├── constants.php     → Directories + global paths
│
├── temp_pdfs/            → Auto-generated PDFs saved temporarily
│
└── index.php / verify page


--------------------------------
FEATURES
--------------------------------

1. **Student Verification**
   - Users enter First Name, Last Name, Student ID
   - System matches against MySQL database using case-insensitive rules
   - If valid → opens success modal
   - If invalid → displays an error toast

2. **Modern Success Modal**
   - Clean white-card design
   - Centered icon, text, and action button
   - Fully responsive for mobile + desktop
   - Smooth animations

3. **ID Card Generation**
   - System automatically renders:
     • FRONT side (photo, name, expiry, signature, codes, header, metadata)
     • BACK side (disclaimer, address, authorized signatory)
   - Uses GD Library to draw:
     • Shapes, rectangles, rounded bars
     • Labels + values
     • Photos with auto-resize
     • Signatures with auto-resize

4. **PDF Export**
   - FPDF packages both FRONT + BACK into a 2-page ID card
   - Saved into /temp_pdfs/
   - Download triggered automatically
   - File name pattern: NIIT_ID_<ID>.pdf

5. **Upload System Compatibility**
   - System fully supports uploaded:
     • Profile images (JPG/PNG)
     • Signature images (PNG/JPG)
   - Stored inside: assets/uploads/

6. **Secure Backend**
   - Prepared statements (PDO)
   - Sanitized input
   - Relative → absolute path conversions for images
   - Safe write operations
   - Clean JSON responses

7. **Lymora System Standards**
   - Modular API structure
   - Clean code separation (frontend, backend, config)
   - Lymora-style architecture for reliability and scalability


--------------------------------
CONFIGURATION
--------------------------------

1. Ensure these paths are correct in config/constants.php:
   BASE_PATH       → the main file directory
   ASSETS_DIR      → BASE_PATH . "assets"
   PDF_TMP_DIR     → BASE_PATH . "temp_pdfs"

2. Required PHP extensions:
   - extension=gd
   - extension=mbstring
   - extension=mysqli / pdo_mysql

3. Database:
   Table: students
   Required fields:
       first_name
       last_name
       student_id
       semester_code
       batch_code
       course
       duration
       expiry_date
       photo
       signature

4. Font files:
   assets/fonts/
      HostGrotesk-Regular.ttf
      HostGrotesk-Bold.ttf
      HostGrotesk-Italic.ttf


--------------------------------
USAGE
--------------------------------

1. Navigate to the Verify Student page
2. Enter required fields:
     • First Name
     • Last Name
     • Student ID
3. Click Verify
4. If student exists → Success Modal opens
5. Click Download ID Card PDF
6. System generates:
     • combined PDF
7. File automatically downloads


--------------------------------
CREDITS
--------------------------------
Developed by:
Lymora Labs
Nigeria
Premium Software Development & Intelligent Systems

Lead Developer:
Mr Wayne – Lymora Systems Engineer

Libraries:
- FPDF (fpdf.org)
- Iconify (iconify.design)

--------------------------------
LICENSE
--------------------------------
This project is an internal Lymora system.  
Not open-source.  
Unauthorized distribution or modification is prohibited.


===========================================================
END OF README
===========================================================
