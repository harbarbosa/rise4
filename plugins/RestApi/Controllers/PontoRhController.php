<?php

namespace RestApi\Controllers;

use PontoRH\Libraries\PontoRh_api_service;

#[\AllowDynamicProperties]
class PontoRhController extends ModuleApiController
{
    protected PontoRh_api_service $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new PontoRh_api_service($this->api_user);
    }

    public function me()
    {
        if ($response = $this->guardPermission('pontorh_view_own')) {
            return $response;
        }

        return $this->sendResult($this->service->me());
    }

    public function status()
    {
        if ($response = $this->guardPermission('pontorh_view_own')) {
            return $response;
        }

        return $this->sendResult($this->service->status());
    }

    public function checkin()
    {
        if ($response = $this->guardPermission('pontorh_create_record')) {
            return $response;
        }

        return $this->sendResult($this->service->checkin($this->payload()));
    }

    public function today()
    {
        if ($response = $this->guardPermission('pontorh_view_own')) {
            return $response;
        }

        return $this->sendResult($this->service->today((string) $this->request->getGet('date')));
    }

    public function month()
    {
        if ($response = $this->guardPermission('pontorh_view_own')) {
            return $response;
        }

        return $this->sendResult($this->service->month(array(
            'month' => (int) $this->request->getGet('month'),
            'year' => (int) $this->request->getGet('year'),
        )));
    }

    public function history()
    {
        if ($response = $this->guardPermission('pontorh_view_own')) {
            return $response;
        }

        return $this->sendResult($this->service->history(array(
            'start_date' => (string) $this->request->getGet('start_date'),
            'end_date' => (string) $this->request->getGet('end_date'),
        )));
    }

    public function adjustments()
    {
        if ($this->request->getMethod(true) === 'POST') {
            if ($response = $this->guardPermission('pontorh_request_adjustment')) {
                return $response;
            }

            return $this->sendResult($this->service->createAdjustment($this->payload()));
        }

        if ($response = $this->guardPermission('pontorh_request_adjustment')) {
            return $response;
        }

        return $this->sendResult($this->service->adjustments());
    }

    public function registerDevice()
    {
        if (!$this->service->canAccess('pontorh_create_record') && !$this->service->canAccess('pontorh_view_own')) {
            return $this->respond(array(
                'status' => false,
                'message' => 'Forbidden.',
            ), 403);
        }

        return $this->sendResult($this->service->registerDevice($this->payload()));
    }

    public function dashboard()
    {
        if ($response = $this->guardPermission('pontorh_view_own')) {
            return $response;
        }

        return $this->sendResult($this->service->dashboard());
    }

    protected function guardPermission(string $permission)
    {
        if ($this->service->canAccess($permission)) {
            return null;
        }

        return $this->respond(array(
            'status' => false,
            'message' => 'Forbidden.',
        ), 403);
    }

    protected function sendResult(array $result)
    {
        $code = (int) ($result['code'] ?? 200);
        unset($result['ok'], $result['code']);

        return $this->respond($result, $code);
    }
}
