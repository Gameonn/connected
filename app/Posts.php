<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Users;
use App\Notifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use DateTime;


class Posts extends Model {


		public static $addPostRules=array(
	   	'token' => 'required',
	   	'title' => 'required',
	   	'skillset'=>'required',
	   	'place_id'=>'required',
	   	'min_age' => 'required',
	   	'max_age' => 'required'
	   	//'post_image' => 'required'

	   	);

	   	public static $editPostRules=array(
	   	'token' => 'required',
	   	'title' => 'required',
	   	'skillset'=>'required',
	   	//'post_image' => 'required',
	   	'post_id' => 'required'

	   	);

	   	public static $likePostRules=array(
	   	'token' => 'required',
	   	'post_id' => 'required'

	   	);

	   	public static $addCommentRules=array(
	   	'token' => 'required',
	   	'comment' => 'required',
	   	'post_id' => 'required'

	   	);
		
		public static $FeedRules=array(
		'token'=>'required'
		);
		
		public static $UserPostsRules=array(
		'token'=>'required',
		'other_id'=>'required'
		);

	
		public static function getPostOwner($post_id){
		
		 $post_owner_id= DB::table('posts')
						->select('user_id')
						->where('id', $post_id)
						->first();
						
			return $post_owner_id->user_id;			
		
		}


		public static function getLatlng($place_id){
	
		 $geocode=file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?placeid='.$place_id.'&key=AIzaSyBwGUURIvvQm2hRDAU-0NowiTJHJtmb-uU');
                $output= json_decode($geocode,true); 

				if(count($output['result']['geometry']))
					return $output['result']['geometry'];                        
				else
				return [];
				
		}
	
	
		public static function time_since($created_on){
	
			$result=DB::select('SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP("'.$created_on.'") as time_diff');
			
			$diff=$result[0]->time_diff;
		
			if($diff < 60){
				$response = $diff.' s ago';
			}elseif($diff < 3600){
				$response = floor($diff / 60).' mins ago';	
			}elseif($diff < 86400){
				$response = floor($diff / 3600).' hrs ago';
			}elseif($diff < 2592000){
				$response = floor($diff / 86400).' days ago';
			}elseif($diff < 31104000){
				$response = floor($diff / 2592000).' months ago';
			}else{
				$response = floor($diff / 31104000).' year ago';
			}
		
		return $response;
		}
	
		public static function addPost($input){
    	
    	 $validation = Validator::make($input, Posts::$addPostRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $title = $input['title'];
        	  $skillset=$input['skillset'];
        	  $place_id=$input['place_id'];
        	  $min_age=$input['min_age'];
        	  $max_age=$input['max_age'];
        	  $post_desc=isset($input['post_desc'])?$input['post_desc']:"";
        	  $post_image = Input::file('post_image');
		      $post_video = Input::file('post_video');

		      $current_time = new DateTime();
		      if(is_string($skillset)){
				$skillset=str_replace('"',"", $skillset);
				$skillset=explode(",",$skillset);
		      }
		      else{
		    	$skillset=json_decode($skillset,true);		      	
		      }

      		  $user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            
		               	$place_data=Posts::getLatlng($place_id);
						$lat= $place_data['location']['lat']?$place_data['location']['lat']:'0';
						$lng=$place_data['location']['lng']?$place_data['location']['lng']:'0';		            	

		          		//users with matching skillset
		            	$user_data=DB::table('user_skillset')
		            				->selectRaw('users.apn_id,users.reg_id,users.push_notification')
		            				->Join('users','users.id','=','user_skillset.user_id')
		            				->whereIN('user_skillset.skillset_id',$skillset)
		            				->groupBy('users.id')
		            				->get();

		            	if($post_image=="")               
		                $image = "";
		           		else
		                $image = Users::uploadPostFiles('post_image');
		            

				    	$notify_message="New Post matching your skillset";
		            	if($post_video=="")               
		                $video = "";
		           		else
		                $video = Users::uploadPostFiles('post_video');
		           
		           		$post_id=DB::table('posts')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'title' => $title,
		            			'post_desc' => $post_desc,
		            			'post_image' =>  $image,
		            			'post_video' => $video,
		            			'min_age'=> $min_age,
		            			'max_age' => $max_age,
		            			'post_lat'=>$lat,
		            			'post_lng'=>$lng,
		            			'created_at' => $current_time,	
		            			)
		            	   	);
		           		
		           		if($skillset){
	            		 	foreach ($skillset as $key => $value) {
			            	
	            		 	$skill[$key]= DB::table('post_skillset')->select('*')->where('post_id', $post_id)->where('user_id', $user_id)->where('skillset_id',$value)->get();

		            		 	if(!$skill[$key]){
		            		 		$post_skill=DB::table('post_skillset')->insertGetId(
				            		array(
				            			'user_id' => $user_id,
				            			'post_id' => $post_id,
				            			'skillset_id' => $value,
				            			'created_at' => $current_time,	
				            			)
				            		);
		            		 	}
			            	
			            	}
		            	}		            	

		            	$post_desc=Posts::PostDesc($user_id,$post_id);

		           		foreach($user_data as $row){
		           			if($row->push_notification){

				    			Notifications::SendPushNotification($notify_message,'',$row->apn_id,3,$post_id,$user_id);
					    		Notifications::SendPushNotification($notify_message,$row->reg_id,'',3,$post_desc,$user_id);
		           			}
	            		}

