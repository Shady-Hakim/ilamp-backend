<?php

$config = require base_path('vendor/livewire/livewire/config/livewire.php');

$config['payload']['max_nesting_depth'] = 30;

// Require authentication + throttle on the Livewire temporary file-upload endpoint.
// Without this, the /livewire/upload-file route inherits only the `web` middleware
// and is therefore reachable by unauthenticated requests.
$config['temporary_file_upload']['middleware'] = ['auth', 'throttle:10,1'];

return $config;
