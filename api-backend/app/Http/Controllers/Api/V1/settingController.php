<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

class settingController extends Controller
{
    use ApiResponseTrait;

    public function latestUpdatesUrl()
    {
        return $this->successResponse([
            'url' => 'http://127.0.0.1:8000/app/updates/latest' 
            ] , 'url for latest updates' , 200);                 
    }
//****************************************************************************** */
    public function termsOfUseUrl()
    {
        return $this->successResponse([
            'url' => 'http://127.0.0.1:8000/legal/terms-of-use'
        ], 'url for terms of use', 200);                         
    }
//****************************************************************************** */
    public function privacyPolicyUrl()
    {
        return $this->successResponse([
            'url' => 'http://127.0.0.1:8000/legal/privacy-policy'
        ], 'url for privacy policy', 200);
    }
    //******************************************************************************** */
    public function termsOfUse()
    {
        return view('setting.termsOfUse');
    }
    ///////////////////////////////////////
    public function privacyPolicy()
    {
        return view('setting.privacyPolicy');
    }
    ///////////////////////////////////////
    public function latestUpdates()
    {
        return view('setting.latestUpdates');
    }



    
}
