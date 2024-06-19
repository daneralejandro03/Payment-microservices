<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AdonisService
{

    protected $subscriptionUrl;

    public function __construct()
    {
        $this->subscriptionUrl = env('MS_NEGOCIO') . '/subscriptions/';
    }

    public function checkSubscriptionExistence($subscriptionId)
    {
        try {
            $response = Http::get($this->subscriptionUrl . $subscriptionId);
            return $response->successful();
        } catch (\Exception $e) {
            // Manejar cualquier excepción que pueda ocurrir durante la solicitud
            return false;
        }
    }
}
