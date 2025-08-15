<?php

    if (!defined('APP_ACCESS')) {
        http_response_code(403);
        header('location: ../template/not-found-page.php');
        exit();
    }
