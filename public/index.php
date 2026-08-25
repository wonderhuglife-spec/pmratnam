<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
pmr_send_security_headers();
http_response_code(200);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PMRatnam</title>
</head>
<body>
    <p>Public CMS routing is not enabled yet. Existing HTML files at the site root still serve the live pages.</p>
</body>
</html>
