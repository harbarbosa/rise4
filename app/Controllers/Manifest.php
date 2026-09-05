<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Manifest extends Controller
{
    public function index()
    {
        $manifest = array(
            'name' => 'Intranet AlfaHP',
            'short_name' => 'AlfaHP',
            'start_url' => '/index.php',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#ffffff',
        );

        return $this->response
            ->setContentType('application/manifest+json')
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setBody(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
