<?php
// Shop and application configuration

define('DB_HOST',  getenv('DB_HOST')  !== false ? getenv('DB_HOST')  : 'localhost');
define('DB_NAME',  getenv('DB_NAME')  !== false ? getenv('DB_NAME')  : 'gst_billing_db');
define('DB_USER',  getenv('DB_USER')  !== false ? getenv('DB_USER')  : 'root');
define('DB_PASS',  getenv('DB_PASS')  !== false ? getenv('DB_PASS')  : '');

// Empty on Render (served from root); /myproject on local XAMPP
define('BASE_URL', getenv('BASE_URL') !== false ? getenv('BASE_URL') : '/myproject');

// Shop details used on invoices and for intra/inter-state GST split
define('SHOP_NAME', 'Kaizen General Store');
define('SHOP_ADDRESS', '12, Market Road, Pune, Maharashtra - 411001');
define('SHOP_STATE', 'Maharashtra');
define('SHOP_GSTIN', '27AAAAA0000A1Z5');
define('SHOP_PHONE', '+91-9876543210');

// Indian financial year runs April (4) to March
define('FY_START_MONTH', 4);
