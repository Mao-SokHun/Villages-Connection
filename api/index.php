<?php

/**
 * Vercel serverless entrypoint — routes all dynamic requests through public/router.php.
 */

chdir(dirname(__DIR__) . '/public');

require dirname(__DIR__) . '/public/router.php';
