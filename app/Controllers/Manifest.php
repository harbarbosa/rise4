<?php

namespace App\Controllers;

class Manifest extends App_Controller
{
    public function index()
    {
        $manifest = array(
            'name' => 'Intranet AlfaHP',
            'short_name' => 'AlfaHP',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#ffffff',
        );

        return $this->response
            ->setHeader('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->setBody(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
