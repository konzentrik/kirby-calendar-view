<?php

namespace konzentrik\CalendarView;

use Kirby\Http\Remote;

class LicenseManager
{
    private string $licenseKey;
    private int $lemonSqueezyStoreId;
    private int $lemonSqueezyProductId;

    public function __construct(private string $instanceId)
    {
        $this->licenseKey = option("konzentrik.calendarview.licenseKey", null);
        $this->lemonSqueezyStoreId = 189910;
        $this->lemonSqueezyProductId = 557759;
    }

    public function isActivated()
    {
        if (is_null($this->licenseKey) || empty($this->licenseKey)) {
            return false;
        }

        $apiCache = kirby()->cache('konzentrik.calendarview');
        $apiCacheData = $apiCache->get('license');

        if ($apiCacheData !== null) {
            return $apiCacheData === 'FULL_PLAN';
        }

        $licenseValidationResponse = Remote::post('https://api.lemonsqueezy.com/v1/licenses/validate', [
            'data' => [
                'license_key' => $this->licenseKey,
                'instance_id' => null,
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if ($licenseValidationResponse->code() === 500) {
            return false;
        }

        if ($licenseValidationResponse->code() !== 200) {
            $apiCache->set('license', 'FREE_PLAN', 1440); // cache is set in minutes => 24 hours
            return false;
        }

        $apiData = $licenseValidationResponse->json();
        $licenseStatus = isset($apiData['license_key']['status'])
            ? $apiData['license_key']['status']
            : null;

        if ($licenseStatus === null || $licenseStatus === 'disabled' || $licenseStatus === 'expired') {
            $apiCache->set('license', 'FREE_PLAN', 1440); // cache is set in minutes => 24 hours
            return false;
        }

        if ($licenseStatus === 'inactive') {
            $licenseActivationResponse = Remote::post('https://api.lemonsqueezy.com/v1/licenses/activate', [
                'data' => [
                    'license_key' => $this->licenseKey,
                    'instance_name' => $this->instanceId,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if ($licenseActivationResponse->code() === 500) {
                return false;
            }


            if ($licenseActivationResponse->code() !== 200) {
                $apiCache->set('license', 'FREE_PLAN', 1440); // cache is set in minutes => 24 hours
                return false;
            }

            $licenseActivationData = $licenseActivationResponse->json();

            if (!isset($licenseActivationData['activated']) || $licenseActivationData['activated'] === false) {
                $apiCache->set('license', 'FREE_PLAN', 1440); // cache is set in minutes => 24 hours
                return false;
            }

            // we will treat a successful activation response as validation response
            // and proceed with the check of store and product id
            $apiData = $licenseActivationData;
        }

        $storeId    = isset($apiData['meta']['store_id']) ? $apiData['meta']['store_id'] : null;
        $productId  = isset($apiData['meta']['product_id']) ? $apiData['meta']['product_id'] : null;

        $licencePlan = $productId === $this->lemonSqueezyProductId && $storeId === $this->lemonSqueezyStoreId
            ? 'FULL_PLAN'
            : 'FREE_PLAN';

        $apiCache->set('license', $licencePlan, 1440); // cache is set in minutes => 24 hours

        return $licencePlan === 'FULL_PLAN';
    }
}
