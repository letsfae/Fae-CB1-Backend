<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome', ['title' => 'Fae API v1']);
});

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->post('/users', 'App\Api\v1\Controllers\UserController@signUp');
    $api->post('/authentication', 'App\Api\v1\Controllers\AuthenticationController@login');

    // verification
    $api->post('/reset_login/code', 'App\Api\v1\Controllers\ResetLoginController@sendResetCode');
    $api->post('/reset_login/code/verify', 'App\Api\v1\Controllers\ResetLoginController@verifyResetCode');
    $api->post('/reset_login/password', 'App\Api\v1\Controllers\ResetLoginController@resetPassword');

    // existence
    $api->get('/existence/email/{email}', 'App\Api\v1\Controllers\ExistenceController@email');
    $api->get('/existence/user_name/{user_name}', 'App\Api\v1\Controllers\ExistenceController@userName');
    $api->get('/existence/phone/_batch', 'App\Api\v1\Controllers\ExistenceController@getUserByPhoneBatched');
    
    // test
    $api->post('/test/push_notification', 'App\Api\v1\Controllers\TestController@sendPushNotification');
});

$api->version('v1', ['middleware' => 'api.auth', 'providers' => ['fae']], function ($api) {
    $api->delete('/authentication', 'App\Api\v1\Controllers\AuthenticationController@logout');

    // account
    $api->post('/users/account', 'App\Api\v1\Controllers\UserController@updateAccount');
    $api->get('/users/account', 'App\Api\v1\Controllers\UserController@getAccount');
    $api->post('/users/account/password', 'App\Api\v1\Controllers\UserController@updatePassword');
    $api->post('/users/account/password/verify', 'App\Api\v1\Controllers\UserController@verifyPassword');
    $api->post('/users/account/email', 'App\Api\v1\Controllers\UserController@updateEmail');
    $api->post('/users/account/email/verify', 'App\Api\v1\Controllers\UserController@verifyEmail');
    $api->post('/users/account/phone', 'App\Api\v1\Controllers\UserController@updatePhone');
    $api->post('/users/account/phone/verify', 'App\Api\v1\Controllers\UserController@verifyPhone');
    // profile
    $api->post('/users/profile', 'App\Api\v1\Controllers\UserProfileController@updateSelfProfile');
    $api->get('/users/profile', 'App\Api\v1\Controllers\UserProfileController@getSelfProfile');
    $api->get('/users/{user_id}/profile', 'App\Api\v1\Controllers\UserProfileController@getProfile');
    $api->post('/users/profile/privacy', 'App\Api\v1\Controllers\UserProfileController@updatePrivacy');
    $api->get('/users/profile/privacy', 'App\Api\v1\Controllers\UserProfileController@getPrivacy');
    // name card
    $api->get('/users/{user_id}/name_card', 'App\Api\v1\Controllers\UserNameCardController@getNameCard');
    $api->get('/users/name_card', 'App\Api\v1\Controllers\UserNameCardController@getSelfNameCard');
    $api->get('/users/name_card/tags', 'App\Api\v1\Controllers\UserNameCardController@getAllTags');
    $api->post('/users/name_card', 'App\Api\v1\Controllers\UserNameCardController@updateNameCard');
    $api->post('/users/{user_id}/name_card/save', 'App\Api\v1\Controllers\UserNameCardController@saveNameCard');
    $api->delete('/users/{user_id}/name_card/save', 'App\Api\v1\Controllers\UserNameCardController@unsaveNameCard');
    $api->get('/users/name_card/saved', 'App\Api\v1\Controllers\UserNameCardController@getSavedNameCardList');
    // status
    $api->post('/users/status', 'App\Api\v1\Controllers\UserController@updateSelfStatus');
    $api->get('/users/status', 'App\Api\v1\Controllers\UserController@getSelfStatus');
    $api->get('/users/{user_id}/status', 'App\Api\v1\Controllers\UserController@getStatus');
    // synchronization
    $api->get('/sync', 'App\Api\v1\Controllers\SyncController@getSync');
    // map
    $api->get('/map', 'App\Api\v1\Controllers\MapController@getMap');
    $api->post('/map/user', 'App\Api\v1\Controllers\MapController@updateUserLocation');
    // comment
    $api->post('/comments', 'App\Api\v1\Controllers\CommentController@create');
    $api->post('/comments/{comment_id}', 'App\Api\v1\Controllers\CommentController@update');
    $api->get('/comments/{comment_id}', 'App\Api\v1\Controllers\CommentController@getOne');
    $api->delete('/comments/{comment_id}', 'App\Api\v1\Controllers\CommentController@delete');
    $api->get('/comments/users/{user_id}', 'App\Api\v1\Controllers\CommentController@getFromUser');
    // media
    $api->post('/medias', 'App\Api\v1\Controllers\MediaController@create');
    $api->post('/medias/{media_id}', 'App\Api\v1\Controllers\MediaController@update');
    $api->get('/medias/{media_id}', 'App\Api\v1\Controllers\MediaController@getOne');
    $api->delete('/medias/{media_id}', 'App\Api\v1\Controllers\MediaController@delete');
    $api->get('/medias/users/{user_id}', 'App\Api\v1\Controllers\MediaController@getFromUser');
    // faevor
    $api->post('/faevors', 'App\Api\v1\Controllers\FaevorController@create');
    $api->post('/faevors/{faevor_id}', 'App\Api\v1\Controllers\FaevorController@update');
    $api->get('/faevors/{faevor_id}', 'App\Api\v1\Controllers\FaevorController@getOne');
    $api->delete('/faevors/{faevor_id}', 'App\Api\v1\Controllers\FaevorController@delete');
    $api->get('/faevors/users/{user_id}', 'App\Api\v1\Controllers\FaevorController@getFromUser');
    // friends
    $api->post('/friends/request', 'App\Api\v1\Controllers\FriendController@requestFriend');
    $api->get('/friends/request', 'App\Api\v1\Controllers\FriendController@getAllRequests');
    $api->get('/friends/request_sent', 'App\Api\v1\Controllers\FriendController@getAllSentRequests');
    $api->post('/friends/accept', 'App\Api\v1\Controllers\FriendController@acceptRequest');
    $api->post('/friends/ignore', 'App\Api\v1\Controllers\FriendController@ignoreRequest');
    $api->post('/friends/withdraw', 'App\Api\v1\Controllers\FriendController@withdrawRequest');
    $api->delete('/friends/{user_id}', 'App\Api\v1\Controllers\FriendController@deleteFriend');
    $api->get('/friends', 'App\Api\v1\Controllers\FriendController@getFriendsList');
    // follows
    $api->post('/follows', 'App\Api\v1\Controllers\FollowController@follow');
    $api->get('/follows/{user_id}/followee', 'App\Api\v1\Controllers\FollowController@getFollowee');
    $api->get('/follows/{user_id}/follower', 'App\Api\v1\Controllers\FollowController@getFollower');
    $api->delete('/follows/{followee_id}', 'App\Api\v1\Controllers\FollowController@unfollow');
    // blocks
    $api->post('/blocks', 'App\Api\v1\Controllers\BlockController@add');
    $api->delete('/blocks/{user_id}', 'App\Api\v1\Controllers\BlockController@delete');
    // chats
    $api->post('/chats', 'App\Api\v1\Controllers\ChatController@send');
    $api->get('/chats/unread', 'App\Api\v1\Controllers\ChatController@getUnread');
    $api->post('/chats/read', 'App\Api\v1\Controllers\ChatController@markRead');
    $api->get('/chats', 'App\Api\v1\Controllers\ChatController@getHistory');
    $api->delete('/chats/{chat_id}', 'App\Api\v1\Controllers\ChatController@delete');
    $api->get('/chats/users/{user_a_id}/{user_b_id}', 'App\Api\v1\Controllers\ChatController@getChatIdFromUserId');
    $api->get('/chats/{user_a_id}/{user_b_id}', 'App\Api\v1\Controllers\ChatController@getMessageByUserId');
    $api->get('/chats/{chat_id}', 'App\Api\v1\Controllers\ChatController@getMessageByChatId');
    // chats v2
    $api->post('/chats_v2', 'App\Api\v1\Controllers\ChatV2Controller@send');
    $api->get('/chats_v2/unread', 'App\Api\v1\Controllers\ChatV2Controller@getUnread');
    $api->delete('/chats_v2/{chat_id}', 'App\Api\v1\Controllers\ChatV2Controller@delete');
    $api->get('/chats_v2/users/{user_a_id}/{user_b_id}', 'App\Api\v1\Controllers\ChatV2Controller@getChatIdFromUserId');
    $api->get('/chats_v2/{user_a_id}/{user_b_id}', 'App\Api\v1\Controllers\ChatV2Controller@getMessageByUserId');
    $api->get('/chats_v2/{chat_id}', 'App\Api\v1\Controllers\ChatV2Controller@getMessageByChatId');
    // chat rooms
    $api->post('/chat_rooms', 'App\Api\v1\Controllers\ChatRoomController@create');
    $api->post('/chat_rooms/{chat_room_id}', 'App\Api\v1\Controllers\ChatRoomController@update');
    $api->get('/chat_rooms/{chat_room_id}', 'App\Api\v1\Controllers\ChatRoomController@getOne');
    // $api->delete('/chat_rooms/{chat_room_id}', 'App\Api\v1\Controllers\ChatRoomController@delete');
    $api->get('/chat_rooms/users/{user_id}', 'App\Api\v1\Controllers\ChatRoomController@getFromUser');
    $api->post('/chat_rooms/{chat_room_id}/message', 'App\Api\v1\Controllers\ChatRoomController@send');
    $api->get('/chat_rooms/message/unread', 'App\Api\v1\Controllers\ChatRoomController@getUnread');
    $api->post('/chat_rooms/{chat_room_id}/message/read', 'App\Api\v1\Controllers\ChatRoomController@markRead');
    $api->get('/chat_rooms', 'App\Api\v1\Controllers\ChatRoomController@getHistory');
    $api->get('/chat_rooms/{chat_room_id}/users', 'App\Api\v1\Controllers\ChatRoomController@getUserList');
    // chat rooms v2
    $api->post('/chat_rooms_v2', 'App\Api\v1\Controllers\ChatRoomV2Controller@create');
    $api->post('/chat_rooms_v2/{chat_room_id}', 'App\Api\v1\Controllers\ChatRoomV2Controller@update');
    $api->get('/chat_rooms_v2/{chat_room_id}', 'App\Api\v1\Controllers\ChatRoomV2Controller@getOne');
    $api->get('/chat_rooms_v2/users/{user_id}', 'App\Api\v1\Controllers\ChatRoomV2Controller@getFromUser');
    $api->post('/chat_rooms_v2/{chat_room_id}/message', 'App\Api\v1\Controllers\ChatRoomV2Controller@send');
    $api->get('/chat_rooms_v2/message/unread', 'App\Api\v1\Controllers\ChatRoomV2Controller@getUnread');
    $api->post('/chat_rooms_v2/{chat_room_id}/message/read', 'App\Api\v1\Controllers\ChatRoomV2Controller@markRead');
    $api->get('/chat_rooms_v2/{chat_room_id}', 'App\Api\v1\Controllers\ChatRoomV2Controller@getMessage');
    $api->get('/chat_rooms_v2/{chat_room_id}/users', 'App\Api\v1\Controllers\ChatRoomV2Controller@getUserList');
    // tags
    $api->post('/tags', 'App\Api\v1\Controllers\TagController@create');
    $api->get('/tags', 'App\Api\v1\Controllers\TagController@getArray');
    $api->get('/tags/{tag_id}', 'App\Api\v1\Controllers\TagController@getOne');
    $api->get('/tags/{tag_id}/pin', 'App\Api\v1\Controllers\TagController@getAllPins');
    // pin operations
    $api->post('/pins/{type}/{pin_id}/read', 'App\Api\v1\Controllers\PinOperationController@read');
    $api->post('/pins/{type}/{pin_id}/like', 'App\Api\v1\Controllers\PinOperationController@like');
    $api->delete('/pins/{type}/{pin_id}/like', 'App\Api\v1\Controllers\PinOperationController@unlike');
    $api->post('/pins/{type}/{pin_id}/comments', 'App\Api\v1\Controllers\PinOperationController@comment');
    $api->post('/pins/comments/{pin_comment_id}', 'App\Api\v1\Controllers\PinOperationController@updateComment');
    $api->delete('/pins/comments/{pin_comment_id}', 'App\Api\v1\Controllers\PinOperationController@uncomment');
    $api->post('/pins/{type}/{pin_id}/save', 'App\Api\v1\Controllers\PinOperationController@save');
    $api->delete('/pins/{type}/{pin_id}/save', 'App\Api\v1\Controllers\PinOperationController@unsave');
    $api->post('/pins/{type}/{pin_id}/feeling', 'App\Api\v1\Controllers\PinOperationController@feeling');
    $api->delete('/pins/{type}/{pin_id}/feeling', 'App\Api\v1\Controllers\PinOperationController@removeFeeling');
    $api->get('/pins/{type}/{pin_id}/attribute', 'App\Api\v1\Controllers\PinOperationController@getPinAttribute');
    $api->get('/pins/{type}/{pin_id}/comments', 'App\Api\v1\Controllers\PinOperationController@getPinCommentList');
    $api->get('/pins/saved', 'App\Api\v1\Controllers\PinOperationController@getSavedPinList');
    $api->get('/pins/users', 'App\Api\v1\Controllers\PinOperationController@getSelfPinList');
    $api->get('/pins/users/{user_id}', 'App\Api\v1\Controllers\PinOperationController@getUserPinList');
    $api->post('/pins/comments/{pin_comment_id}/vote', 'App\Api\v1\Controllers\PinOperationController@vote');
    $api->delete('/pins/comments/{pin_comment_id}/vote', 'App\Api\v1\Controllers\PinOperationController@cancelVote');
    $api->get('/pins/statistics', 'App\Api\v1\Controllers\PinOperationController@pinStatistics');
    // richtext (hashtags and at)
    $api->get('/richtext/hashtag/{hashtag}', 'App\Api\v1\Controllers\RichTextController@searchHashtag');
    $api->get('/richtext/hashtag/{hashtag}/contains', 'App\Api\v1\Controllers\RichTextController@searchHashtagWithText');
    // feedback
    $api->post('/feedback', 'App\Api\v1\Controllers\FeedbackController@sendFeedback');
    // places
    $api->get('/pins/places/{place_id}', 'App\Api\v1\Controllers\PlaceController@getOne');
});

