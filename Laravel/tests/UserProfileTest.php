<?php
 
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Phaza\LaravelPostgis\Eloquent\PostgisTrait;
use Phaza\LaravelPostgis\Geometries\Point;
use Phaza\LaravelPostgis\Geometries\Geometry;
use App\Users;

class UserProfileTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    // use DatabaseMigrations;
    /** @test */
    use PostgisTrait;
    public function setUp() {
        parent::setUp();
        $this->domain = Config::get('api.domain'); 
        $this->markTestSkipped(); 
    } 

    public function tearDown() {
        parent::tearDown();
        $this->beforeApplicationDestroyed(function () {
            DB::disconnect();
        });
    }

    // the correct response of getProfile.
    public function testGetProfile() { 
        $this->markTestSkipped(); 
        //register of the user. 
        $parameters = array(
            'email' => 'letsfae@126.com',
            'password' => 'letsfaego',
            'first_name' => 'kevin',
            'last_name' => 'zhang',
            'user_name' => 'faeapp',
            'gender' => 'male',
            'birthday' => '1992-02-02',
            'login_count' => 0,  
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1',
        );
        $response = $this->call('post', 'http://'.$this->domain.'/users', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $parameters = array(
            'email' => 'letsfae@126.com', 
            'password' => 'letsfaego',
            'user_name' => 'faeapp',
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
        );
        //login of the user.
        $login_response = $this->call('post', 'http://'.$this->domain.'/authentication', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $array = json_decode($login_response->getContent());
        $server2 = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
            'Authorization' => 'FAE '.$array->debug_base64ed,
        );  
        $response = $this->call('get', 'http://'.$this->domain.'/users/1/profile', [], [], [], $this->transformHeadersToServerVars($server2));  
        $array2 = json_decode($response->getContent()); 
        $this->seeJson([
                'user_id' => 1,
                'mini_avatar' => 0, 
                'last_login_at' => null,
                'user_name' => 'faeapp',  
                'birthday' => '1992-02-02',
                'email' => 'letsfae@126.com', 
                'phone' => null,
                'gender' => 'male',
        ]);
        $result = false;
        if ($response->status() == '200') {
            $result = true;
        }
        $this->assertEquals(true, $result);   
    }

    // test the response when the user information does not exist with the given user_id.
    public function testGetProfile2() { 
        $this->markTestSkipped(); 
        //register of the user.
        $user = Users::create([
            'email' => 'letsfae@126.com',
            'password' => bcrypt('letsfaego'),
            'first_name' => 'kevin',
            'last_name' => 'zhang',
            'user_name' => 'faeapp',
            'gender' => 'male',
            'birthday' => '1992-02-02',
            'login_count' => 0, 
            'mini_avatar' => 1
        ]);
        $parameters = array(
            'email' => 'letsfae@126.com', 
            'password' => 'letsfaego',
            'user_name' => 'faeapp',
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
        );
        //login of the user.
        $login_response = $this->call('post', 'http://'.$this->domain.'/authentication', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $array = json_decode($login_response->getContent());
        $server2 = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
            'Authorization' => 'FAE '.$array->debug_base64ed,
        );  
        $response = $this->call('get', 'http://'.$this->domain.'/users/2/profile', [], [], [], $this->transformHeadersToServerVars($server2)); 
        $array2 = json_decode($response->getContent());  
        $result = false;
        if ($response->status() == '404' && $array2->message == 'Not Found') {
            $result = true;
        }
        $this->assertEquals(true, $result);  
    }

    // test the response when the format of the given user_id is not right.
    public function testGetProfile3() { 
        $this->markTestSkipped(); 
        //register of the user.
        $user = Users::create([
            'email' => 'letsfae@126.com',
            'password' => bcrypt('letsfaego'),
            'first_name' => 'kevin',
            'last_name' => 'zhang',
            'user_name' => 'faeapp',
            'gender' => 'male',
            'birthday' => '1992-02-02',
            'login_count' => 0, 
            'mini_avatar' => 1
        ]);
        $parameters = array(
            'email' => 'letsfae@126.com', 
            'password' => 'letsfaego',
            'user_name' => 'faeapp',
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
        );
        //login of the user.
        $login_response = $this->call('post', 'http://'.$this->domain.'/authentication', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $array = json_decode($login_response->getContent());
        $server2 = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
            'Authorization' => 'FAE '.$array->debug_base64ed,
        );  
        $response = $this->call('get', 'http://'.$this->domain.'/users/fae/profile', [], [], [], $this->transformHeadersToServerVars($server2)); 
        $array2 = json_decode($response->getContent());   
        $result = false;
        if ($response->status() == '400' && $array2->message == 'Bad Request') {
            $result = true;
        }
        $this->assertEquals(true, $result);  
    } 
    // the correct response of getSelfProfile.
    public function testGetSelfProfile() { 
        $this->markTestSkipped(); 
        //register of the user.
       $parameters = array(
            'email' => 'letsfae@126.com',
            'password' => 'letsfaego',
            'first_name' => 'kevin',
            'last_name' => 'zhang',
            'user_name' => 'faeapp',
            'gender' => 'male',
            'birthday' => '1992-02-02',
            'login_count' => 0,  
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1',
        );
        $response = $this->call('post', 'http://'.$this->domain.'/users', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $parameters = array(
            'email' => 'letsfae@126.com', 
            'password' => 'letsfaego',
            'user_name' => 'faeapp',
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
        );
        //login of the user.
        $login_response = $this->call('post', 'http://'.$this->domain.'/authentication', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $array = json_decode($login_response->getContent());
        $server2 = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
            'Authorization' => 'FAE '.$array->debug_base64ed,
        );  
        $this->refreshApplication();
        $response = $this->call('get', 'http://'.$this->domain.'/users/profile', [], [], [], $this->transformHeadersToServerVars($server2)); 
        $array2 = json_decode($response->getContent()); 
        $this->seeJson([
                'user_id' => 1,
                'mini_avatar' => 0, 
                'last_login_at' => null,
                'user_name' => 'faeapp',  
                'birthday' => '1992-02-02',
                'email' => 'letsfae@126.com', 
                'phone' => null,
                'gender' => 'male',
        ]);
        $result = false;
        if ($response->status() == '200') {
            $result = true;
        }
        $this->assertEquals(true, $result);   
    }
    // the correct response of updateSelfProfile.
    public function testUpdateSelfProfile() { 
        $this->markTestSkipped(); 
        //register of the user.
       $parameters = array(
            'email' => 'letsfae@126.com',
            'password' => 'letsfaego',
            'first_name' => 'kevin',
            'last_name' => 'zhang',
            'user_name' => 'faeapp',
            'gender' => 'male',
            'birthday' => '1992-02-02',
            'login_count' => 0,  
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1',
        );
        $response = $this->call('post', 'http://'.$this->domain.'/users', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $parameters = array(
            'email' => 'letsfae@126.com', 
            'password' => 'letsfaego',
            'user_name' => 'faeapp',
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
        );
        //login of the user.
        $login_response = $this->call('post', 'http://'.$this->domain.'/authentication', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $array = json_decode($login_response->getContent());
        $server2 = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
            'Authorization' => 'FAE '.$array->debug_base64ed,
        );  
        $this->refreshApplication();
        $parameters = array(
            'gender' => 'female',
        );
        $response = $this->call('post', 'http://'.$this->domain.'/users/profile', $parameters, [], [], $this->transformHeadersToServerVars($server2));   
        $result = false;
        if ($response->status() == '201') {
            $result = true;
        }
        $this->assertEquals(true, $result);   
    }
    // the correct response of getPrivacy.
    public function testGetPrivacy() { 
        $this->markTestSkipped(); 
        //register of the user. 
        $parameters = array(
            'email' => 'letsfae@126.com',
            'password' => 'letsfaego',
            'first_name' => 'kevin',
            'last_name' => 'zhang',
            'user_name' => 'faeapp',
            'gender' => 'male',
            'birthday' => '1992-02-02',
            'login_count' => 0,  
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1',
        );
        $response = $this->call('post', 'http://'.$this->domain.'/users', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $parameters = array(
            'email' => 'letsfae@126.com', 
            'password' => 'letsfaego',
            'user_name' => 'faeapp',
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
        );
        //login of the user.
        $login_response = $this->call('post', 'http://'.$this->domain.'/authentication', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $array = json_decode($login_response->getContent());
        $server2 = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
            'Authorization' => 'FAE '.$array->debug_base64ed,
        );  
        $this->refreshApplication();
        $response = $this->call('get', 'http://'.$this->domain.'/users/profile/privacy', [], [], [], $this->transformHeadersToServerVars($server2));  
        $array2 = json_decode($response->getContent()); 
        $this->seeJson([
            'show_user_name' => true, 
            'show_email' => true,
            'show_phone' => true, 
            'show_birthday' => true, 
            'show_gender' => true,
        ]);
        $result = false;
        if ($response->status() == '200') {
            $result = true;
        }
        $this->assertEquals(true, $result);   
    }
    // the correct response of updatePrivacy.
    public function testUpdatePrivacy() { 
        $this->markTestSkipped(); 
        //register of the user. 
        $parameters = array(
            'email' => 'letsfae@126.com',
            'password' => 'letsfaego',
            'first_name' => 'kevin',
            'last_name' => 'zhang',
            'user_name' => 'faeapp',
            'gender' => 'male',
            'birthday' => '1992-02-02',
            'login_count' => 0,  
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1',
        );
        $response = $this->call('post', 'http://'.$this->domain.'/users', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $parameters = array(
            'email' => 'letsfae@126.com', 
            'password' => 'letsfaego',
            'user_name' => 'faeapp',
        );
        $server = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
        );
        //login of the user.
        $login_response = $this->call('post', 'http://'.$this->domain.'/authentication', $parameters, [], [], $this->transformHeadersToServerVars($server));
        $array = json_decode($login_response->getContent());
        $server2 = array(
            'Accept' => 'application/x.faeapp.v1+json', 
            'Fae-Client-Version' => 'ios-0.0.1', 
            'Authorization' => 'FAE '.$array->debug_base64ed,
        );  
        $this->refreshApplication();
        $parameters = array(
            'show_phone' => false,
        );
        $response = $this->call('post', 'http://'.$this->domain.'/users/profile/privacy', $parameters, [], [], $this->transformHeadersToServerVars($server2));  
        $array2 = json_decode($response->getContent()); 
        $result = false;
        if ($response->status() == '201') {
            $result = true;
        }
        $this->assertEquals(true, $result);   
    }
}
