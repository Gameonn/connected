<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
//use Illuminate\Database\Capsule\Manager;
use App\Posts;
use App\Users;
use App\Notifications;
use DateTime;


class Messages extends Model {

	
	public static $MessagingRules=array(
	   	'token' => 'required',
	   	'other_id'=>'required'
	   	// 'message'=>'required'

	   	);

	public static $FetchMessageRules=array(
	   	'token' => 'required',
	   	'other_id'=>'required',
	   	'message_id'=>'required'

	   	);

	public static $deleteChatRules=array(
	   	'token' => 'required',
	   	'message_id'=>'required'

	   	);


	//update the read status of the sent message
	public static function updateReadStatus($user_id,$other_id){
		
		DB::table('messages')->where('user_id_sender', $other_id)->where('user_id_receiver',$user_id)->update(['is_read' => 1]);
 		
		return true;		
	}


	public static function getUnreadCount($user_id,$other_id){


		$unread_messages=DB::table('messages AS m')
            			  ->selectRaw("m.id,u.id as uid,m.message,m.created_at")
            			  ->Join('users as u','u.id','=','m.user_id_sender')
            			  ->Where('m.user_id_sender','=',$other_id)
            			  ->Where('m.user_id_receiver','=',$user_id)
            			  ->Where('m.is_read','=','0')
            			  ->get();	
		
	return $unread_messages;
		
	}


	public static function getOnline($user_id){

		// $last_active_time=DB::table('user')
		// 					->selectRaw("*")
		// 					->Where('users.id','=',$user_id)
		// 					->Where('users.created_at','>=','DATE_SUB(UTC_TIMESTAMP(),INTERVAL 50 MINUTE)') 
		// 					->first();

		$last_active_time=DB::select('SELECT * from `users` where `users`.`id` = '.$user_id.' and `users`.`created_at` >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 SECOND) limit 1');

				if($last_active_time)
				$online='1';
				else
				$online='0';

			return $online;

	}


