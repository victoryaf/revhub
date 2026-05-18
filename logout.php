<?php
session_start();
session_destroy();
header('Location: /revhub/index.php');
exit;
?>