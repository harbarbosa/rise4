<?php

namespace App\Controllers;

class Service_worker extends App_Controller
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
            ->setHeader('Content-Type', 'application/javascript; charset=UTF-8')
            ->setHeader('Service-Worker-Allowed', '/')
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setBody($javascript);
    }
}
