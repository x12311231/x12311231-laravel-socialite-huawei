<?php

namespace X12311231LaravelSocialite\huawei;

use Illuminate\Support\Facades\Log;
use SocialiteProviders\Manager\SocialiteWasCalled;

class HuaweiExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('huawei', Provider::class);
    }
}
