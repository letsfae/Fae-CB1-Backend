<?php

namespace App\Api\v1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Dingo\Api\Routing\Helpers;

class BusinessController extends Controller implements PinInterface {
    use Helpers;
    
    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function create() {}
    public function update($business_id) {}
    public function delete($business_id) {}
    public static function getPinObject($business_id, $user_id) {}

    public function getOne($business_id) {

    }

    public function getImage($business_id) {
        
    }
}
