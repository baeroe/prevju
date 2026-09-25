<?php

use App\Mcp\Servers\PrevjuServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', PrevjuServer::class)->middleware(['auth:sanctum', 'throttle:120,1']);
