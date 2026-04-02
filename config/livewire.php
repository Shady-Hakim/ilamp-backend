<?php

$config = require base_path('vendor/livewire/livewire/config/livewire.php');

$config['payload']['max_nesting_depth'] = 30;

return $config;
