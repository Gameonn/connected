<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use App\Users;
use DateTime;
use PushNotification;

class Notifications extends Model {

	
		public static $NotifyRules = array(
        'token' => 'required',
        'notification_id' => 'required'
		);
	
	
		public static function addNotification($input){
		
    	   	  $sender_id=$input['sender_id'];
        	  $receiver_id = $input['receiver_id'];
        	  $type_id=$input['type_id'];
        	  $type=$input['type'];
        	  $notify_message=$input['title'];
		      $current_time = new DateTime();
		            
		            $notification_data= DB::table('notification')
		            			->select('*')
		            			->where('user_id_receiver', $receiver_id)
		            			->where('user_id_sender', $sender_id)
		            			->where('type_id', $type_id)
		            			->where('type', $type)
		            			->first();

		            if(!$notification_data){
		            $notification_id=DB::table('notification')->insertGetId(
		            		array(
		            			'user_id_sender' => $sender_id,
		            			'user_id_receiver' => $receiver_id,
		            			'type_id' => $type_id,
		            			'type' => $type,
		            			'title' => $notify_message,
								'is_read'=>'0',
		            			'created_at' => $current_time,	
		            			)
		            	);

		           }
					return true;
		}
		
		
		public static function RemoveNotification($input){
		
    	   	  $sender_id=$input['sender_id'];
        	  $receiver_id = $input['receiver_id'];
        	  $type_id=$input['type_id'];
        	  $type=$input['type'];
		      $current_time = new DateTime();
		            
		            $notification_data= DB::table('notification')
		            			->select('*')
		            			->where('user_id_receiver', $receiver_id)
		            			->where('user_id_sender', $sender_id)
		            			->where('type_id', $type_id)
		            			->where('type', $type)
		            			->delete();

					return true;
		}


		public static function SendPushNotification($msg,$reg_id,$device_token,$type,$uid,$utype){

			 $message = PushNotification::Message($msg,array(
		        // 'badge' => 1,
		        'sound' => 'example.aiff',


		        'locArgs' => array(
		            'u1' => $uid,
		            'u2' => $utype,
		            't' => $type,
		        ),
		    ));

	    // send push
			if(!empty($device_token)){
				$send = PushNotification::app('appNameIOS')
		        ->to($device_token)
		        ->send($message);
			} 
		    
			if(!empty($reg_id)){
				$send = PushNotification::app('appNameAndroid')
			    ->to($reg_id)
			    ->send($message); 
			}

	    return true;

		}

		public static function SendPushNotification2($msg,$reg_id,$device_token,$type,$uid,$utype){

			 $message = PushNotification::Message($msg,array(
		        // 'badge' => 1,
		        'sound' => 'example.aiff',


		        'locArgs' => array(
		            'u1' => $uid,
		            'u2' => $utype,
		            't' => $type,
		        ),
		    ));

	    	// send push					    
			if(!empty($reg_id)){
				$send = PushNotification::app('appNameAndroid')
			    ->to($reg_id)
			    ->send($message); 
			}
			
	    	return true;

		}
	
	
		public static function getNotifications($input){
		
		 $validation = Validator::make($input, Posts::$FeedRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {

        	  	$access_token=$input['token'];
				$user_id=Users::getUserIdByToken($access_token);
				
		          if($user_id){
					
		            // $path=THUMBS_DIR_PATH;
					
		            $notification_data= DB::select( DB::raw(" SELECT notification.*,users.full_name,users.profile_pic,
											CASE 
											  WHEN DATEDIFF(NOW(),notification.created_at) != 0 THEN CONCAT(DATEDIFF(NOW(),notification.created_at) ,'d ago')
											  WHEN HOUR(TIMEDIFF(NOW(),notification.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(NOW(),notification.created_at)) ,'h ago')
											  WHEN MINUTE(TIMEDIFF(NOW(),notification.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(NOW(),notification.created_at)) ,'m ago')
											  ELSE
												 CONCAT(SECOND(TIMEDIFF(NOW(),notification.created_at)) ,' s ago')
											END as time_elapsed
											FROM notification 
												left join comments on comments.id=notification.type_id and notification.type='comment' 
												left join likes on likes.id=notification.type_id and notification.type='like' 
												left join follow on follow.id=notification.type_id and notification.type='follow' 
												join users on users.id=notification.user_id_sender 
												where notification.user_id_receiver=".$user_id));
					            											
								        if($notification_data)
										return Response::json(array('status'=>'1', 'msg'=>'Notifications','result'=>$notification_data), 200);
										else
										return Response::json(array('status'=>'0', 'msg'=>'No Notifications'), 200);
										
								}
								else{
									return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
								}
		
		}
		
		
		public static function SetNotificationStatus($input){
			
			 $validation = Validator::make($input, Notifications::$NotifyRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
				$access_token=$input['token'];
        	  $notification_id = $input['notification_id'];

		      $current_time = new DateTime();

		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            
		            $notification_data= DB::table('notification')
		            			->select('*')
		            			->where('id', $notification_id)
		            			->first();

		            if($notification_data){
		             DB::table('notification')->where('id', $notification_id)->update(['is_read' => '1']);

		            return Response::json(array('status'=>'1', 'msg'=>'Notification Read'), 200);
		        	
		        	}
		        	else{
		        		return Response::json(array('status'=>'0', 'msg'=>'Notification doesnot exists'),200);
		        	}


		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		}
		
		}
	

}
