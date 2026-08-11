<?php
session_start();
session_destroy();
header("Location: /biblioteca/login.php");
exit();