<?php

if (isset($_SESSION['status'])) {
    $status = $_SESSION['status'];

    if ($status['success']) {
        echo "<p class=\"p3 text-success\">{$status['message']}</p>";
    } else {
        echo "<p class=\"p3 text-danger\">{$status['message']}</p>";
    }

    $_SESSION['status'] = null;
}