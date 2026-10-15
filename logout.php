<?php

session_start();


// ==========================================
// CLEAR SESSION
// ==========================================

session_unset();

session_destroy();


// ==========================================
// GO TO MAIN PAGE
// ==========================================

header(
    "Location: main_page.html"
);

exit;

?>