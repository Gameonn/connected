<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Users;
use App\Posts;
use App\Messages;
use App\Notifications;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
// use Illuminate\Contracts\Filesystem\Factory as Filesystem;
use AWS;  
use Illuminate\Contracts\Filesystem\Filesystem;
// use File;
// use PushNotification;
use Storage;
 

class ApiController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//
	}

	 public function signUp() {
	 	
        $input = Input::all();
        return Users::signUp($input);
    }

     public function login() {
        $input = Input::all();
        return Users::login($input);
    }

    public function logout() {
        $input = Input::all();
        return Users::logout($input);
    }

    public function editProfile() {
        $input = Input::all();
        return Users::editProfile($input);
    }

    public function editVideo() {
        $input = Input::all();
        return Users::editVideo($input);
    }

    public function updateProfile() {
        $input = Input::all();
        return Users::updateProfile($input);
    }

    public function updateUserSettings() {
        $input = Input::all();
        return Users::updateUserSettings($input);
    }

    public function searchUsers() {
        $input = Input::all();
        return Users::searchUsers($input);
    }

    public function searchHash() {
        $input = Input::all();
        return Users::searchHash($input);
    }

    public function HashUsers() {
        $input = Input::all();
        return Users::HashUsers($input);
    }


    public function addAttachments(){
    	$input=Input::all();
    	return Users::addAttachments($input);

    }


    public function deleteAttachment(){
        $input=Input::all();
        return Users::deleteAttachment($input);

    }
	
	public function getUserProfile(){
    	$input=Input::all();
    	return Users::getUserProfile($input);

    }

    public static function addJob(){

    	$input=Input::all();
    	return Users::addJob($input);
    }

    public static function addQualification(){

    	$input=Input::all();
    	return Users::addQualification($input);
    }

    public static function addTestimonial(){

    	$input=Input::all();
    	return Users::addTestimonial($input);
    }

    public static function editTestimonial(){

    	$input=Input::all();
    	return Users::editTestimonial($input);
    }

  	public static function deleteTestimonial(){

    	$input=Input::all();
    	return Users::deleteTestimonial($input);
    }


    public static function addPost(){

    	$input=Input::all();
    	return Posts::addPost($input);
    }

    public static function editPost(){

    	$input=Input::all();
    	return Posts::editPost($input);
    }


    public static function addComment(){

    	$input=Input::all();
    	return Posts::addComment($input);
    }

    public static function likePost(){

    	$input=Input::all();
    	return Posts::likePost($input);
    }

    public static function unlikePost(){

    	$input=Input::all();
    	return Posts::unlikePost($input);
    }

    public static function getComments(){

    	$input=Input::all();
    	return Posts::getComments($input);
    }
	
	
	public static function FollowUser(){

    	$input=Input::all();
    	return Users::FollowUser($input);
    }
	
	
	public static function UnfollowUser(){

    	$input=Input::all();
    	return Users::FollowUser($input);
    }
	
	public static function getFriends(){
	
		$input=Input::all();
		return Users::getFriends($input);
	}
	

     public static function deletePost(){

    	$input=Input::all();
    	return Posts::deletePost($input);
    }

      public static function deleteComment(){

    	$input=Input::all();
    	return Posts::deleteComment($input);
    }

    public static function getPostDetail(){

        $input=Input::all();
        return Posts::getPostDetail($input);
    }
	
	public static function getFeeds(){

        $input=Input::all();
        return Posts::getFeeds($input);
    }

    public static function getPopularPosts(){

        $input=Input::all();
        return Posts::getPopularPosts($input);
    }
	
	public static function getUserPosts(){

        $input=Input::all();
        return Posts::getUserPosts($input);
    }
	
	public static function getNotifications(){

        $input=Input::all();
        return Notifications::getNotifications($input);
    }
	
	
	public static function SetNotificationStatus(){

        $input=Input::all();
        return Notifications::SetNotificationStatus($input);
    }


    public static function sendMessage(){

        $input=Input::all();
        return Messages::saveUserMessage($input);
    }


    public static function getMessages(){

        $input=Input::all();
        return Messages::getUserMessages($input);
    }


    public static function getMessagesBefore(){

        $input=Input::all();
        return Messages::getUserMessagesBefore($input);
    }


    public static function getMessagesAfter(){

        $input=Input::all();
        return Messages::getRecUserMessagesAfter($input);
    }


    public static function getLastChat(){

        $input=Input::all();
        return Messages::getLastChat($input);
    }


    public static function deleteChat(){

        $input=Input::all();
        return Messages::deleteChat($input);
    }


     public function testS3Upload() {
    /* $image = $request->file('image');

     $imageFileName = time() . '.' . $image->getClientOriginalExtension();
     $s3 = \Storage::disk('s3');
    $filePath = '/connected_uploads/' . $imageFileName;
    $s3->put($filePath, file_get_contents($image), 'public');*/
       
      $input = Input::all();
        $image = $input['image'];    

        $filename = time().''.str_random(30).'.jpg';

        $s3 = AWS::get('s3');
        $s3->putObject(array(
            'Bucket'     => 'cbrealestate',
            'Key'        => 'connected_uploads/'.$filename,
            'SourceFile' => $image->getPathname(),
        ));
        die;
        //return true;
    }

}
