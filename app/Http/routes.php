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

define('THUMBS_DIR_PATH', 'http://'.$_SERVER['HTTP_HOST']."/uploads/");

Route::get('/', 'WelcomeController@index');

Route::get('home', 'HomeController@index');

Route::controllers([
	'auth' => 'Auth\AuthController',
	'password' => 'Auth\PasswordController',
]);

Route::group(array('prefix'=>'api'),function(){
Route::post('signup', array('uses' => 'ApiController@signUp', 'as' => 'api.signUp'));
Route::post('login',array('uses'=>'ApiController@login', 'as'=>'api.login'));
Route::post('logout',array('uses'=>'ApiController@logout', 'as'=>'api.logout'));
Route::post('edit_profile',array('uses'=>'ApiController@editProfile','as'=>'api.editProfile'));
Route::post('update_profile',array('uses'=>'ApiController@updateProfile','as'=>'api.updateProfile'));
Route::post('edit_video',array('uses'=>'ApiController@editVideo','as'=>'api.editVideo'));
Route::post('add_attachment',array('uses'=>'ApiController@addAttachments', 'as'=>'api.addAttachments'));
Route::post('delete_attachment',array('uses'=>'ApiController@deleteAttachment','as'=>'api.deleteAttachment'));
Route::post('add_job',array('uses'=>'ApiController@addJob','as'=>'api.addJob'));
Route::post('add_qualification',array('uses'=>'ApiController@addQualification','as'=>'api.addQualification'));
Route::post('add_testimonial',array('uses'=>'ApiController@addTestimonial','as'=>'api.addTestimonial'));
Route::post('edit_testimonial',array('uses'=>'ApiController@editTestimonial','as'=>'api.editTestimonial'));
Route::post('delete_testimonial',array('uses'=>'ApiController@deleteTestimonial','as'=>'api.deleteTestimonial'));
Route::post('get_user_profile',array('uses'=>'ApiController@getUserProfile','as'=>'api.getUserProfile'));
Route::post('search_users',array('uses'=>'ApiController@searchUsers','as'=>'api.searchUsers'));
Route::post('search_hash',array('uses'=>'ApiController@searchHash','as'=>'api.searchHash'));
Route::post('get_hashed_users',array('uses'=>'ApiController@HashUsers','as'=>'api.HashUsers'));
Route::post('update_user_settings',array('uses'=>'ApiController@updateUserSettings','as'=>'api.updateUserSettings'));



/*
|--------------------------------------------------------------------------
| Friend Routes
|--------------------------------------------------------------------------
*/

Route::post('follow_user',array('uses'=>'ApiController@FollowUser','as'=>'api.FollowUser'));
Route::post('unfollow_user',array('uses'=>'ApiController@UnfollowUser','as'=>'api.UnfollowUser'));
Route::post('get_friends',array('uses'=>'ApiController@getFriends','as'=>'api.getFriends'));

/*
|--------------------------------------------------------------------------
| Friend Routes
|--------------------------------------------------------------------------
*/



Route::post('get_notifications',array('uses'=>'ApiController@getNotifications','as'=>'api.getNotifications'));
Route::post('set_notification_status',array('uses'=>'ApiController@SetNotificationStatus','as'=>'api.SetNotificationStatus'));



/*
|--------------------------------------------------------------------------
| Messaging Routes
|--------------------------------------------------------------------------
*/

Route::post('send_message',array('uses'=>'ApiController@sendMessage','as'=>'api.sendMessage'));
Route::post('get_messages',array('uses'=>'ApiController@getMessages','as'=>'api.getMessages'));
Route::post('get_messages_after',array('uses'=>'ApiController@getMessagesAfter','as'=>'api.getMessagesAfter'));
Route::post('get_messages_before',array('uses'=>'ApiController@getMessagesBefore','as'=>'api.getMessagesBefore'));
Route::post('get_last_chat',array('uses'=>'ApiController@getLastChat','as'=>'api.getLastChat'));
Route::post('delete_chat',array('uses'=>'ApiController@deleteChat','as'=>'api.deleteChat'));

/*
|--------------------------------------------------------------------------
| Messaging Routes
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Post Routes
|--------------------------------------------------------------------------
*/

Route::post('add_post',array('uses'=>'ApiController@addPost','as'=>'api.addPost'));
Route::post('edit_post',array('uses'=>'ApiController@editPost','as'=>'api.editPost'));
Route::post('add_comment',array('uses'=>'ApiController@addComment','as'=>'api.addComment'));
Route::post('like_post',array('uses'=>'ApiController@likePost','as'=>'api.likePost'));
Route::post('unlike_post',array('uses'=>'ApiController@unlikePost','as'=>'api.unlikePost'));
Route::post('delete_post',array('uses'=>'ApiController@deletePost','as'=>'api.deletePost'));
Route::post('get_comments',array('uses'=>'ApiController@getComments','as'=>'api.getComments'));
Route::post('delete_comment',array('uses'=>'ApiController@deleteComment','as'=>'api.deleteComment'));
Route::post('get_post_detail',array('uses'=>'ApiController@getPostDetail','as'=>'api.getPostDetail'));
Route::post('get_feeds',array('uses'=>'ApiController@getFeeds','as'=>'api.getFeeds'));
Route::post('get_popular_posts',array('uses'=>'ApiController@getPopularPosts','as'=>'api.getPopularPosts'));
Route::post('get_user_posts',array('uses'=>'ApiController@getUserPosts','as'=>'api.getUserPosts'));

/*
|--------------------------------------------------------------------------
| Post Routes
|--------------------------------------------------------------------------
*/

});


