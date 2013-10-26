<?php
MyImouto\Application::configure(function($config) {
    $config->error->report_types = E_WARNING;
    
    $config->serve_static_assets = true;
    
    $config->consider_all_requests_local = false;
    
    $config->active_record->use_cached_schema = true;
    
    $config->assets->digest = true;
});
