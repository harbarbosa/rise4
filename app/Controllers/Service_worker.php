<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Service_worker extends Controller
{
    public function index()
    {
        $javascript = <<<'JS'
self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});
JS;

        return $this->response
            ->setContentType('application/javascript')
            ->setHeader('Service-Worker-Allowed', '/')
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setBody($javascript);
    }
}