		            if($post_id)
		            return Response::json(array('status'=>'1', 'msg'=>'New Post Added'), 200);
		        	else
		        	return Response::json(array('status'=>'0','msg'=>"Error"), 200);
		        	
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
        }

    }


		public static function editPost($input){
    	
    	 $validation = Validator::make($input, Posts::$editPostRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $title = $input['title'];
        	  $post_id=$input['post_id'];
        	  $skillset=$input['skillset'];
        	  $post_desc=isset($input['post_desc'])?$input['post_desc']:"";
        	  $post_image = Input::file('post_image');
		      $post_video = Input::file('post_video');

		      $current_time = new DateTime();


		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            
		            $post_data= DB::table('posts')
		            			->select('*')
		            			->where('title', $title)
		            			->where('user_id', $user_id)
		            			->first();

		            if($post_data){

		            	if($post_image=="")               
		                $image = "";
		            	else
		                $image = Users::uploadPostFiles('post_image');
		            

		            	if($post_video=="")               
		                $video = "";
		                else
		                $video = Users::uploadPostFiles('post_video');
		            

		           if($video) 
		           DB::table('posts')->where('user_id', $user_id)->where('id',$post_id)->update(['title' => $title, 'post_desc' => $post_desc, 'post_image' => $image, 'post_video' => $video]);
		       	   else
		       	   DB::table('posts')->where('id', $post_id)->where('user_id',$user_id)->update(['title' => $title, 'post_desc' => $post_desc, 'post_image' => $image]);

		       		if($skillset){

						$set="";
						foreach($skillset as $key=>$val){
						$m=str_replace(' ', '', $val);
						$set.='"'.$m.'",';
						}

						$num= rtrim($set, ', ');
						DB::table('post_skillset')->select('*')->where('user_id', $user_id)->where('post_id',$post_id)->whereRaw('skillset_id NOT IN'.'('.$num.')' )->delete();
    	

		       			foreach ($skillset as $key => $value) {
			            	
			            	$skill[$key]= DB::table('post_skillset')->select('*')->where('post_id', $post_id)->where('user_id', $user_id)->where('skillset_id',$value)->get();

				            	if(!$skill[$key]){
				            		$post_skill=DB::table('post_skillset')->insertGetId(
				            		array(
				            			'user_id' => $user_id,
				            			'post_id' => $post_id,
				            			'skillset_id' => $value,
				            			'created_at' => $current_time,	
				            			)
				            		);
				            	}
			            	
			            	}
		            	}	
		           
		           return Response::json(array('status'=>'1', 'msg'=>'Post Updated'), 200);
		        	}
		        	else{
		        		return Response::json(array('status'=>'2', 'msg'=>'Post doesnot exists'),200);
		        	}


		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
        }

    }



		public static function addComment($input){
    	
    	 $validation = Validator::make($input, Posts::$addCommentRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $comment = $input['comment'];
        	  $post_id=$input['post_id'];

		      $current_time = new DateTime();
				

		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            $other_id=Posts::getPostOwner($post_id);

		            $uname=Users::getUsername($user_id);
		            $push_status=Users::getPushSettings($other_id);
		            $user_det=Users::getUserType($user_id);
	          		$user_type=$user_det->user_type;

	            	$push_ids=Users::getUserPushIds($other_id);
	            	$reg_id=$push_ids->reg_id;
	            	$device_token=$push_ids->apn_id;

		            $comment_id=DB::table('comments')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'post_id' => $post_id,
		            			'comment' => $comment,
		            			'created_at' => $current_time,	
		            			)
		            	);
						  
					  	if($user_id!=$other_id){

					  	  $post_desc=Posts::PostDesc($other_id,$post_id);
						  $notify_message=$uname." commented on your post";
					 	  $Ninput['sender_id']=$user_id;
						  $Ninput['receiver_id']= Posts::getPostOwner($post_id);
						  $Ninput['type_id']=$comment_id;
						  $Ninput['type']='comment';
						  $Ninput['title']=$notify_message;
						  
					  	  Notifications::addNotification($Ninput);	

				  	  	if($push_status){
		  	  			  Notifications::SendPushNotification($notify_message,'',$device_token,1,$post_id,$user_id);
				    	  Notifications::SendPushNotification($notify_message,$reg_id,'',1,$post_desc,$user_type);
						 	
				  	  	}
				    	  
					 	}
		          		
				    return Response::json(array('status'=>'1', 'msg'=>'New comment on post'), 200);
   		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
        }

    }
	
	
		public static function deleteComment($input){
    	
    	 $validation = Validator::make($input, Posts::$likePostRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $post_id = $input['post_id'];

		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            
		            $comment_data= DB::table('comments')
		            			->select('id')
		            			->where('post_id', $post_id)
		            			->where('user_id', $user_id)
		            			->first();
								
						 $comment_id= DB::table('comments')
		            			->select('id')
		            			->where('post_id', $post_id)
		            			->where('user_id', $user_id)
		            			->delete();	
					
					 	  $Ninput['sender_id']=$user_id;
						  $Ninput['receiver_id']= Posts::getPostOwner($post_id);
						  $Ninput['type_id']=$comment_data->id;
						  $Ninput['type']='comment';
						  
						  Notifications::RemoveNotification($Ninput);
					
		            if($comment_id){

		            return Response::json(array('status'=>'1', 'msg'=>'Comment Deleted'), 200);
		        	}
		        	
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
			}

		}


		public static function deletePost($input){
    	
    	 $validation = Validator::make($input, Posts::$likePostRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $post_id = $input['post_id'];

		      	$user_id=Users::getUserIdByToken($access_token);

	          	if($user_id){
		            
		            $post_data= DB::table('posts')
		            			->select('id')
		            			->where('id', $post_id)
		            			->where('user_id', $user_id)
		            			->delete();
		
		            return Response::json(array('status'=>'1', 'msg'=>'Post Deleted'), 200);
		        }
	          	else{
	          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
	          	}
		            
			}

		}



		public static function likePost($input){
    	
			$validation = Validator::make($input, Posts::$likePostRules);
			if($validation->fails()) {
				return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
			}
			else {
        	  $access_token=$input['token'];
        	  $post_id = $input['post_id'];

		      $current_time = new DateTime();

		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            $uname=Users::getUsername($user_id);
		            $other_id=Posts::getPostOwner($post_id);
		            $push_status=Users::getPushSettings($other_id);
		            $user_det=Users::getUserType($user_id);
	          		$user_type=$user_det->user_type;
	            	$push_ids=Users::getUserPushIds($other_id);
	            	$reg_id=$push_ids->reg_id;
	            	$device_token=$push_ids->apn_id;
					
		            $post_data= DB::table('likes')
		            			->select('*')
		            			->where('post_id', $post_id)
		            			->where('user_id', $user_id)
		            			->first();

		            if(!$post_data){

		            $like_id=DB::table('likes')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'post_id' => $post_id,
		            			'created_at' => $current_time,	
		            			)
		            	);
						  
		            	if($user_id!=$other_id){

            			  $post_desc=Posts::PostDesc($other_id,$post_id);
						  $notify_message=$uname." liked your post";
					 	  $Ninput['sender_id']=$user_id;
						  $Ninput['receiver_id']= Posts::getPostOwner($post_id);
						  $Ninput['type_id']=$like_id;
						  $Ninput['type']='like';
						  $Ninput['title']=$notify_message;
						  
						  Notifications::addNotification($Ninput);

						  if($push_status){
					  		
					  		Notifications::SendPushNotification($notify_message,'',$device_token,2,$post_id,$user_id);
				    	  	Notifications::SendPushNotification($notify_message,$reg_id,'',2,$post_desc,$user_type);
						  }
				    	  
		            	}
	          	 	      

		            if($like_id)
		            return Response::json(array('status'=>'1', 'msg'=>'Post Liked'), 200);
		        	else
		        	return Response::json(array('status'=>'0','msg'=>"Error"), 200);
		        	}
		        	else{
		        		return Response::json(array('status'=>'2', 'msg'=>'Post Already liked by user'),200);
		        	}
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
			}

		}


    	public static function unlikePost($input){
    	
    	 $validation = Validator::make($input, Posts::$likePostRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $post_id = $input['post_id'];

		      $current_time = new DateTime();


		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            
		            $like_data= DB::table('likes')
		            			->select('id')
		            			->where('post_id', $post_id)
		            			->where('user_id', $user_id)
		            			->first();
								
						 $like_id= DB::table('likes')
		            			->select('id')
		            			->where('post_id', $post_id)
		            			->where('user_id', $user_id)
		            			->delete();	
					
					 	  $Ninput['sender_id']=$user_id;
						  $Ninput['receiver_id']= Posts::getPostOwner($post_id);
						  $Ninput['type_id']=$like_data->id;
						  $Ninput['type']='like';
						  
						  Notifications::RemoveNotification($Ninput);
					
		            if($like_id){

		           	 return Response::json(array('status'=>'1', 'msg'=>'Post Unliked'), 200);
		        	}
		        	
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
        }

    }
	
	
		public static function getComments($input){

    	 $validation = Validator::make($input, Posts::$likePostRules);//like post rules common with get comments
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {

        	  $access_token=$input['token'];
        	  $post_id = $input['post_id'];

		      $current_time = new DateTime();


		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
											
		            $comment_data= DB::table('comments')
		            			->selectRaw("comments.*,(SELECT users.full_name from users where users.id=comments.user_id) as uname,
											(SELECT users.user_type from users where users.id=comments.user_id) as user_type,
											(SELECT users.profile_pic from users where users.id=comments.user_id) as c_profile_pic,
											CASE 
										  WHEN DATEDIFF(UTC_TIMESTAMP(),comments.created_at) != 0 THEN CONCAT(DATEDIFF(UTC_TIMESTAMP(),comments.created_at) ,'d ago')
										  WHEN HOUR(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) ,'h ago')
										  WHEN MINUTE(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) ,'m ago')
										  ELSE
											 CONCAT(SECOND(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) ,'s ago')
											END as comment_time")
		            			->where('post_id', $post_id)
		            			->orderBy('comments.created_at', 'DESC')
		            			->get();
								
								foreach($comment_data as $row){
								
								//$row->comment_time = Posts::time_since($row->created_at);
								$row->c_profile_pic=$row->c_profile_pic?Users::getFormattedImage($row->c_profile_pic):"";
								
								}

		           
		            if($comment_data)
					return Response::json(array('status'=>'1', 'msg'=>'Records Found', 'data'=>$comment_data), 200);
		        	else
		        	return Response::json(array('status'=>'2','msg'=>"No Record Found"), 200);
		        	
		        	
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
        }

        }
	

        public static function getPopularPosts($input){

        	$validation = Validator::make($input, Posts::$FeedRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	$access_token=$input['token'];

        	$user_id=Users::getUserIdByToken($access_token);
				
		          if($user_id){
	          		// $user_latlong=Users::getUserLatLong($user_id);
	          		$user_det=Users::getUserType($user_id);
	          		$user_type=$user_det->user_type;
					// $latitude=$user_latlong->lat;
					// $longitude=$user_latlong->lng;
	          		$vid_path='https://s3-us-west-2.amazonaws.com/cbrealestate/connected_uploads/';

					if($user_type=='employer'){

					 $popular_posts=DB::select("SELECT users.id,full_name,profile_pic,profile_thumb,profile_video,user_type,count(*) as follow_count FROM `users` JOIN follow ON follow.follow_to=users.id WHERE user_type='employer' AND profile_video!='' GROUP BY follow_to 
									UNION
									SELECT users.id,full_name,profile_pic,profile_thumb,profile_video,user_type,0 as follow_count FROM `users` JOIN user_skillset ON user_skillset.user_id=users.id JOIN post_skillset ON post_skillset.skillset_id=user_skillset.skillset_id WHERE  profile_video!='' AND post_skillset.skillset_id IN (SELECT skillset_id from post_skillset WHERE post_skillset.user_id=".$user_id.")");
		            }
		            else{

		                	$popular_posts=DB::select("SELECT users.id,full_name,profile_pic,profile_thumb,profile_video,user_type,0 as follow_count FROM `users` JOIN user_skillset ON user_skillset.user_id=users.id WHERE  profile_video!='' AND user_skillset.skillset_id IN (SELECT skillset_id FROM user_skillset WHERE user_skillset.user_id=".$user_id.")
												UNION
												SELECT users.id,full_name,profile_pic,profile_thumb,profile_video,user_type,0 as follow_count FROM `users` JOIN post_skillset ON post_skillset.user_id=users.id JOIN user_skillset ON user_skillset.skillset_id=post_skillset.skillset_id WHERE  profile_video!='' AND post_skillset.skillset_id IN (SELECT skillset_id from user_skillset WHERE user_skillset.user_id=".$user_id.")");
		            
		            }
		            		foreach($popular_posts as $row){
								
								// $row->distance=$row->distance?$row->distance:"0";
								$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
								$row->profile_thumb=$row->profile_thumb?Users::getFormattedImage($row->profile_thumb):"";
								$row->profile_video=$row->profile_video?$vid_path.$row->profile_video:"";
								}

					 if($popular_posts)
					return Response::json(array('status'=>'1', 'msg'=>'Records Found', 'data'=>$popular_posts), 200);
		        	else
		        	return Response::json(array('status'=>'2','msg'=>"No Record Found"), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        	}	
        }

	
		public static function getFeeds($input){

    	 $validation = Validator::make($input, Posts::$FeedRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {

        	  $access_token=$input['token'];
        	  $post_id = $input['post_id'];
        	  $skillsets=isset($input['skillset'])?$input['skillset']:"";
        	  $min_age=isset($input['min_age'])?$input['min_age']:"";
        	  $max_age=isset($input['max_age'])?$input['max_age']:"";
        	  $discovery_radius=isset($input['discovery_radius'])?$input['discovery_radius']:'0';

				$final=array();
				$result=array();
		      	$current_time = new DateTime();
		      	$ageClause="";
				$skillsetclause="";
				$distanceClause="";

		        $vid_path="https://s3-us-west-2.amazonaws.com/cbrealestate/connected_uploads/";
		      	$user_id=Users::getUserIdByToken($access_token);
				
		          if($user_id){
					
					$user_latlong=Users::getUserLatLong($user_id);
					$latitude=$user_latlong->lat;
					$longitude=$user_latlong->lng;					
					$all_skillset=Users::Getskillset();
					
					if($post_id)
						$whereclause='posts.id <'.$post_id;
					else									
						$whereclause= 'posts.id >'.$post_id;
					

					if($skillsets){
						$skills=str_replace('"',"", $skillsets);
						$skillsetclause=" posts.id IN (SELECT post_id FROM post_skillset WHERE skillset_id IN ($skills)) OR ";
					}

					if($min_age && $max_age){
						$ageClause="AND temp.min_age>=".$min_age." AND temp.max_age<=".$max_age;
					}

					if($discovery_radius)
						$distanceClause=" WHERE temp.distance <= ".$discovery_radius;
				

					$feed_data=DB::select("SELECT temp.* FROM (SELECT users.user_type,users.full_name,users.profile_pic,users.lat,users.lng,users.id as uid,posts.*,posts.id as pid,(select count(likes.id) from likes where likes.post_id=posts.id) as likes_count,
									(SELECT count(likes.id) from likes where likes.post_id=posts.id and likes.user_id=".$user_id.") as is_liked,comments.id as cid,comments.user_id as c_uid,
									comments.comment,(select users.full_name from users where users.id=comments.user_id) as un,
									(SELECT FLOOR(DATEDIFF( NOW(),users.dob) / 365.25)) as age,
									(SELECT group_concat(CONCAT('#',skillset.skill) SEPARATOR ' ') from skillset JOIN post_skillset ON post_skillset.skillset_id=skillset.id WHERE post_skillset.post_id=posts.id) as skills,
									(SELECT users.user_type from users where users.id=comments.user_id) as cutype,
									(SELECT users.profile_pic from users where users.id=comments.user_id) as c_profile_pic,
									TRUNCATE(( 3961 * acos( cos( radians( ".$latitude." ) ) * cos( radians( `post_lat` ) ) * cos( radians( `post_lng` ) - radians( ".$longitude." ) ) + sin( radians( ".$latitude." ) ) * sin( radians( `post_lat` ) ) ) ),2) AS distance,
									CASE 
									WHEN DATEDIFF(UTC_TIMESTAMP(),posts.created_at) != 0 THEN CONCAT(DATEDIFF(UTC_TIMESTAMP(),posts.created_at) ,'d ago')
									WHEN HOUR(TIMEDIFF(UTC_TIMESTAMP(),posts.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(UTC_TIMESTAMP(),posts.created_at)) ,'h ago')
									WHEN MINUTE(TIMEDIFF(UTC_TIMESTAMP(),posts.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(UTC_TIMESTAMP(),posts.created_at)) ,'m ago')
									ELSE
									CONCAT(SECOND(TIMEDIFF(UTC_TIMESTAMP(),posts.created_at)) ,' s ago')
									END as post_time,
									CASE 
									WHEN DATEDIFF(UTC_TIMESTAMP(),comments.created_at) != 0 THEN CONCAT(DATEDIFF(UTC_TIMESTAMP(),comments.created_at) ,'d ago')
									WHEN HOUR(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) ,'h ago')
									WHEN MINUTE(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) ,'m ago')
									ELSE
									CONCAT(SECOND(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) ,' s ago')
									END as comment_time from `posts` left join `users` on `users`.`id` = `posts`.`user_id` 
									left join `comments` on `comments`.`post_id` = `posts`.`id` where ".$whereclause." and 
									(".$skillsetclause." posts.user_id IN (select follow.follow_to from follow WHERE follow.follow_by=".$user_id." and follow.status=1) or posts.user_id = ".$user_id.")) as temp ".$distanceClause." ". $ageClause." ORDER BY `temp`.`pid` DESC");
		           
				   			// $feed_data= DB::table('posts')
	     					//  ->selectRaw("users.*,users.id as uid,posts.*,posts.id as pid,(select count(likes.id) from likes where likes.post_id=posts.id) as likes_count,
							// 	(select count(likes.id) from likes where likes.post_id=posts.id and likes.user_id='$user_id') as is_liked,comments.id as cid,comments.user_id as c_uid,
							// 	comments.*,(select users.full_name from users where users.id=comments.user_id) as un,
							// 	(select group_concat(CONCAT('#',skillset.skill) SEPARATOR '  ') from skillset JOIN post_skillset ON post_skillset.skillset_id=skillset.id WHERE post_skillset.post_id=posts.id) as skills,
							// 	(select users.user_type from users where users.id=comments.user_id) as cutype,
							// 	(select users.profile_pic from users where users.id=comments.user_id) as c_profile_pic,
							// 	TRUNCATE(( 3961 * acos( cos( radians( ".$latitude." ) ) * cos( radians( `post_lat` ) ) * cos( radians( `post_lng` ) - radians( ".$longitude." ) ) + sin( radians( ".$latitude." ) ) * sin( radians( `post_lat` ) ) ) ),2) AS distance,
							// 	CASE 
							// 	  WHEN DATEDIFF(UTC_TIMESTAMP(),posts.created_at) != 0 THEN CONCAT(DATEDIFF(UTC_TIMESTAMP(),posts.created_at) ,'d ago')
							// 	  WHEN HOUR(TIMEDIFF(UTC_TIMESTAMP(),posts.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(UTC_TIMESTAMP(),posts.created_at)) ,'h ago')
							// 	  WHEN MINUTE(TIMEDIFF(UTC_TIMESTAMP(),posts.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(UTC_TIMESTAMP(),posts.created_at)) ,'m ago')
							// 	  ELSE
							// 		 CONCAT(SECOND(TIMEDIFF(UTC_TIMESTAMP(),posts.created_at)) ,' s ago')
							// 	END as post_time,
							// 	CASE 
							// 	  WHEN DATEDIFF(UTC_TIMESTAMP(),comments.created_at) != 0 THEN CONCAT(DATEDIFF(UTC_TIMESTAMP(),comments.created_at) ,'d ago')
							// 	  WHEN HOUR(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) ,'h ago')
							// 	  WHEN MINUTE(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) ,'m ago')
							// 	  ELSE
							// 		 CONCAT(SECOND(TIMEDIFF(UTC_TIMESTAMP(),comments.created_at)) ,' s ago')
							// 	END as comment_time")
							// 	->leftJoin('users','users.id','=','posts.user_id')
							// 	->leftJoin('comments','comments.post_id','=','posts.id')
							// 	->where(function($query) use($post_id) {
							// 		if($post_id)
							// 		$query->Where('posts.id','<',$post_id);
							// 		else									
							// 		$query->Where('posts.id','>',$post_id);
							// 		})
							// 	->where(function($q) use($user_id){
							// 		 $q->WhereRaw('posts.user_id IN (select follow.follow_to from follow WHERE follow.follow_by='.$user_id.' and follow.status=1) ')
							// 			->orWhereRaw('posts.user_id = '.$user_id.'');
							// 		})
            				// 	->orderBy('posts.id', 'DESC')
							// 	//->take(15)
		     				//  ->get();
								
								if($feed_data){
								$profile_setup_status=Users::ProfileSetupStatus($user_id);
								
								foreach($feed_data as $key=>$value){
									if(!ISSET($final[$value->pid])){
										$final[$value->pid]=array(
										'user_id'=>$value->uid,
										'user_type'=>$value->user_type,
										'p_name'=>$value->full_name,
										'latitude'=>$value->lat?$value->lat:"0",
										'profile_pic'=>$value->profile_pic?Users::getFormattedImage($value->profile_pic):"",
										'longitude'=>$value->lng?$value->lng:"0",
										"post_id"=>$value->pid?$value->pid:"",
										'min_age'=>$value->min_age,
										'max_age'=>$value->max_age,
										"post_latitude"=>$value->post_lat?$value->post_lat:'0',
										"post_longitude"=>$value->post_lng?$value->post_lng:'0',
										"post_title"=>$value->title?$value->title:"",
										"skills"=>$value->skills?$value->skills:"",
										"post_description"=>$value->post_desc?$value->post_desc:"",
										'post_image'=>$value->post_image?Users::getFormattedImage($value->post_image):"",
										'post_video'=>$value->post_video?$vid_path.$value->post_video:"",
										'post_time'=>$value->post_time?$value->post_time:"",
										'likes_count'=>$value->likes_count?$value->likes_count:'0',
										'is_liked'=>$value->is_liked?$value->is_liked:'0',
										'distance'=>$value->distance?$value->distance:'0',
										'comments'=>array()
										);
									}

								if(!ISSET($final[$value->pid]['comments'][$value->cid])){
									if($value->cid){
										$final[$value->pid]['comments'][]=array(
										"comment_id"=>$value->cid?$value->cid:"",
										"user_id"=>$value->c_uid?$value->c_uid:"",
										"name"=>$value->un?$value->un:"",
										"user_type"=>$value->cutype?$value->cutype:"",
										'profile_pic'=>$value->c_profile_pic?Users::getFormattedImage($value->c_profile_pic):"",
										'comment'=>$value->comment?$value->comment:"",
										'comment_time'=>$value->comment_time?$value->comment_time:""
										);
									}	
								}
								}	
								}


								if($final){
									foreach($final as $key=>$val){
									$data2=array();

									$result[]=$val;
									}
								}

							$result= array_slice($result , 0, 15);
		           
		            if($result)
					return Response::json(array('status'=>'1', 'msg'=>'Records Found', 'data'=>$result,'all_skillset'=>$all_skillset,'profile_complete_status'=>$profile_setup_status), 200);
		        	else{
		        
		        		if($latitude && $longitude)
		        		return Response::json(array('status'=>'2','msg'=>"No Record Found",'all_skillset'=>$all_skillset), 200);
		        		else
			        	return Response::json(array('status'=>'3','msg'=>"No Record Found",'all_skillset'=>$all_skillset), 200);
					}
		        	
		        	
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
        }

        }
	

    		public static function PostDesc($user_id,$post_id){
	
	  
		            $post_data= DB::table('posts')
					->selectRaw("users.*,users.id as uid,posts.*,posts.id as pid,(SELECT count(likes.id) from likes where likes.post_id=posts.id) 
						as likes_count,(select count(likes.id) from likes where likes.post_id=posts.id and likes.user_id='$user_id') as is_liked,comments.id as cid,
						comments.user_id as c_uid,comments.*,(select users.full_name from users where users.id=comments.user_id) as un,
						(SELECT group_concat(CONCAT('#',skillset.skill) SEPARATOR ' ') from skillset JOIN post_skillset ON post_skillset.skillset_id=skillset.id WHERE post_skillset.post_id=posts.id) as skills,
						(SELECT users.profile_pic from users where users.id=comments.user_id) as c_profile_pic,
						CASE 
						  WHEN DATEDIFF(NOW(),posts.created_at) != 0 THEN CONCAT(DATEDIFF(NOW(),posts.created_at) ,'d ago')
						  WHEN HOUR(TIMEDIFF(NOW(),posts.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(NOW(),posts.created_at)) ,'h ago')
						  WHEN MINUTE(TIMEDIFF(NOW(),posts.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(NOW(),posts.created_at)) ,'m ago')
						  ELSE
							 CONCAT(SECOND(TIMEDIFF(NOW(),posts.created_at)) ,' s ago')
						END as post_time,
						CASE 
						  WHEN DATEDIFF(NOW(),comments.created_at) != 0 THEN CONCAT(DATEDIFF(NOW(),comments.created_at) ,'d ago')
						  WHEN HOUR(TIMEDIFF(NOW(),comments.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(NOW(),comments.created_at)) ,'h ago')
						  WHEN MINUTE(TIMEDIFF(NOW(),comments.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(NOW(),comments.created_at)) ,'m ago')
						  ELSE
							 CONCAT(SECOND(TIMEDIFF(NOW(),comments.created_at)) ,' s ago')
						END as comment_time ")
						->leftJoin('users','users.id','=','posts.user_id')
						->leftJoin('comments','posts.id','=','comments.post_id')
						->Where('posts.id','=',$post_id)
						->get();
						
							$final=array();	
							$result=array();
							
						if($post_data){

						foreach($post_data as $key=>$value){
						
							if(!ISSET($final[$value->pid])){
								$final[$value->pid]=array(
								'user_id'=>$value->uid,								
								'user_type'=>$value->user_type,
								'full_name'=>$value->full_name?$value->full_name:"",
								'latitude'=>$value->lat?$value->lat:"",
								'longitude'=>$value->lng?$value->lng:"",
								'profile_pic'=>$value->profile_pic?Users::getFormattedImage($value->profile_pic):"",
								'longitude'=>$value->lng?$value->lng:"0",
								"post_id"=>$value->pid?$value->pid:"",
								"skills"=>$value->skills?$value->skills:"",
								'post_latitude'=>$value->post_lat?$value->post_lat:"0",
								'post_longitude'=>$value->post_lng?$value->post_lng:"0",
								'min_age'=>$value->min_age,
								'max_age'=>$value->max_age,
								"post_title"=>$value->title?$value->title:"",
								"post_description"=>$value->post_desc?$value->post_desc:"",
								'post_image'=>$value->post_image?Users::getFormattedImage($value->post_image):"",
								'post_video'=>$value->post_video?$vid_path.$value->post_video:"",
								'post_time'=>$value->post_time?$value->post_time:"",
								'likes_count'=>$value->likes_count?$value->likes_count:'0',
								'is_liked'=>$value->is_liked?$value->is_liked:'0',
								'comments'=>array()
								);
							}

						if(!ISSET($final[$value->pid]['comments'][$value->cid])){
							if($value->cid){
								$final[$value->pid]['comments'][]=array(
								"comment_id"=>$value->cid,
								"user_id"=>$value->c_uid?$value->c_uid:"",
								"name"=>$value->un?$value->un:"",
								'profile_pic'=>$value->c_profile_pic?Users::getFormattedImage($value->c_profile_pic):"",
								'comment'=>$value->comment?$value->comment:"",
								'comment_time'=>$value->comment_time?$value->comment_time:""
								);
							}	
						}
						}	
						}


						if($final){
							foreach($final as $key=>$val){
							$data2=array();

							$result[]=$val;
							}
						}
						
		            
		            return $result;
	
	}


		public static function FetchPosts($user_id,$other_id){
						  
					$user_latlong=Users::getUserLatLong($user_id);
					$latitude=$user_latlong->lat;
					$longitude=$user_latlong->lng;
				    $vid_path="https://s3-us-west-2.amazonaws.com/cbrealestate/connected_uploads/";
					$post_data= DB::table('posts')
					->selectRaw("users.id as uid,posts.*,posts.id as pid,comments.id as cid,comments.user_id as c_uid,comments.*,
							(select count(likes.id) from likes where likes.post_id=posts.id and likes.user_id='$user_id') as is_liked,
							(select group_concat(CONCAT('#',skillset.skill) SEPARATOR '  ') from skillset JOIN post_skillset ON post_skillset.skillset_id=skillset.id WHERE post_skillset.post_id=posts.id) as skills,
							(select count(likes.id) from likes where likes.post_id=posts.id) as likes_count,(select users.full_name from users where users.id=comments.user_id) as un,
								(select users.profile_pic from users where users.id=comments.user_id) as c_profile_pic,(select users.user_type from users where users.id=comments.user_id) as cutype,
								TRUNCATE(( 3961 * acos( cos( radians( ".$latitude." ) ) * cos( radians( `lat` ) ) * cos( radians( `lng` ) - radians( ".$longitude." ) ) + sin( radians( ".$latitude." ) ) * sin( radians( `lat` ) ) ) ),2) AS distance,
							CASE 
							  WHEN DATEDIFF(NOW(),posts.created_at) != 0 THEN CONCAT(DATEDIFF(NOW(),posts.created_at) ,'d ago')
							  WHEN HOUR(TIMEDIFF(NOW(),posts.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(NOW(),posts.created_at)) ,'h ago')
							  WHEN MINUTE(TIMEDIFF(NOW(),posts.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(NOW(),posts.created_at)) ,'m ago')
							  ELSE
								 CONCAT(SECOND(TIMEDIFF(NOW(),posts.created_at)) ,' s ago')
							END as post_time,
							CASE 
							  WHEN DATEDIFF(NOW(),comments.created_at) != 0 THEN CONCAT(DATEDIFF(NOW(),comments.created_at) ,'d ago')
							  WHEN HOUR(TIMEDIFF(NOW(),comments.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(NOW(),comments.created_at)) ,'h ago')
							  WHEN MINUTE(TIMEDIFF(NOW(),comments.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(NOW(),comments.created_at)) ,'m ago')
							  ELSE
								 CONCAT(SECOND(TIMEDIFF(NOW(),comments.created_at)) ,' s ago')
							END as comment_time")
						->leftJoin('users','users.id','=','posts.user_id')
						->leftJoin('comments','posts.id','=','comments.post_id')
						->Where('users.id','=',$other_id)
						->orderBy('posts.created_at','DESC')
						->get();
										
							$final=array();	
							$result=array();
							
						if($post_data){

						foreach($post_data as $key=>$value){
						
							if(!ISSET($final[$value->pid])){
								$final[$value->pid]=array(
								"post_id"=>$value->pid?$value->pid:"",
								'post_latitude'=>$value->post_lat?$value->post_lat:"0",
								'post_longitude'=>$value->post_lng?$value->post_lng:"0",
								'min_age'=>$value->min_age,
								'max_age'=>$value->max_age,
								"post_title"=>$value->title?$value->title:"",
								"post_description"=>$value->post_desc?$value->post_desc:"",
								'post_image'=>$value->post_image?Users::getFormattedImage($value->post_image):"",
								'post_video'=>$value->post_video?$vid_path.$value->post_video:"",
								'post_time'=>$value->post_time?$value->post_time:"",
								'likes_count'=>$value->likes_count?$value->likes_count:'0',
								'skills'=>$value->skills?$value->skills:'',
								'distance'=>$value->distance?$value->distance:'0',
								'is_liked'=>$value->is_liked?$value->is_liked:'0',
								'comments'=>array()
								);
							}

						if(!ISSET($final[$value->pid]['comments'][$value->cid])){
							if($value->cid){
								$final[$value->pid]['comments'][]=array(
								"comment_id"=>$value->cid,
								"user_id"=>$value->c_uid?$value->c_uid:"",
								"name"=>$value->un?$value->un:"",
								"user_type"=>$value->cutype?$value->cutype:"",
								'profile_pic'=>$value->c_profile_pic?Users::getFormattedImage($value->c_profile_pic):"",
								'comment'=>$value->comment?$value->comment:"",
								'comment_time'=>$value->comment_time?$value->comment_time:""
								);
							}	
						}
						}	
						}


						if($final){
							foreach($final as $key=>$val){
							$data2=array();

							$result[]=$val;
							}
						}
					
					return $result;
					
		}

	
		public static function getUserPosts($input){
		
		 $validation = Validator::make($input, Posts::$UserPostsRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
			  $other_id=$input['other_id'];
		      $current_time = new DateTime();
			  
				$user_id=Users::getUserIdByToken($access_token);
				
		          if($user_id){
				  
					$result=Posts::FetchPosts($user_id,$other_id);
						
		            if($result){
						return Response::json(array('status'=>'1', 'msg'=>'Post Details','result'=>$result), 200);
		        	}
					else
						return Response::json(array('status'=>'2','msg'=>'No Post Found'),200);
		        	
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
        }
	
	
	}

			
	
		public static function getPostDetail($input){
	
		 $validation = Validator::make($input, Posts::$likePostRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $post_id = $input['post_id'];

		      $current_time = new DateTime();
			  
				$user_id=Users::getUserIdByToken($access_token);
				$vid_path="https://s3-us-west-2.amazonaws.com/cbrealestate/connected_uploads/";
		          if($user_id){
				  
		            $post_data= DB::table('posts')
					->selectRaw("users.*,users.id as uid,posts.*,posts.id as pid,(SELECT count(likes.id) from likes where likes.post_id=posts.id) 
						as likes_count,(select count(likes.id) from likes where likes.post_id=posts.id and likes.user_id='$user_id') as is_liked,comments.id as cid,
						comments.user_id as c_uid,comments.*,(select users.full_name from users where users.id=comments.user_id) as un,
						(select users.user_type from users where users.id=comments.user_id) as utype,
						(SELECT group_concat(CONCAT('#',skillset.skill) SEPARATOR ' ') from skillset JOIN post_skillset ON post_skillset.skillset_id=skillset.id WHERE post_skillset.post_id=posts.id) as skills,
						(SELECT users.profile_pic from users where users.id=comments.user_id) as c_profile_pic,
						CASE 
						  WHEN DATEDIFF(NOW(),posts.created_at) != 0 THEN CONCAT(DATEDIFF(NOW(),posts.created_at) ,'d ago')
						  WHEN HOUR(TIMEDIFF(NOW(),posts.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(NOW(),posts.created_at)) ,'h ago')
						  WHEN MINUTE(TIMEDIFF(NOW(),posts.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(NOW(),posts.created_at)) ,'m ago')
						  ELSE
							 CONCAT(SECOND(TIMEDIFF(NOW(),posts.created_at)) ,' s ago')
						END as post_time,
						CASE 
						  WHEN DATEDIFF(NOW(),comments.created_at) != 0 THEN CONCAT(DATEDIFF(NOW(),comments.created_at) ,'d ago')
						  WHEN HOUR(TIMEDIFF(NOW(),comments.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(NOW(),comments.created_at)) ,'h ago')
						  WHEN MINUTE(TIMEDIFF(NOW(),comments.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(NOW(),comments.created_at)) ,'m ago')
						  ELSE
							 CONCAT(SECOND(TIMEDIFF(NOW(),comments.created_at)) ,' s ago')
						END as comment_time ")
						->leftJoin('users','users.id','=','posts.user_id')
						->leftJoin('comments','posts.id','=','comments.post_id')
						->Where('posts.id','=',$post_id)
						->get();
						
							$final=array();	
							$result=array();
							
						if($post_data){

						foreach($post_data as $key=>$value){
						
							if(!ISSET($final[$value->pid])){
								$final[$value->pid]=array(
								'user_id'=>$value->uid,
								'user_type'=>$value->user_type,
								'full_name'=>$value->full_name?$value->full_name:"",
								'latitude'=>$value->lat?$value->lat:"",
								'longitude'=>$value->lng?$value->lng:"",
								'profile_pic'=>$value->profile_pic?Users::getFormattedImage($value->profile_pic):"",
								'min_age'=>$value->min_age,
								'max_age'=>$value->max_age,
								'longitude'=>$value->lng?$value->lng:"0",
								"post_id"=>$value->pid?$value->pid:"",
								"skills"=>$value->skills?$value->skills:"",
								'post_latitude'=>$value->post_lat?$value->post_lat:"0",
								'post_longitude'=>$value->post_lng?$value->post_lng:"0",
								"post_title"=>$value->title?$value->title:"",
								"post_description"=>$value->post_desc?$value->post_desc:"",
								'post_image'=>$value->post_image?Users::getFormattedImage($value->post_image):"",
								'post_video'=>$value->post_video?$vid_path.$value->post_video:"",
								'post_time'=>$value->post_time?$value->post_time:"",
								'likes_count'=>$value->likes_count?$value->likes_count:'0',
								'is_liked'=>$value->is_liked?$value->is_liked:'0',
								'comments'=>array()
								);
							}

						if(!ISSET($final[$value->pid]['comments'][$value->cid])){
							if($value->cid){
								$final[$value->pid]['comments'][]=array(
								"comment_id"=>$value->cid,
								"user_id"=>$value->c_uid?$value->c_uid:"",
								"name"=>$value->un?$value->un:"",
								"user_type"=>$value->utype?$value->utype:"",
								'profile_pic'=>$value->c_profile_pic?Users::getFormattedImage($value->c_profile_pic):"",
								'comment'=>$value->comment?$value->comment:"",
								'comment_time'=>$value->comment_time?$value->comment_time:""
								);
							}	
						}
						}	
						}


						if($final){
							foreach($final as $key=>$val){
							$data2=array();

							$result[]=$val;
							}
						}
						
		            if($result){
						return Response::json(array('status'=>'1', 'msg'=>'Post Details','result'=>$result), 200);
		        	}
					else
						return Response::json(array('status'=>'2','msg'=>'No Post Found'),200);
		        	
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
        }
	
	
	}


}