/**
 * Files
 */
$api->version('v1', ['middleware' => 'api.auth', 'providers' => ['fae']], function ($api) {
    // general
    $api->post('/files', 'App\Api\v1\Controllers\FileController@upload');
    $api->get('/files/{file_id}/attribute', 'App\Api\v1\Controllers\FileController@getAttribute');
    $api->get('/files/{file_id}/data', 'App\Api\v1\Controllers\FileController@getData');
    // avatar
    $api->post('/files/users/avatar', 'App\Api\v1\Controllers\UserFileController@setSelfAvatar');
    $api->get('/files/users/avatar', 'App\Api\v1\Controllers\UserFileController@getSelfAvatarMaxSize');
    $api->get('/files/users/avatar/{size}', 'App\Api\v1\Controllers\UserFileController@getSelfAvatar');
    $api->get('/files/users/{user_id}/avatar', 'App\Api\v1\Controllers\UserFileController@getAvatarMaxSize');
    $api->get('/files/users/{user_id}/avatar/{size}', 'App\Api\v1\Controllers\UserFileController@getAvatar');
    // name card cover
    $api->post('/files/users/name_card_cover', 'App\Api\v1\Controllers\UserFileController@setSelfNameCardCover');
    $api->get('/files/users/name_card_cover', 'App\Api\v1\Controllers\UserFileController@getSelfNameCardCover');
    $api->get('/files/users/{user_id}/name_card_cover', 'App\Api\v1\Controllers\UserFileController@getNameCardCover');
    // name card photo
    $api->post('/files/users/name_card_photo', 'App\Api\v1\Controllers\UserFileController@updateNameCardPhoto');
    $api->delete('/files/users/name_card_photo/{position}', 'App\Api\v1\Controllers\UserFileController@deleteNameCardPhoto');
    $api->get('/files/users/name_card_photo/{position}', 'App\Api\v1\Controllers\UserFileController@getSelfNameCardPhoto');
    $api->get('/files/users/{user_id}/name_card_photo/{position}', 'App\Api\v1\Controllers\UserFileController@getNameCardPhoto');
    // char room cover
    $api->post('/files/chat_rooms/cover_image', 'App\Api\v1\Controllers\ChatRoomFileController@setChatRoomCoverImage');
    $api->get('/files/chat_rooms/{chat_room_id}/cover_image', 'App\Api\v1\Controllers\ChatRoomFileController@getChatRoomCoverImage');
    $api->get('/files/places/{place_id}', 'App\Api\v1\Controllers\PlaceController@getImage');
});