	public static function getNewMessages($user_id,$other_id,$message_id){


		 $all_messages=DB::select("SELECT m.id,u.id as uid,u.user_type,u.fbid,u.full_name,u.profile_pic,m.image as image_name, m.message,m.created_at
		 				 FROM messages m JOIN users u ON u.id=m.user_id_sender WHERE m.user_id_sender=".$other_id."
		 				 AND m.user_id_receiver=".$user_id." and receiver_delete=0 and m.id>".$message_id." order by m.id ASC ");
			

				foreach($all_messages as $row){
					
					$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
					$row->image_name=$row->image_name?Users::getFormattedImage($row->image_name):"";
					$row->created_date= date('M-d-Y',strtotime($row->created_at));
					$row->time = date('g:i a',strtotime($row->created_at));
				}

			return $all_messages;	
		}


		//fetching last message chat for a user
	public static function getLastChat($input){
		
	  	$validation = Validator::make($input, Posts::$FeedRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	$access_token=$input['token'];
        	$user_id=Users::getUserIdByToken($access_token);
				
		          if($user_id){
		          	//dd(Messages::getUnreadCount(3,5));
		//mode r-recieve  s-send
		 $last_chat=DB::select("SELECT temp2.* FROM (SELECT temp.* FROM (SELECT u.id,u.user_type,u.fbid,u.full_name as name,u.profile_pic,m.id as mid,m.image as image_name, m.message,'s' as mode,m.created_at,
				CASE 
                  WHEN DATEDIFF(NOW(),m.created_at) != 0 THEN CONCAT(DATEDIFF(NOW(),m.created_at) ,' days ago')
                  WHEN HOUR(TIMEDIFF(NOW(),m.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(NOW(),m.created_at)) ,' hrs ago')
                  WHEN MINUTE(TIMEDIFF(NOW(),m.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(NOW(),m.created_at)) ,' mins ago')
                  ELSE
                     CONCAT(SECOND(TIMEDIFF(NOW(),m.created_at)) ,' s ago')
                END as time_elapsed
		FROM messages m JOIN users u ON m.user_id_receiver=u.id WHERE m.user_id_sender=".$user_id." AND m.sender_delete=0
		UNION 
		SELECT u.id,u.user_type,u.fbid,u.full_name as name,u.profile_pic,m.id as mid,m.image as image_name, m.message,'r' as mode,m.created_at,
		CASE 
                  WHEN DATEDIFF(NOW(),m.created_at) != 0 THEN CONCAT(DATEDIFF(NOW(),m.created_at) ,' days ago')
                  WHEN HOUR(TIMEDIFF(NOW(),m.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(NOW(),m.created_at)) ,' hrs ago')
                  WHEN MINUTE(TIMEDIFF(NOW(),m.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(NOW(),m.created_at)) ,' mins ago')
                  ELSE
                     CONCAT(SECOND(TIMEDIFF(NOW(),m.created_at)) ,' s ago')
                END as time_elapsed
		FROM messages m JOIN users u ON m.user_id_sender=u.id WHERE m.user_id_receiver=".$user_id." AND m.receiver_delete=0) as temp ORDER BY temp.created_at DESC) as temp2 GROUP BY temp2.id order by temp2.created_at DESC");
		

	     	foreach($last_chat as $row){
								
								$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
								$row->image_name=$row->image_name?Users::getFormattedImage($row->image_name):"";
								$row->unread_count=count(self::getUnreadCount($user_id,$row->id));
								$row->created_date= date('M-d-Y',strtotime($row->created_at));
								$row->time = date('g:i a',strtotime($row->created_at));
								
								}

					 if($last_chat)
					return Response::json(array('status'=>'1', 'msg'=>'Records Found', 'data'=>$last_chat), 200);
		        	else
		        	return Response::json(array('status'=>'2','msg'=>"No Record Found"), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        	}
}

	
	public static function getUserMessages($input){
		
	  	$validation = Validator::make($input, Posts::$UserPostsRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	$access_token=$input['token'];
        	$other_id=$input['other_id'];
        	$user_id=Users::getUserIdByToken($access_token);
				
		          if($user_id){


		          //set messages received before the specified message_id as read
		$updated=Messages::updateReadStatus($user_id,$other_id);

		$online_status=Messages::getOnline($other_id);

		 $all_messages=DB::select("SELECT temp2.* from(SELECT temp.* from (SELECT m.id,m.user_id_sender as uid,u.user_type,u.fbid,u.full_name as name,u.profile_pic,
		m.image as image_name, m.message, m.created_at FROM messages m 
		JOIN users u ON u.id=m.user_id_sender WHERE m.user_id_sender=".$other_id." AND m.user_id_receiver=".$user_id." AND receiver_delete=0
		UNION 
		SELECT m.id,m.user_id_sender as uid,u.user_type,u.fbid,u.full_name as name,u.profile_pic,m.image as image_name ,m.message, m.created_at FROM messages m JOIN users u ON u.id=m.user_id_sender 
		WHERE m.user_id_sender=".$user_id." AND sender_delete=0 AND m.user_id_receiver=".$other_id." ) as temp ORDER BY temp.created_at DESC LIMIT 0,30) as temp2 ORDER BY temp2.created_at");
		

	     				foreach($all_messages as $row){
								
							$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
							$row->image_name=$row->image_name?Users::getFormattedImage($row->image_name):"";
							$row->created_date= date('M-d-Y',strtotime($row->created_at));
							$row->time = date('g:i a',strtotime($row->created_at));
						}

					 if($all_messages)
					return Response::json(array('status'=>'1', 'msg'=>'Records Found', 'data'=>$all_messages,'online'=>$online_status), 200);
		        	else
		        	return Response::json(array('status'=>'2','msg'=>"No Record Found"), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        	}
	}


	public static function getRecUserMessagesAfter($input){
		
		$validation = Validator::make($input, Posts::$UserPostsRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	$access_token=$input['token'];
        	$other_id=$input['other_id'];
        	$message_id=isset($input['message_id'])?$input['message_id']:0;
        	$user_id=Users::getUserIdByToken($access_token);
			$online_status=Messages::getOnline($other_id);

		          if($user_id){

		          	$all_messages=Messages::getNewMessages($user_id,$other_id,$message_id);

					 if($all_messages)
					return Response::json(array('status'=>'1', 'msg'=>'Records Found', 'data'=>$all_messages,'online'=>$online_status), 200);
		        	else
		        	return Response::json(array('status'=>'2','msg'=>"No Record Found",'online'=>$online_status), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        	}
	}


	public static function getUserMessagesBefore($input){
		
		$validation = Validator::make($input, Messages::$FetchMessageRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	$access_token=$input['token'];
        	$other_id=$input['other_id'];
        	$message_id=$input['message_id'];
        	$user_id=Users::getUserIdByToken($access_token);
				
		          if($user_id){

		          $all_messages=DB::select("SELECT temp.* from(SELECT m.id,u.id as uid,u.user_type,u.fbid,u.full_name as name, u.profile_pic,m.image as image_name,m.message,m.created_at FROM messages m JOIN users u ON u.id=m.user_id_sender WHERE m.user_id_sender=".$user_id." AND sender_delete=0
										 AND m.user_id_receiver=".$other_id." and m.id<".$message_id."
										 UNION
										 SELECT m.id,u.id as uid,u.user_type,u.fbid,u.full_name as name,u.profile_pic,m.image as image_name, m.message,m.created_at FROM messages m JOIN users u ON u.id=m.user_id_sender WHERE m.user_id_sender=".$other_id."
										 AND m.user_id_receiver=".$user_id." and receiver_delete=0 and m.id<".$message_id.") as temp ORDER BY temp.created_at ASC");
						

	     				foreach($all_messages as $row){
								
							$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
							$row->image_name=$row->image_name?Users::getFormattedImage($row->image_name):"";
							$row->created_date= date('M-d-Y',strtotime($row->created_at));
							$row->time = date('g:i a',strtotime($row->created_at));
						}


					 if($all_messages)
					return Response::json(array('status'=>'1', 'msg'=>'Records Found', 'data'=>$all_messages), 200);
		        	else
		        	return Response::json(array('status'=>'2','msg'=>"No Record Found"), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        	}
	}



	public static function saveUserMessage($input) {
	
		
			$validation = Validator::make($input, Messages::$MessagingRules);
		        if($validation->fails()){
		            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
		        }
		        else{
		            $message = $input['message'];
		            $other_id = $input['other_id'];
		            $image = Input::file('image');
		            $message_id=isset($input['message_id'])?$input['message_id']:0;
		            $current_time = new DateTime();   	 
		            $access_token=$input['token'];
			      	$user_id=Users::getUserIdByToken($access_token);


			    if($user_id){
			    	 // Handling User Profile Image
		            if($image=="") {                
		                $image = "";
		            }
		            else {
		                $image = Users::uploadPostFiles('image');
		            }


		            	$uname=Users::getUsername($user_id);
		            	$push_status=Users::getPushSettings($other_id);

		            	$user_det=Users::getUserType($user_id);
	          			$user_type=$user_det->user_type;
		            	$push_ids=Users::getUserPushIds($other_id);
		            	$reg_id=$push_ids->reg_id;
		            	$device_token=$push_ids->apn_id;

		            	DB::table('messages')->insertGetId(
		                array(
		                    'user_id_sender' => $user_id,
		                    'user_id_receiver' => $other_id,
		                    'message' => $message,
		                    'image' => $image,
		                    'is_read' => 0,
		                    'sender_delete' => 0,
		                    'receiver_delete' => 0,
		                    'created_at' => $current_time,
		                )
		            );

				    	$notify_message=$uname." sent you a message";

				    	if($push_status)
				    	Notifications::SendPushNotification($notify_message,$reg_id,$device_token,4,$user_id,$user_type);

					$all_messages=Messages::getNewMessages($user_id,$other_id,$message_id);
		      
	            return Response::json(array('status'=>'1', 'msg'=>'Message Sent', 'all_messages'=>$all_messages), 200);
			   }
			   else
			   	return Response::json(array('status'=>'0','msg'=>'Token Expired'));
		    }
		
		}



		public static function deleteChat($input) {
	
		
			$validation = Validator::make($input, Messages::$deleteChatRules);
		        if($validation->fails()){
		            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
		        }
		        else{
		            $message_id=$input['message_id'];  	 
		            $access_token=$input['token'];
			      	$user_id=Users::getUserIdByToken($access_token);


			    if($user_id){
			    	
			    	DB::table('messages')->where('id','<=', $message_id)->where('user_id_sender',$user_id)->update(['sender_delete' => 1]);
			    	DB::table('messages')->where('id','<=', $message_id)->where('user_id_receiver',$user_id)->update(['receiver_delete' => 1]);
					
		      
	            return Response::json(array('status'=>'1', 'msg'=>'Message Removed'), 200);
			   }
			   else
			   	return Response::json(array('status'=>'0','msg'=>'Token Expired'));
		    }
		
		}

}


/*
	
	public static function local_time($time_zone,$dateFormat,$current_server_time){

	$minutes	=substr($time_zone,3);
	$hours		=substr($time_zone,1,2);
	$sign		=substr($time_zone,0,1);
	$seconds	=$sign.($hours * 3600)+($minutes * 60);
	$qqq = gmdate($dateFormat, strtotime($current_server_time) + $seconds);
	
	return $qqq;
	}
	
	
		//calculation of post created time
	public static function time_since($created_on){
	
	global $conn;
	$sth=$conn->prepare("SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP('$created_on') as time_diff");
	try{$sth->execute();}
	catch(Exception $e){}
	$res=$sth->fetchAll();
	$diff=$res[0]['time_diff'];

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




}
