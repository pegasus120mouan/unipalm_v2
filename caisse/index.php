<?php
// Prevent directory listing of /pages/
http_response_code(403);
header('Content-Type: text/plain; charset=UTF-8');
echo "403 Forbidden";
exit;
