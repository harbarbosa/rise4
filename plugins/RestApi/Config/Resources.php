<?php

namespace RestApi\Config;

use CodeIgniter\Config\BaseConfig;

class Resources extends BaseConfig
{
    public array $excluded_tables = [
        'ci_sessions',
        'migrations',
        'rise_api_users',
        'settings',
        'verification'
    ];

    public array $reserved_query_parameters = [
        'page',
        'limit',
        'sort',
        'order',
        'fields',
        'q',
        'include_deleted'
    ];

    public int $default_limit = 50;
    public int $max_limit = 200;
}
