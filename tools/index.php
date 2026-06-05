<?php
http_response_code(403);
header('Content-Type: text/plain; charset=utf-8');
echo 'Access denied. Debug and maintenance tools are restricted.';
exit;