// Image Routes
Route::get('photos/thumb/{id}/{ext}', function($id, $ext)
{
    // $img = Image::make('uploads/'.$id.'.'.$ext);
    $img = Image::make('https://s3-us-west-2.amazonaws.com/cbrealestate/connected_uploads/'.$id.'.'.$ext);
    return $img->response($img);
});

Route::get('photos/thumb/{id}/{ext}/{width}', function($id, $ext, $width)
{
    // $img = Image::make('uploads/'.$id.'.'.$ext);
    $img = Image::make('https://s3-us-west-2.amazonaws.com/cbrealestate/connected_uploads/'.$id.'.'.$ext);
    $img->resize($width, null, function ($constraint) {
        $constraint->aspectRatio();
    });
    return $img->response($img);
});

Route::get('photos/thumb/{id}/{ext}/{width}/{height}', function($id, $ext, $width, $height)
{
    // $img = Image::make('uploads/'.$id.'.'.$ext);
    $img = Image::make('https://s3-us-west-2.amazonaws.com/cbrealestate/connected_uploads/'.$id.'.'.$ext);
    $img->resize($width, $height);
    return $img->response($img);
});

// testing push notifications

// Route::get('/testing-push', function(){

//     // message to be send in push
//     $message = PushNotification::Message('Mr.Abc has requested for meeting',array(
//         'badge' => 1,
//         'sound' => 'example.aiff',


//         'locArgs' => array(
//             'u1' => 3,
//             'u2' => 2,
//             'mid' => 7,
//         ),
//     ));
//     // send push
//     $send = PushNotification::app('appNameIOS')
//         ->to('84271d42284100e0e53c35f9cd2656bdbd2dc2afe8c0eb3dc83e786080d23878')
//         ->send($message);


//     /*$send = PushNotification::app('appNameIOS')
//     ->to('7d1195ae9b14d962dde3f383bdefb4cfafce2dfd4e57a308556647906d8860e0')
//     ->send(); */

//     dd($send);
//     if($send)
//         echo('Sent!');
//     else
//         echo('Not sent!');
// });

Route::get('testing-push-ios', function(){

   $send = PushNotification::app('appNameIOS')
    ->to('75f7abe210345a4201efdb71c4351fd4cdd02f1077f6226eaf797bd35ef6d007')
    ->send('Dummy push, from server'); 

    dd($send);

});

Route::get('testing-push-android', function(){

   $send = PushNotification::app('appNameAndroid')
    ->to('APA91bH3PY1qGMsLIJ-lJMOETw3OZGbjBxhQM0BwKdNQZ_Ym1hSapbOmqpwBmOePZYaVF-AZVSZa8EluFdc1KXDWfebx2p50iRbshCcxyNjJoiNXnGqbZKuOZTfPB5Q9Rr3emw9shTGK')
    ->send('Dummy push, from server'); 

    dd($send);

});

// Test S3 image upload
Route::post('api/test_s3_upload', array('uses' => 'ApiController@testS3Upload', 'as' => 'api.testS3Upload'));