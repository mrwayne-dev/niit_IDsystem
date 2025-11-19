<?php
define('BASE_PATH', dirname(dirname(dirname(__FILE__)))); 
define('ASSETS_DIR', BASE_PATH . '/assets');
define('IMG_DIR', ASSETS_DIR . '/img');
define('UPLOAD_DIR', ASSETS_DIR . '/uploads'); 
define('PDF_TMP_DIR', BASE_PATH . '/temp_pdfs');
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
if (!is_dir(PDF_TMP_DIR)) mkdir(PDF_TMP_DIR, 0777, true);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);
define('MAX_FILE_SIZE', 2 * 1024 * 1024);