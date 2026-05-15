<?php
// Lightweight health check for AWS ALB / ECS health checks
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'timestamp' => date('c')]);
