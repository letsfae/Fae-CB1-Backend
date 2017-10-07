<?php

namespace App\Api\v1\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use App\Api\v1\Controllers\PinOperationController;
use App\Users;
use App\Places;
use App\Locations;
use App\ChatRooms;
use App\Collections;
use App\Collection_of_pins;
use Auth;
use Validator;
use DB;

use App\Api\v1\Utilities\ErrorCodeUtility;
use App\Api\v1\Utilities\PinUtility;

class CollectionController extends Controller {
    use Helpers;
    
    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function createOne() {
        $this->createOneValidation($this->request);
        $collection = new Collections();
        $collection->user_id = $this->request->self_user_id;
        $collection->name = $this->request->name;
        $collection->description = $this->request->description;
        $collection->type = $this->request->type;
        $collection->is_private = $this->request->is_private == 'true' ? true : false;
        $collection->save();
        return $this->response->created(null, array('collection_id' => $collection->id));
    }

    public function getAll() {
        $user = Users::find($this->request->self_user_id);
        $collections = $user->hasManyCollections;
        $result = array();
        foreach ($collections as $collection) {
            $result[] = $this->collectionFormatting($collection);
        }
        return array($result);
    }

    public function updateOne($collection_id) {
        $this->updateOneValidation($this->request);
        $result = PinUtility::validation('collection', $collection_id);
        if($result[0]) {
            return $result[1];
        }
        $collection = $result[1];
        if($collection->user_id != $this->request->self_user_id) {
            return response()->json([
                    'message' => 'You can not update this collection',
                    'error_code' => ErrorCodeUtility::NOT_OWNER_OF_COLLECTION,
                    'status_code' => '403'
                ], 403);
        }
        if($this->request->has('name')) {
            $collection->name = $this->request->name;
        }
        if($this->request->has('description')) {
            $collection->description = $this->request->description;
        }
        if($this->request->has('is_private')){
            $collection->is_private = $this->request->is_private == 'true' ? true : false;
        }
        $collection->save();
        return $this->response->created();
    }

    public function deleteOne($collection_id) {
        $result = PinUtility::validation('collection', $collection_id);
        if($result[0]) {
            return $result[1];
        }
        $collection = $result[1];
        if($collection->user_id != $this->request->self_user_id) {
            return response()->json([
                    'message' => 'You can not delete this collection',
                    'error_code' => ErrorCodeUtility::NOT_OWNER_OF_COLLECTION,
                    'status_code' => '403'
                ], 403);
        }
        $collection_of_pins = Collection_of_pins::where('collection_id', $collection->id)->get();
        $pinOperation = new PinOperationController($this->request);
        foreach ($collection_of_pins as $collection_of_pin) {
            $pinOperation->unsave($collection_id, $collection_of_pin->type, $collection_of_pin->pin_id);
        }
        $collection->delete();
        return $this->response->noContent();
    }

    public function getOne($collection_id) {
        $result = PinUtility::validation('collection', $collection_id);
        if($result[0]) {
            return $result[1];
        }
        $collection = $result[1];
        $result = $this->collectionFormatting($collection);
        if(is_null($result)) {
            return response()->json([
                    'message' => "it's a private collection",
                    'error_code' => ErrorCodeUtility::NOT_OWNER_OF_COLLECTION,
                    'status_code' => '403'
                ], 403);
        }
        $pins = DB::select("SELECT pin_id FROM collection_of_pins WHERE collection_id = :collection_id",
                            array('collection_id' => $collection_id));
        $result['pin_id'] = $pins;
        return array($result);
    }

    public function save($collection_id, $type, $pin_id) {
        $result = PinUtility::validation('collection', $collection_id);
        if($result[0]) {
            return $result[1];
        }
        $collection = $result[1];
        if($collection->user_id != $this->request->self_user_id) {
            return response()->json([
                    'message' => 'You can not save',
                    'error_code' => ErrorCodeUtility::NOT_OWNER_OF_COLLECTION,
                    'status_code' => '403'
                ], 403);
        }
        if($collection->type != $type) {
            return response()->json([
                'message' => 'wrong type, not '.$collection->type,
                'error_code' => ErrorCodeUtility::WRONG_TYPE,
                'status_code' => '400'
            ], 400);
        }
        // if($type = 'location') {
        //     $location = Locations::find($pin_id);
        //     if(is_null($location))
        //     {
        //         return response()->json([
        //             'message' => 'location not found',
        //             'error_code' => ErrorCodeUtility::LOCATIONS_NOT_FOUND,
        //             'status_code' => '404'
        //         ], 404);
        //     }
        //     if($location->user_id != $this->request->self_user_id)
        //     {
        //         return response()->json([
        //             'message' => 'You can not save this location',
        //             'error_code' => ErrorCodeUtility::NOT_OWNER_OF_PIN,
        //             'status_code' => '403'
        //         ], 403);
        //     }
        // }
        $pinOperation = new PinOperationController($this->request);
        return $pinOperation->save($collection_id, $type, $pin_id);
    }

    public function unsave($collection_id, $type, $pin_id) {
        $result = PinUtility::validation('collection', $collection_id);
        if($result[0]) {
            return $result[1];
        }
        $collection = $result[1];
        if($collection->user_id != $this->request->self_user_id) {
            return response()->json([
                    'message' => 'You can not unsave',
                    'error_code' => ErrorCodeUtility::NOT_OWNER_OF_COLLECTION,
                    'status_code' => '403'
                ], 403);
        }
        if($collection->type != $type) {
            return response()->json([
                'message' => 'wrong type, neither media nor comment',
                'error_code' => ErrorCodeUtility::WRONG_TYPE,
                'status_code' => '400'
            ], 400);
        }
        // if($type = 'location') {
        //     $location = Locations::find($pin_id);
        //     if(is_null($location))
        //     {
        //         return response()->json([
        //             'message' => 'location not found',
        //             'error_code' => ErrorCodeUtility::LOCATIONS_NOT_FOUND,
        //             'status_code' => '404'
        //         ], 404);
        //     }
        //     if($location->user_id != $this->request->self_user_id)
        //     {
        //         return response()->json([
        //             'message' => 'You can not save this location',
        //             'error_code' => ErrorCodeUtility::NOT_OWNER_OF_PIN,
        //             'status_code' => '403'
        //         ], 403);
        //     }
        // }
        $pinOperation = new PinOperationController($this->request);
        return $pinOperation->unsave($collection_id, $type, $pin_id);
    }

    private function collectionFormatting($collection) {
        if($this->request->self_user_id != $collection->user_id && $collection->is_private == true) {
            return null;
        }
        return array("collection_id" => $collection->id, "name" => $collection->name, 
                     "description" => $collection->description, "type" => $collection->type,
                     "is_private" => $collection->is_private, "created_at" => $collection->created_at);
    }

    private function createOneValidation(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|in:location,place',
            'is_private' => 'required|in:true,false'
        ]);
        if($validator->fails())
        {
            throw new StoreResourceFailedException('Could not create collection.',$validator->errors());
        }
    }

    private function updateOneValidation(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'filled|required_without_all:description,s_private|string',
            'description' => 'filled|required_without_all:name,is_private|string',
            'is_private' => 'filled|required_without_all:name,description|in:true,false'
        ]);
        if($validator->fails())
        {
            throw new UpdateResourceFailedException('Could not create collection.',$validator->errors());
        }
    }
}
