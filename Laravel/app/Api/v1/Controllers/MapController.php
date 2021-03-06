<?php

namespace App\Api\v1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Dingo\Api\Routing\Helpers;
use Dingo\Api\Exception\UpdateResourceFailedException;
use App\Sessions;
use App\PinHelper;
use App\Comments;
use App\ChatRooms;
use App\Medias;
use App\User_exts;
use App\UserSettings;
use App\Name_cards;
use App\Users;
use App\Places;
use App\Locations;
use App\Api\v1\Controllers\PlaceController;
use App\Api\v1\Controllers\PinOperationController;
use Validator;
use DB;
use Phaza\LaravelPostgis\Eloquent\PostgisTrait;
use Phaza\LaravelPostgis\Geometries\Point;
use Phaza\LaravelPostgis\Geometries\Geometry;
use App\Api\v1\Utilities\ErrorCodeUtility;
use App\Api\v1\Utilities\PinUtility;
use Elasticsearch;

class MapController extends Controller
{
    use Helpers;
    use PostgisTrait;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getMap()
    {
        $this->getMapValidation($this->request);
        $location = new Point($this->request->geo_latitude,$this->request->geo_longitude);
        $longitude = $this->request->geo_longitude;
        $latitude = $this->request->geo_latitude;
        $radius = $this->request->has('radius') ? $this->request->radius:200;
        $max_count = $this->request->has('max_count') ? $this->request->max_count:30;
        $in_duration_str = $this->request->has('in_duration') ? $this->request->in_duration : 'false';
        $in_duration = $in_duration_str == 'true' ? true : false;
        $user_updated_in = $this->request->has('user_updated_in') ? $this->request->user_updated_in : 0;
        $offset = $this->request->has('offset') ? $this->request->offset : 0;
        $info = array();
        $type = array();
        if($this->request->type == 'user')
        {
            $sessions = array();
            if($user_updated_in == 0)
            {
                $sessions = DB::select("SELECT user_id,location,created_at,location_updated_at
                                        FROM sessions
                                        WHERE st_dwithin(location,ST_SetSRID(ST_Point(:longitude, :latitude),4326),:radius,true)
                                        AND user_id != :user_id
                                        ORDER BY ST_Distance(location, ST_SetSRID(ST_Point(:longitude, :latitude),4326)), user_id
                                        LIMIT :max_count;", 
                                        array('longitude' => $longitude, 'latitude' => $latitude,
                                              'user_id' => $this->request->self_user_id,
                                              'radius' => $radius, 'max_count' => $max_count));
            }
            else
            {
                $sessions = DB::select("SELECT user_id,location,created_at,location_updated_at
                                        FROM sessions
                                        WHERE st_dwithin(location,ST_SetSRID(ST_Point(:longitude, :latitude),4326),:radius,true)
                                        AND CURRENT_TIMESTAMP - location_updated_at < ".strval($user_updated_in)." * INTERVAL '1 min' 
                                        ORDER BY ST_Distance(location, ST_SetSRID(ST_Point(:longitude, :latitude),4326)), user_id
                                        LIMIT :max_count;", 
                                        array('longitude' => $longitude, 'latitude' => $latitude,
                                              'radius' => $radius, 'max_count' => $max_count));
            }
            // $distance = DB::select("SELECT ST_Distance_Spheroid(ST_SetSRID(ST_Point(55, 56.1),4326), ST_SetSRID(ST_Point(56, 56),4326), 'SPHEROID[\"WGS 84\",6378137,298.257223563]')");
            // echo $distance[0]->st_distance_spheroid; exit();
            //bug here! 如果存在隐身用户，返回数量会少于max_count              
            foreach($sessions as $session)
            {
                $user_exts = User_exts::find($session->user_id);
                if(is_null($user_exts) || $user_exts->status == 5)
                {
                    continue;
                }
                $location = Geometry::fromWKB($session->location);
                $locations = array();
                for($i = 0; $i < 5; $i++)
                {
                    $distance = mt_rand(300,600);
                    $degree = mt_rand(0,360);
                    $locations_original = DB::select("select ST_AsText(ST_Project(ST_SetSRID(ST_Point(:longitude,:latitude),4326),:distance, radians(:degree)))", 
                        array('longitude' => $location->getLng(),'latitude'=>$location->getLat(),'distance'=>$distance,'degree'=>$degree));
                        $locations[] = Point::fromWKT($locations_original[0]->st_astext);
                }
                $user = Users::find($session->user_id);
                if(is_null($user))
                {
                    continue;
                }

                $user_setting = UserSettings::find($session->user_id);

                $nameCard = Name_cards::find($session->user_id);
                $birthDate = $user->birthday;
                $birthDate = explode("-", $birthDate);
                $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[2], $birthDate[0]))) > date("md")
                        ? ((date("Y") - $birthDate[0]) - 1)
                        : (date("Y") - $birthDate[0]));
                $info[] = ['type'=>'user', 'user_id' => $session->user_id,
                           'user_name' => $user_exts->show_user_name ? $user->user_name : null,
                           'user_nick_name' => $nameCard->nick_name,
                           'user_age' => $nameCard->show_age ? $age : null,
                           'user_gender' => $nameCard->show_gender ? $user->gender : null,
                           'mini_avatar' => $user->mini_avatar, 
                           'location_updated_at' => $session->location_updated_at,
                           'short_intro' => $nameCard->short_intro,
                           'show_name_card_options' => $user_setting->show_name_card_options,
                           'geolocation'=>[
                           ['latitude'=>$locations[0]->getLat(),'longitude'=>$locations[0]->getLng()],
                           ['latitude'=>$locations[1]->getLat(),'longitude'=>$locations[1]->getLng()],
                           ['latitude'=>$locations[2]->getLat(),'longitude'=>$locations[2]->getLng()],
                           ['latitude'=>$locations[3]->getLat(),'longitude'=>$locations[3]->getLng()],
                           ['latitude'=>$locations[4]->getLat(),'longitude'=>$locations[4]->getLng()]],
                           'created_at'=>$session->created_at];
            }
        }
        else if($this->request->type == 'place')
        {
            if($this->request->has('categories')) {
                 $data = [
                    "body" => [
                        "size" => $max_count,
                        "from" => $offset,
                        "query"=> [
                            "filtered"=> [
                                "query"=> [
                                    "match" => [
                                      "class_two" => $this->request->categories
                                    ]
                                ],
                                "filter"=> [
                                    "geo_distance"=> [
                                        "distance" => $radius."m",
                                        "distance_type" => "sloppy_arc", 
                                        "places.location"=> [
                                            "lat" =>  $latitude,
                                            "lon" => $longitude
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "sort" => [[
                            "_geo_distance" => [
                                "location" => [ 
                                    "lat" => $latitude,
                                    "lon" => $longitude
                                ],
                                "order" => $this->request->has('offset') ? "asc" : "RANDOM",
                                "unit" => "m", 
                                "distance_type" => "sloppy_arc" 
                            ]
                        ]]
                    ],
                    "index" => "foursquare",
                    "type" => "places"
                ];

                $raw_places = Elasticsearch::search($data)['hits']['hits'];

                $places = array();
                foreach ($raw_places as $place) {
                    $places[] = $place['_source'];
                }
                // $places = DB::connection('foursquare')->select(
                //             "SELECT * FROM places
                //             WHERE class_two = :categories 
                //             AND st_dwithin(geolocation,ST_SetSRID(ST_Point(:longitude, :latitude),4326),:radius,true)
                //             ORDER BY ST_Distance(geolocation, ST_SetSRID(ST_Point(:longitude, :latitude),4326))
                //             LIMIT :max_count;",
                //             array('categories' => $this->request->categories, 'longitude' => $longitude, 
                //                 'latitude' => $latitude, 'radius' => $radius, 'max_count' => $max_count));
            } else {
                $data = [
                    "body" => [
                        "size" => $max_count,
                        "from" => $offset,
                        "query"=> [
                            "filtered"=> [
                                "filter"=> [
                                    "geo_distance"=> [
                                        "distance" => $radius."m",
                                        "distance_type" => "sloppy_arc", 
                                        "places.location"=> [
                                            "lat" =>  $latitude,
                                            "lon" => $longitude
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "sort" => [[
                            "_geo_distance" => [
                                "location" => [ 
                                    "lat" => $latitude,
                                    "lon" => $longitude
                                ],
                                "order" => $this->request->has('offset') ? "asc" : "RANDOM",
                                "unit" => "m", 
                                "distance_type" => "sloppy_arc" 
                            ]
                        ]]
                    ],
                    "index" => "foursquare",
                    "type" => "places"
                ];

                $raw_places = Elasticsearch::search($data)['hits']['hits'];

                $places = array();
                foreach ($raw_places as $place) {
                    $places[] = $place['_source'];
                }
                // $places = DB::connection('foursquare')->select(
                //             "SELECT * FROM places
                //             WHERE st_dwithin(geolocation,ST_SetSRID(ST_Point(:longitude, :latitude),4326),:radius,true)
                //             ORDER BY ST_Distance(geolocation, ST_SetSRID(ST_Point(:longitude, :latitude),4326))
                //             LIMIT :max_count;",
                //             array('longitude' => $longitude, 'latitude' => $latitude,
                //             'radius' => $radius, 'max_count' => $max_count));
            }
            foreach ($places as $place) {
                //$info[] = PlaceController::getPinObject($place['id'], $this->request->self_user_id);  
                // print_r($place);
                $info[] = array(
                    'place_id' => $place["id"], 
                    'name' => $place["name"],
                    'priceRange' => array_key_exists("priceRange", $place) ? $place["priceRange"] : '',
                    'hour' => array_key_exists("hour_data", $place) ? $place["hour_data"] : '',
                    'url' => array_key_exists("url", $place) ? $place["url"] : '',
                    'img' => array_key_exists("img", $place) ? $place["img"] : '',
                    'phone' => array_key_exists("phone", $place) ? $place["phone"] : '',
                    'geolocation' => [
                        'latitude' => $place["location"]["lat"], 
                        'longitude' => $place["location"]["lon"]], 
                    'location' => [
                        'city' => $place["city"],
                        'country' => $place["country"], 
                        'state' => $place["state"],
                        'address' => $place["address"], 
                        'zip_code' => $place["zip_code"]],
                    'categories' => [
                        'class1' => array_key_exists("class_one", $place) ? $place["class_one"] : '',
                        'class1_icon_id' => array_key_exists("class_one_idx", $place) ? $place["class_one_idx"] : '',
                        'class2' => array_key_exists("class_two", $place) ? $place["class_two"] : '',
                        'class2_icon_id' => array_key_exists("class_two_idx", $place) ? $place["class_two_idx"] : '',
                        'class3' => array_key_exists("class_three", $place) ? $place["class_three"] : '',
                        'class3_icon_id' => array_key_exists("class_three_idx", $place) ? $place["class_three_idx"] : '',
                        'class4' => array_key_exists("class_four", $place) ? $place["class_four"] : '',
                        'class4_icon_id' => array_key_exists("class_four_idx", $place) ? $place["class_four_idx"] : '']
                );
            }
        }
        else if($this->request->type == 'location')
        {
            $locations = DB::select(
                        "SELECT * FROM locations
                        WHERE user_id = :user_id
                        AND st_dwithin(geolocation,ST_SetSRID(ST_Point(:longitude, :latitude),4326),:radius,true)
                        ORDER BY ST_Distance(geolocation, ST_SetSRID(ST_Point(:longitude, :latitude),4326))
                        LIMIT :max_count;",
                        array('user_id' => $this->request->self_user_id, 
                              'longitude' => $longitude, 'latitude' => $latitude,
                              'radius' => $radius, 'max_count' => $max_count));
            foreach ($locations as $location) 
            {
                $info[] = LocationController::getPinObject($location->id, $this->request->self_user_id);    
            }
        }
        else
        {
            $types = explode(',', $this->request->type);
            foreach($types as $t)
            {
                $t = trim($t);
                switch($t)
                {
                    case 'user':
                        return response()->json([
                            'message' => 'wrong type',
                            'error_code' => ErrorCodeUtility::WRONG_TYPE,
                            'status_code' => '400'
                            ], 400);
                        break;
                    case 'place':
                        return response()->json([
                            'message' => 'wrong type',
                            'error_code' => ErrorCodeUtility::WRONG_TYPE,
                            'status_code' => '400'
                            ], 400);
                        break;
                    case 'comment':
                        $type[] = "'comment'";
                        break;
                    case 'media':
                        $type[] = "'media'";
                        break;
                    case 'chat_room':
                        $type[] = "'chat_room'";
                        break;
                    default:
                        return response()->json([
                            'message' => 'wrong type',
                            'error_code' => ErrorCodeUtility::WRONG_TYPE,
                            'status_code' => '400'
                            ], 400);
                }
            }
            
            $type_string = implode(",", $type);

            $sql_select = "SELECT p1.pin_id, p1.type, p1.created_at FROM pin_helper p1";
            if($this->request->has('is_saved') || $this->request->has('is_liked') || $this->request->has('is_read'))
            {
                $sql_select .= ", pin_operations p2";
            }
            $sql_select .= "\nWHERE st_dwithin(geolocation,ST_SetSRID(ST_Point(:longitude, :latitude),4326),:radius,true)\n";
            if($in_duration == true)
            {
                $sql_select .= "AND CURRENT_TIMESTAMP - p1.created_at < duration * INTERVAL '1 min'\n";
            }
            $sql_select .= "AND p1.type IN (".$type_string.")\n";
            if($this->request->has('is_saved') || $this->request->has('is_liked') || $this->request->has('is_read'))
            {
                if(!$this->request->has('is_read') || $this->request->is_read == 'true')
                {
                    $sql_select .= "AND p1.pin_id = p2.pin_id
                                    AND p1.type = p2.type
                                    AND p2.user_id = :user_id\n";
                    if($this->request->has('is_saved')) 
                    {
                        $sql_select .= "AND p2.saved = ".$this->request->is_saved."\n";
                    }
                    if($this->request->has('is_liked'))
                    {
                        $sql_select .= "AND p2.liked = ".$this->request->is_liked."\n";
                    }
                }
                else
                {
                    $sql_select .= "AND NOT EXISTS
                                    (SELECT * FROM pin_operations p3 
                                    WHERE p1.pin_id = p3.pin_id 
                                    AND p1.type = p3.type  
                                    AND p3.user_id = :user_id)\n";
                }   
            }
            $sql_select .= "ORDER BY p1.created_at DESC
                            LIMIT :max_count;";
            if($this->request->has('is_saved') || $this->request->has('is_liked') || $this->request->has('is_read'))
            {
                $pin_helpers = DB::select($sql_select, array('longitude' => $longitude, 'latitude' => $latitude,
                                                             'radius' => $radius, 'max_count' => $max_count, 
                                                             'user_id' => $this->request->self_user_id));
            }
            else
            {
                $pin_helpers = DB::select($sql_select, array('longitude' => $longitude, 'latitude' => $latitude,
                                                             'radius' => $radius, 'max_count' => $max_count));
            }
            foreach ($pin_helpers as $pin_helper)
            {
                $info[] = array('pin_id' => $pin_helper->pin_id,
                                'type' => $pin_helper->type,
                                'created_at' => $pin_helper->created_at,
                                'pin_object' => PinUtility::getPinObject($pin_helper->type, $pin_helper->pin_id, 
                                $this->request->self_user_id));
            }
        }
        return $this->response->array($info);   
    }

    private function getMapValidation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'geo_longitude' => 'required|numeric|between:-180,180',
            'geo_latitude' => 'required|numeric|between:-90,90',
            'radius' => 'filled|integer|min:0',
            'type' => 'required|string',
            'max_count' => 'filled|integer|between:0,1000',
            'in_duration' => 'filled|in:true,false',
            'user_updated_in' => 'filled|integer|min:1',
            'is_saved' => 'filled|in:true,false',
            'is_liked' => 'filled|in:true,false',
            'is_read' => 'filled|in:true,false',
            'categories' => 'filled|string',
        ]);
        if($validator->fails())
        {
            throw new UpdateResourceFailedException('Could not get map.',$validator->errors());
        }
    }

    public function updateUserLocation()
    {        
        $this->locationValidation($this->request);
        $session = Sessions::find($this->request->self_session_id);
        if(is_null($session))
        {
            return response()->json([
                    'message' => 'session not found',
                    'error_code' => ErrorCodeUtility::SESSION_NOT_FOUND,
                    'status_code' => '404'
                ], 404);
        }
        if($session->is_mobile)
        {
            $session->location = new Point($this->request->geo_latitude, $this->request->geo_longitude);
            $session->updateLocationTimestamp();
            $session->save();
            return $this->response->created();
        }
        else
        {
            return response()->json([
                    'message' => 'current client is not mobile',
                    'error_code' => ErrorCodeUtility::NOT_MOBILE,
                    'status_code' => '400'
                ], 400);
        }
    }

    private function locationValidation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'geo_longitude' => 'required|numeric|between:-180,180',
            'geo_latitude' => 'required|numeric|between:-90,90',
        ]);
        if($validator->fails())
        {
            throw new UpdateResourceFailedException('Could not update user location.',$validator->errors());
        }
    }
}