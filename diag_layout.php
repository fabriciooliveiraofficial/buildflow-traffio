<?php
// Dummy context
$title = "Diagnostic Dashboard";
$page = "dashboard";
$user = ['first_name' => 'Diag', 'last_name' => 'Nostic', 'email' => 'test@test.com'];
$content = "<h1>Diagnostic Content</h1>";

ob_start();
require_once "views/layouts/main_v114.php";
$rendered = ob_get_clean();

echo "<textarea style='width:100%;height:90vh;'>" . htmlspecialchars($rendered) . "</textarea>";
