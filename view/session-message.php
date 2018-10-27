<?php

if (isset($_SESSION['status'])) {
    $status = $_SESSION['status'];

    if ($status['success']) {
        echo "<p class=\"p3 text-success\" id=\"message\">{$status['message']}</p>";
    } else {
        echo "<p class=\"p3 text-danger\" id=\"message\">{$status['message']}</p>";
    }

    $_SESSION['status'] = null;
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];

    if ($error['message']) {
        echo "<p class=\"p3 text-danger\" id=\"message\">{$error['message']}</p>";
    }

    $_SESSION['error'] = null;
}