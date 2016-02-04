<?php namespace App;

//use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
//use Illuminate\Database\Capsule\Manager;
use App\Posts;
use AWS;  
use Illuminate\Contracts\Filesystem\Filesystem;
use DateTime;

class Users extends Model {

	
	  public static $signUpEmployerRules = array(
        'full_name' => 'required',
        'email' => 'required|email|Unique:users',
        'password' => 'required',
        'user_type' => 'required',
        'dob'=> 'required'
        //'phone_number'=>'required'
        //'company' => 'required',
        //'designation' => 'required'

    );

	    public static $signUpInternRules = array(
        'full_name' => 'required',
        'email' => 'required|email|Unique:users',
        'password' => 'required',
        'user_type' => 'required',
        'dob'=> 'required'
        //'phone_number'=>'required'
        //'majors' => 'required',
        //'college'=> 'required'
    );

	    public static $accessTokenRequired = array(
        'token' => 'required|exists:users,access_token',
    );


    	public static $userSettingsRules = array(
        'token' => 'required|exists:users,access_token'
        // 'push_notification'=>'required'
    );


	   public static $loginRules = array(
        'email' => 'required',
        'password' => 'required'
    );

	   public static $editProfileRules= array(
	   	'token' => 'required',
	   	'user_type' => 'required',
	   	'full_name' => 'required'
	  );


	   public static $addAttachmentsRules= array(
	   	'token' => 'required',
	   	'filename' => 'required'
	  );


	   public static $addJobRules=array(
	   	'token' => 'required',
	   	'company_name' => 'required',
	   	'start_year' => 'required',
	   	'end_year' => 'required',
	   	'designation' => 'required'

	   	);

	   public static $addQualificationRules=array(
	   	'token' => 'required',
	   	'masters' => 'required',
	   	'college' => 'required'

	   	);

	   public static $addTestimonialRules=array(
	   	'token' => 'required',
	   	'user_id' => 'required',//for whom testimonial is written
	   	'description' => 'required'

	   	);

	   public static $editTestimonialRules=array(
	   	'token' => 'required',
	   	'user_id' => 'required',//for whom testimonial is written
	   	'description' => 'required',
	   	'testimonial_id' => 'required'

	   	);

	   public static $deleteTestimonialRules=array(
	   	'token' => 'required',
	   	'testimonial_id' => 'required'

	   	);

       public static $deleteAttachmentRules=array(
        'token' => 'required',
        'attachment_id' => 'required'

        );

	   public static $searchRules=array(
	   	'token' => 'required',
	   	'searchkey' => 'required'

	   	);

	   public static $editVideoRules=array(
	   	'token' => 'required',
	   	'profile_thumb'=>'required',
	   	'profile_video' => 'required'

	   	);
		
		

	public static function generateToken(){

		return $access_token = str_random(30);
	}


	  // Image Upload Methods
    public static function uploadImage() {

    	$filename="";
        if(Input::file('profile_pic')->isValid()){
            // store file input in a variable
            $file = Input::file('profile_pic');
            //get extension of file
            $ext = $file->getClientOriginalExtension();
            //directory to store images
            $dir = 'uploads';
            // change filename to random name
            $filename = substr(time(), 0, 15).str_random(30) . ".{$ext}";

             $s3 = AWS::get('s3');
            $s3->putObject(array(
                'Bucket'     => 'cbrealestate',
                'Key'        => 'connected_uploads/'.$filename,
                'SourceFile' => $file->getPathname(),
                // 'ContentType' => 'images/jpeg',
                'ACL' => 'public-read'
            ));

            // move uploaded file to temp. directory
            // $upload_success = Input::file('profile_pic')->move($dir, $filename);
            // $img = $upload_success ? $filename : '';
        }
        return $filename;
    }


      // File Attachment Upload Methods
    public static function uploadFile() {

    	$filename="";
        if(Input::file('filename')->isValid()){
            // store file input in a variable
            $file = Input::file('filename');
            //get extension of file
            $ext = $file->getClientOriginalExtension();
            //directory to store images
            $dir = 'uploads';
            // change filename to random name
            $filename = substr(time(), 0, 15).str_random(30) . ".{$ext}";
            // move uploaded file to temp. directory
            // $upload_success = Input::file('filename')->move($dir, $filename);
            // $img = $upload_success ? $filename : '';
            $s3 = AWS::get('s3');
            $s3->putObject(array(
                'Bucket'     => 'cbrealestate',
                'Key'        => 'connected_uploads/'.$filename,
                'SourceFile' => $file->getPathname(),
                // 'ContentType' => 'images/jpeg',
                'ACL' => 'public-read'
            ));
        }
        return $filename;
    }



        // Post Images/Videos Upload Methods
    public static function uploadPostFiles($type) {

    	$filename="";
        if(Input::file($type)->isValid()){
            // store file input in a variable
            $file = Input::file($type);
            //get extension of file
            $ext = $file->getClientOriginalExtension();
            //directory to store images
            $dir = 'uploads';
            // change filename to random name
            $filename = substr(time(), 0, 15).str_random(30) . ".{$ext}";
            // move uploaded file to temp. directory
            // $upload_success = Input::file($type)->move($dir, $filename);
            // $img = $upload_success ? $filename : '';

            $s3 = AWS::get('s3');
            $s3->putObject(array(
                'Bucket'     => 'cbrealestate',
                'Key'        => 'connected_uploads/'.$filename,
                'SourceFile' => $file->getPathname(),
                // 'ContentType' => 'images/jpeg',
                'ACL' => 'public-read'
            ));
        }
        return $filename;
    }


        public static function getIsFollow($user_id,$other_id) {
        $follow_status = DB::table('follow')->selectRaw('count(follow.id) as f_count')->where('follow_by', $user_id)->where('follow_to',$other_id)->first();
        
       		return $follow_status->f_count;
    }


       public static function getFormattedImage($image) {
     
       	if($image=='') {
            return $image;
        }
        elseif($image==null)
        	return '';
       	else {
        $img_base_path = URL::to('/').'/';
        $image_name_divide_arr = explode('.', $image);
        $img_name = $image_name_divide_arr[0];
        $img_ext = end($image_name_divide_arr);
        return $img_name2 = $img_base_path.'photos/thumb/'.$img_name.'/'.$img_ext;
    	}
}


     public static function updateUserToken($user_id) {
        $access_token = Users::generateToken();
        DB::table('users')->where('id', $user_id)->update(['access_token' => $access_token]);
        return $access_token;
    }


     public static function updateLastVisited($user_id) {

     	$current_time = new DateTime();
        DB::table('users')->where('id', $user_id)->update(['created_at' => $current_time]);
        return true;
   	 }


   	 public static function updateLatLng($user_id,$lat,$lng) {

        DB::table('users')->where('id', $user_id)->update(['lat' => $lat,'lng'=>$lng]);
        return true;
   	 }


   	 public static function setDeviceToken($user_id,$reg_id,$device_token) {

   	 	if($device_token){
   	 		DB::table('users')->where('apn_id', $device_token)->update(['apn_id' => '']);
   	 		DB::table('users')->where('id', $user_id)->update(['apn_id' => $device_token]);
       	}

       	if($reg_id){
       		DB::table('users')->where('reg_id', $reg_id)->update(['reg_id' => '']);
        	DB::table('users')->where('id', $user_id)->update(['reg_id' => $reg_id]);
        
       	}
        return true;
   	 }

   	 public static function updateLocation($user_id,$place) {

        DB::table('users')->where('id', $user_id)->update(['place' => $place]);
        return true;
   	 }
	
	
	 public static function getUserLatLong($user_id) {
        $user_data = DB::table('users')->select('lat','lng')->where('id', $user_id)->first();
        
       		return $user_data;
    }


    public static function getPushSettings($user_id) {
        $user_data = DB::table('users')->select('push_notification')->where('id', $user_id)->first();
        
        if($user_data)
        	return $user_data->push_notification;
    	else
    		return '0';
    }


     public static function getUserType($user_id) {
        $user_data = DB::table('users')->select('user_type')->where('id', $user_id)->first();
        
       		return $user_data;
    }


  	 public static function getUserPushIds($user_id) {
        $user_data = DB::table('users')->select('apn_id','reg_id')->where('id', $user_id)->first();
        
       		return $user_data;
    }


    public static function getUserIdByToken($access_token) {
        $user_data = DB::table('users')->select('id')->where('access_token', $access_token)->first();
        
        if($user_data){
        	Users::updateLastVisited($user_data->id);
        	return $user_data->id;
        }
    	else
    		return '';
    }
	
	
	public static function ProfileSetupStatus($user_id) {
	
			 $user_profile=DB::select("SELECT temp.* FROM (SELECT users.profile_pic,users.profile_video,users.user_type ,user_attachments.filename,
										CASE WHEN users.user_type!='employer' THEN user_qualification.masters ELSE 1 END as masters,
										CASE WHEN users.user_type='employer' THEN company.company_name ELSE 1 END as company_param,
										CASE WHEN users.user_type='intern' THEN (SELECT user_jobs.company_name FROM user_jobs WHERE user_jobs.user_id=users.id LIMIT 1) 
										ELSE 1 END as user_job_param FROM `users` LEFT JOIN user_qualification ON user_qualification.user_id=users.id 
										LEFT JOIN user_attachments on user_attachments.user_id=users.id 
                    					LEFT JOIN company ON company.user_id=users.id
										WHERE users.id=".$user_id.") as temp 
										WHERE (temp.profile_video='' OR temp.profile_pic='' OR temp.filename='' OR temp.filename IS NULL OR temp.masters='' OR temp.masters IS NULL OR temp.user_job_param IS NULL OR 				
                                        temp.company_param IS NULL)");
		if($user_profile)
			return '1';
		else
			return '2';
	
	}
	
	
	public static function getUserName($user_id) {
        $user_data = DB::table('users')->select('full_name')->where('id', $user_id)->first();
        
        if($user_data)
        	return $user_data->full_name;
    	else
    		return '';
    }


	public static function LoginTypeResponse($user_id){

		$final=array();	
		$data=array();
			//COALESCE(masters,"") as masters

		$vid_path="https://s3-us-west-2.amazonaws.com/cbrealestate/connected_uploads/";
        	Users::updateLastVisited($user_id);
        	
		 $user_details=DB::table('users')
		            			  ->selectRaw("users.*,users.id as user_id,user_qualification.id as qid,active,masters,college,user_attachments.id as aid,filename,
		            			  	(SELECT FLOOR(DATEDIFF( NOW(),users.dob) / 365.25)) as age,
                                    company.id as cid,company.company_name as mycompany,designation,company_description,passout_year,job_description,user_jobs.id as jid,user_jobs.company_name as job_company,testimonials.id as tid,
                                    (SELECT U.full_name FROM users as U JOIN testimonials as T ON T.user_id1=U.id WHERE T.id=testimonials.id) as t_name,
									CASE 
									  WHEN DATEDIFF(NOW(),testimonials.created_at) != 0 THEN CONCAT(DATEDIFF(NOW(),testimonials.created_at) ,'d ago')
									  WHEN HOUR(TIMEDIFF(NOW(),testimonials.created_at)) != 0 THEN CONCAT(HOUR(TIMEDIFF(NOW(),testimonials.created_at)) ,'h ago')
									  WHEN MINUTE(TIMEDIFF(NOW(),testimonials.created_at)) != 0 THEN CONCAT(MINUTE(TIMEDIFF(NOW(),testimonials.created_at)) ,'m ago')
									  ELSE
										 CONCAT(SECOND(TIMEDIFF(NOW(),testimonials.created_at)) ,' s ago')
									END as time_elapsed,
		            			  	testimonials.description,testimonials.user_id1 as u_id,testimonials.user_id2 as u_id1, job_designation,start_year,end_year,present_job")
		            			  ->leftJoin('user_qualification','users.id','=','user_qualification.user_id')
		            			  ->leftJoin('testimonials','users.id','=','testimonials.user_id2')
		            			  ->leftJoin('company','users.id','=','company.user_id')
		            			  ->leftJoin('user_attachments','users.id','=','user_attachments.user_id')
		            			  ->leftJoin('user_jobs','users.id','=','user_jobs.user_id')
		            			  ->Where('users.id','=',$user_id)
                                  ->orderBy('testimonials.created_at','DESC')
		            			  ->get();	

				if($user_details){

					foreach($user_details as $value){

						if(!ISSET($final[$value->user_id])){
							$final[$value->user_id]=array(
									"user_id"=> (string)$value->user_id,
						            "fbid"=> $value->fbid?$value->fbid:"",
						            "linkedin_id"=> $value->linkedin_id?$value->linkedin_id:"",
						            "place"=> $value->place?$value->place:"",
						            "apn_id"=> $value->apn_id ? $value->apn_id:"",
						            "reg_id"=> $value->reg_id ? $value->reg_id:"",
						            "full_name"=> $value->full_name,
						            "email"=> $value->email,
									"password"=> $value->password,
						            "phone_number"=> $value->phone_number,
						            "profile_pic"=> $value->profile_pic?Users::getFormattedImage($value->profile_pic):"",
						            "lat"=> $value->lat?$value->lat:"0",
						            "lng"=> $value->lng?$value->lng:"0",
						            "profile_thumb"=> $value->profile_thumb?Users::getFormattedImage($value->profile_thumb):"",
						            "profile_video"=> $value->profile_video?$vid_path.$value->profile_video:"",
						            "dob"=>$value->dob,
                                    "age"=>$value->age?$value->age:"0",
						            "fraternity"=>$value->fraternity?$value->fraternity:"",
						            "bio"=>$value->bio?$value->bio:"",
						            "user_type"=>$value->user_type,
						            "access_token"=>$value->access_token,
									"jobs"=>array(),
									"testimonials"=>array(),
									"qualification"=>array(),
									"attachment"=>array(),
									"company"=>array()  
							);	
						}
						
						if(!ISSET($final[$value->user_id]['jobs'][$value->jid])){
							if($value->jid){
								$final[$value->user_id]['jobs'][$value->jid]=array(
									"job_id"=>$value->jid,
									"company_name"=>$value->job_company,
									"designation"=>$value->job_designation,
									"job_description"=>$value->job_description?$value->job_description:"",
									"start_year"=> $value->start_year,
									"end_year"=>$value->end_year,
									"present_job"=>$value->present_job?(string)$value->present_job:'0'								
								);
							}
						}
						
						if(!ISSET($final[$value->user_id]['testimonials'][$value->tid])){
							if($value->tid){
								$final[$value->user_id]['testimonials'][$value->tid]=array(
                                    "testimonial_id"=>$value->tid,
                                    "user_id"=>$value->u_id,//user_id of user who has written the testimonial
									"other_id"=>$value->u_id1,//user_id of user for whom testimonial is written
									"testimonial_by"=>$value->t_name,
									"description"=>$value->description?$value->description:"",					
									"time_elapsed"=>$value->time_elapsed				
								);
							}
						}

						if(!ISSET($final[$value->user_id]['qualification'][$value->qid])){
							if($value->qid){
								$final[$value->user_id]['qualification'][$value->qid]=array(
									"qid"=>(string)$value->qid,
									"masters"=>$value->masters,
									"college"=>$value->college,
									"passout_year"=>$value->passout_year,
									"active"=> $value->active?(string)$value->active:'0'
									);
							}
						}

						if(!ISSET($final[$value->user_id]['attachment'][$value->aid])){
							if($value->aid){
								$final[$value->user_id]['attachment'][$value->aid]=array(
									"attachment_id"=>(string)$value->aid,
									"filename"=>$value->filename?(preg_match("/(pdf|doc|docx)/i", $value->filename)?$vid_path.$value->filename:Users::getFormattedImage($value->filename)):"",							
								);
							}
						}

						if(!ISSET($final[$value->user_id]['company'][$value->cid])){
							//if($value->cid){
								$final[$value->user_id]['company'][$value->cid]=array(
									"company_id"=>$value->cid?$value->cid:"",
									"company_name"=>$value->mycompany?$value->mycompany:"",
									"company_description"=>$value->company_description?$value->company_description:"",
									"designation"=>$value->designation?$value->designation:""								
								);
							//}
						}
					}
					
						foreach($final as $value){
							$job=array();
							$qual=array();
							$attach=array();
							$company=array();
							$testimonial=array();
							foreach($value['jobs'] as $value2){
							$job[]=$value2;
							}
							$value['jobs']=$job;
							foreach($value['testimonials'] as $value2){
							$testimonial[]=$value2;
							}
							$value['testimonials']=$testimonial;
							foreach($value['qualification'] as $value2){
							$qual[]=$value2;
							}
							$value['qualification']=$qual;
							foreach($value['attachment'] as $value2){
							$attach[]=$value2;
							}
							$value['attachment']=$attach;
							foreach($value['company'] as $value2){
							$company[]=$value2;
							}
							$value['company']=$company;
									
							$data[]=$value;
						}	
					} 		     

		  return $data;
	}
	
	public static function GetFollowers($user_id){

		$data=array();
		
			$follower_data= DB::table('follow')
		            			->selectRaw('users.id,users.full_name,users.phone_number,ifnull((users.place),"") as place, users.user_type,users.profile_pic,
									ifnull((SELECT status from follow WHERE follow.follow_by='.$user_id.' AND follow.follow_to=users.id),"0") as is_followed')
								->leftJoin('users','users.id','=','follow.follow_by')
		            			->where('follow_to', $user_id)
		            			->get();
								
			$following_data= DB::table('follow')
						->selectRaw('users.id,users.full_name,users.phone_number,ifnull((users.place),"") as place,users.user_type,users.profile_pic,
							ifnull((SELECT status from follow WHERE follow.follow_by='.$user_id.' AND follow.follow_to=users.id),"0") as is_followed')
						->leftJoin('users','users.id','=','follow.follow_to')
						->where('follow_by', $user_id)
						->get();
			
				foreach($follower_data as $row){
				$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
				}
				
				foreach($following_data as $row){
				$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
				}
						
				     
			$data['followers']=$follower_data;
			$data['following']=$following_data;
			
		  return $data;
	}
	
	
    public static function getLocation($lat, $lon) {

        $url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lon&sensor=false";

        // Make the HTTP request
        $data = file_get_contents($url);
        // Parse the json response
        $output = json_decode($data);

            for($j=0;$j<count($output->results[0]->address_components);$j++){

                $cn=array($output->results[0]->address_components[$j]->types[0]);

                if(in_array("country", $cn)){
                    $country= $output->results[0]->address_components[$j]->long_name;
                }
            }

            return $country;

    }



    public static function Get_Address_From_Google_Maps($lat, $lon) {

    $url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lon&sensor=false";

    // Make the HTTP request
    $data = @file_get_contents($url);
    // Parse the json response
    $jsondata = json_decode($data,true);

    // If the json data is invalid, return empty array
    if (!self::check_status($jsondata))   return array();

    // $address = array(
    // 'country' => self::google_getCountry($jsondata),
    // 'province' => self::google_getProvince($jsondata),
    // 'city' => self::google_getCity($jsondata),
    // 'street' => self::google_getStreet($jsondata),
    // 'postal_code' => self::google_getPostalCode($jsondata),
    // 'country_code' => self::google_getCountryCode($jsondata),
    // 'formatted_address' => self::google_getAddress($jsondata),
    // );

    $country=self::google_getCountry($jsondata);
    $city=self::google_getCity($jsondata);

    if($city)
        $address=$city.', '.$country;
    else
        $address=$country;

    return $address;
    }

    public static function check_status($jsondata) {
    if ($jsondata["status"] == "OK") return true;
    return false;
    }

    
    public static function google_getCountry($jsondata) {
        return self::Find_Long_Name_Given_Type("country", $jsondata["results"][0]["address_components"]);
    }
    
    public static function google_getProvince($jsondata) {
        return self::Find_Long_Name_Given_Type("administrative_area_level_1", $jsondata["results"][0]["address_components"], true);
    }
    
    public static function google_getCity($jsondata) {
        return self::Find_Long_Name_Given_Type("locality", $jsondata["results"][0]["address_components"]);
    }
    
    public static function google_getStreet($jsondata) {
        return self::Find_Long_Name_Given_Type("street_number", $jsondata["results"][0]["address_components"]) . ' ' . self::Find_Long_Name_Given_Type("route", $jsondata["results"][0]["address_components"]);
    }
    
    public static function google_getPostalCode($jsondata) {
        return self::Find_Long_Name_Given_Type("postal_code", $jsondata["results"][0]["address_components"]);
    }
    
    public static function google_getCountryCode($jsondata) {
        return self::Find_Long_Name_Given_Type("country", $jsondata["results"][0]["address_components"], true);
    }
    
    public static function google_getAddress($jsondata) {
        return $jsondata["results"][0]["formatted_address"];
    }

    public static function Find_Long_Name_Given_Type($type, $array, $short_name = false) {
        foreach( $array as $value) {
            if (in_array($type, $value["types"])) {
                if ($short_name)    
                    return $value["short_name"];
                return $value["long_name"];
            }
        }
    }
                
				
	   	  // Video Upload Methods
    public static function uploadVideo() {
        if(Input::file('profile_video')->isValid()){
            // store file input in a variable
            $file = Input::file('profile_video');
            //get extension of file
            $ext = $file->getClientOriginalExtension();
            //directory to store images
            $dir = 'uploads';
            // change filename to random name
            $filename = substr(time(), 0, 15).str_random(30) . ".{$ext}";
            // move uploaded file to temp. directory

             $s3 = AWS::get('s3');
            $s3->putObject(array(
                'Bucket'     => 'cbrealestate',
                'Key'        => 'connected_uploads/'.$filename,
                'SourceFile' => $file->getPathname(),
                // 'ContentType' => 'video/mp4',
                'ACL' => 'public-read'
            ));

            // $upload_success = Input::file('profile_video')->move($dir, $filename);
            // $vid = $upload_success ? $filename : '';
        }
        return $vid;
    }


    public static function EmployerCompanyDetails($user_id){

			$company_detail= DB::table('company')
		            			->selectRaw('company.*')
								->leftJoin('users','users.id','=','company.user_id')
		            			->where('user_id', $user_id)
		            			->get();
		  
		  return $company_detail;
    }


    public static function Getskillset(){

			$skillset_detail= DB::table('skillset')
		            			->selectRaw('skillset.*')
		            			->get();
		  
		  return $skillset_detail;
    }


    public static function GetUserSkills($user_id){

			$skillset_detail= DB::table('user_skillset')
		            			->selectRaw('skillset.id,user_skillset.skillset_id,skillset.skill')
		            			->Join('skillset','user_skillset.skillset_id','=','skillset.id')
		            			->where('user_id',$user_id)
		            			->get();
		  
		  return $skillset_detail;
    }



    public static function UpdateHashTags($user_id,$hash_tags){

    	if($hash_tags){
    		$hashes="";
    		foreach($hash_tags as $key=>$val){
				$m=str_replace(' ', '', $val);
				$hashes.='"#'.$m.'",';
			}
		
		$num= rtrim($hashes, ', ');

		DB::table('hash_tags')->select('*')->where('user_id', $user_id)->whereRaw('hash_tag NOT IN'.'('.$num.')' )->delete();
    	}
		
    	$current_time = new DateTime();

    	foreach ($hash_tags as $key => $value) {
    		$value = str_replace(' ', '', $value);
    		$hash_detail[$key]= DB::table('hash_tags')
		            			->selectRaw('hash_tags.*')
		            			->where('user_id', $user_id)
		            			->where('hash_tags.hash_tag','#'.$value)
		            			->get();
		    if(!$hash_detail[$key]){
		    		
    				$hash_id=DB::table('hash_tags')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'hash_tag' => '#'.$value,
		            			'created_at' => $current_time,	
		            			)
		            	);
    		}
    	}
    	return true;
    }


     public static function UpdateUserJobs($user_id,$company_name,$start_year,$end_year,$designation,$job_description,$present_job){
		
		//if($job_ids){
			/*$jobs="";
			foreach($job_ids as $key=>$val){
				$m=$val;
				$jobs.='"'.$m.'",';
			}
		
		$num= rtrim($jobs, ',');*/
	
		DB::table('user_jobs')->select('*')->where('user_id', $user_id)->delete();
		//}

    	$current_time = new DateTime();

    	for ($i=0; $i <count($company_name) ; $i++) {
    		
    		/*$job_detail[$i]= DB::table('user_jobs')
		            			->selectRaw('user_jobs.*')
								->leftJoin('users','users.id','=','user_jobs.user_id')
		            			->where('user_id', $user_id)
		            			->where('user_jobs.id',$job_ids[$i])
		            			->get();
		    if($job_detail[$i]){
		    	DB::table('user_jobs')->where('user_id', $user_id)->where('id',$job_ids[$i])->update(['company_name' => $company_name[$i],'start_year'=>$start_year[$i],'end_year'=>$end_year[$i],'job_designation'=>$designation[$i],'job_description'=>$job_description[$i],'present_job'=>$present_job[$i]]);
    	    }
		    else{*/
		    		 $job_ids=DB::table('user_jobs')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'company_name' => $company_name[$i],
		            			'start_year' => $start_year[$i],
		            			'end_year' => $end_year[$i],
		            			'job_designation' => $designation[$i],		            			
		            			'job_description' => $job_description[$i],
		            			'present_job' => $present_job[$i],
		            			'created_at' => $current_time,	
		            			)
		            	);
		    //}
    	
    	}

    	return true;
    }


     public static function UpdateQualification($user_id,$masters,$college,$passout_year){

     	/*if($qual_ids){
			$qual="";
			foreach($qual_ids as $key=>$val){
				$m=$val;
				$qual.='"'.$m.'",';
			}
		
		$num= rtrim($qual, ',');*/
	
		DB::table('user_qualification')->select('*')->where('user_id', $user_id)->delete();
		//}
		
    	$current_time = new DateTime();

    	for ($i=0; $i <count($masters) ; $i++) {
    		
    		/*$qual_detail[$i]= DB::table('user_qualification')
		            			->selectRaw('user_qualification.*')
								->leftJoin('users','users.id','=','user_qualification.user_id')
		            			->where('user_id', $user_id)
		            			->where('user_qualification.id',$qual_ids[$i])
		            			->get();

		    if($qual_detail[$i]){
		    	DB::table('user_qualification')->where('user_id', $user_id)->where('id',$qual_ids[$i])->update(['masters' => $masters[$i],'college'=>$college[$i],'passout_year'=>$passout_year[$i]]);
    	    }
		    else{*/
		    		 $qids=DB::table('user_qualification')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'masters' => $masters[$i],
		            			'college' => $college[$i],
		            			'passout_year' => $passout_year[$i],
		            			'active' => '0',
		            			'created_at' => $current_time,	
		            			)
		            	);
		    //}
    	
    	}
    	return true;
    }


     public static function UpdateSkillset($user_id,$skillsets){
		
		if($skillsets){
    		$skill="";
    		foreach($skillsets as $key=>$val){
				$m=str_replace(' ', '', $val);
				$skill.='"'.$m.'",';
			}
		
		$num= rtrim($skill, ', ');
		DB::table('user_skillset')->select('*')->where('user_id', $user_id)->whereRaw('skillset_id NOT IN'.'('.$num.')' )->delete();
    	}
		

    	$current_time = new DateTime();
    	$s=array();
    	   foreach ($skillsets as $key=>$value) {

    		$skill_detail[$key]= DB::table('user_skillset')
		            			->selectRaw('user_skillset.*')
		            			->where('user_id', $user_id)
		            			->where('skillset_id',$value)
		            			->get();
		    
		    if(!$skill_detail[$key]){
		    		
    				$hash_id=DB::table('user_skillset')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'skillset_id' => $value,
		            			'created_at' => $current_time,	
		            			)
		            	);
    		}
    	}

    	return true;
    }


	public static function signUp($input) {
	
		if(isset($input['user_type']) && !empty($input['user_type'])){
			if($input['user_type']=='employer')
				$validation = Validator::make($input, Users::$signUpEmployerRules);
			else
				$validation = Validator::make($input, Users::$signUpInternRules);

				if($validation->fails()){
		        return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
		    	}
		        else{
		            $name = $input['full_name'];
                    $dob = $input['dob'];
		            $phone_number  = isset($input['phone_number'])?$input['phone_number']:"";
		            $email = $input['email'];
		            $password = $input['password'];
		            $password = Hash::make($password);
		            $fraternity = isset($input['fraternity'])?$input['fraternity']:'';
		            $user_type = $input['user_type'];
		            if($user_type=='employer'){
		            	$company=isset($input['company'])?$input['company']:"";
		            	$designation=isset($input['designation'])?$input['designation']:"";
	            		$company_description=isset($input['company_description'])?$input['company_description']:"";
		            }
		            else{
		            	$masters=isset($input['majors'])?$input['majors']:"";
		            	$college=isset($input['college'])?$input['college']:"";
		            	$passout_year=isset($input['passout_year'])?$input['passout_year']:"";
		            }
		           
		            $fbid = isset($input['fbid']) ? $input['fbid']:'0';
		            $linkedin_id = isset($input['linkedin_id'])?$input['linkedin_id']:'';
		            $bio = isset($input['bio'])?$input['bio']:'';            
		            $lat = isset($input['lat']) ? $input['lat'] : '0';
		            $lng = isset($input['lng']) ? $input['lng'] : '0';      
		            $device_token = isset($input['device_token']) ? $input['device_token'] : '';
		            $reg_id = isset($input['reg_id']) ? $input['reg_id'] : '';
		            $profile_pic = Input::file('profile_pic');
		            // $profile_thumb = Input::file('profile_thumb');
		            $profile_video = Input::file('profile_video');
		            $current_time = new DateTime();

                    if($lat && $lng && $lat!=0.0 && $lng!=0.0){
		            // $place=Users::getLocation($lat,$lng)?Users::getLocation($lat,$lng):"";
                        $place=self::Get_Address_From_Google_Maps($lat,$lng)?self::Get_Address_From_Google_Maps($lat,$lng):"" ;
                    }
		            
                    $access_token = Users::generateToken();
		            $all_skillset=Users::Getskillset();
					
		            // Handling User Profile Image
		            if($profile_pic=="") {                
		                $image = "";
		            }
		            else {
		                $image = Users::uploadImage();
		            }

		            //video thumb
		            $profile_thumb="";

		            // Handling User Profile Video
		            if($profile_video=="") {                
		                $video = "";
		            }
		            else {
		                $video = Users::uploadVideo();
		            }

		            $user_id = DB::table('users')->insertGetId(
		                array(
		                    'full_name' => $name,
		                    'email' => $email,
                            'dob'=>$dob,
		                    'password' => $password,
		                    'access_token' => $access_token,
		                    'phone_number' => $phone_number,
		                    'profile_pic' => $image,
		                    'profile_thumb'=>$profile_thumb,
		                    'profile_video' => $video,
		                    'place' => $place,
		                    'user_type'=>$user_type,
		                    'fraternity' => $fraternity,
		                    'bio'=>$bio,
		                    'fbid'=>$fbid,
		                    'linkedin_id'=>$linkedin_id,
		                    'lat' => $lat,
		                    'lng' => $lng,
		                    'push_notification'=>'1',
		                    'created_at' => $current_time,
		                    'updated_at' => $current_time,
		                )
		            );

		            if($user_type=='employer'){
						if($company){
		            	$company_id=DB::table('company')->insertGetId(
		            		array(
		            		'user_id' => $user_id,
		            		'company_name' => $company,
		            		'designation' => $designation,
		            		'company_description'=>$company_description,
		            		'created_at' => $current_time, 

		            		)	
		            		);
						}
		            }
		            else{
						if($masters){
		            	$qual_id=DB::table('user_qualification')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'masters' => $masters,
		            			'college' => $college,
		            			'passout_year'=>$passout_year,
		            			'active' => 1,
		            			'created_at' => $current_time,	
		            			)
		            	);
						}
		            }

		            	if($reg_id || $device_token)
                        	Users::setDeviceToken($user_id,$reg_id,$device_token);

		           		$user_details=Users::LoginTypeResponse($user_id);
						$profile_setup_status=Users::ProfileSetupStatus($user_id);
		            
		            return Response::json(array('status'=>'1', 'msg'=>'User Details', 'user_details'=>$user_details,'profile_complete_status'=>$profile_setup_status,'all_skillset'=>$all_skillset), 200);

		        }
		
		}
		else{
			return Response::json(array('status'=>'0', 'msg'=>'User Type Invalid'), 200);

		}
       
	}




        public static function login($input) {

        $validation = Validator::make($input, Users::$loginRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {

            $email = $input['email'];
            $password = $input['password'];
            $lat = isset($input['lat']) ? $input['lat'] : '0';
            $lng = isset($input['lng']) ? $input['lng'] : '0';
            $device_token = isset($input['device_token']) ? $input['device_token'] : '';
            $reg_id = isset($input['reg_id']) ? $input['reg_id'] : '';
            $current_time = new DateTime();

            $user_check = DB::table('users')->select('id','email', 'password')->where('email', $email)->first();            

            if(isset($user_check)) {
               
                if((Hash::check($password, $user_check->password))) {
             	
             	        // Updating the user Token
                        $token = Users::updateUserToken($user_check->id);
                	
                        if($token) {

                        	$all_skillset=Users::Getskillset();
                        	if($reg_id || $device_token)
                        	Users::setDeviceToken($user_check->id,$reg_id,$device_token);

                        	if($lat && $lng && $lat!=0.0 && $lng!=0.0){
                        		Users::updateLatLng($user_check->id,$lat,$lng);
                        		// $place=Users::getLocation($lat,$lng);  
                                $place=self::Get_Address_From_Google_Maps($lat,$lng);
                        		Users::updateLocation($user_check->id,$place);
                        	}

                            //$user_details = User::find($user_check->id);
                             /* $user_details=DB::table('users')
		            			  ->selectRaw('users.*,COALESCE(masters,"") as masters,COALESCE(college,"") as college,COALESCE(company_name,"") as company,COALESCE(designation,"") as designation')
		            			  /*->leftJoin('user_qualification', function($join)
								    {
								        $join->on('user_qualification.user_id', '=', 'user.id');
								        //$join->on('user_qualification.active', '=', 1);
								    })*/
		            			 /* ->leftJoin('user_qualification','users.id','=','user_qualification.user_id')
		            			  ->leftJoin('company','users.id','=','company.user_id')
								  ->selectRaw('*')
		            			  ->Where('users.id','=',$user_check->id)
		            			  ->get();	

		            		if($user_details[0]->profile_pic)
                            $user_details[0]->profile_pic = Users::getFormattedImage($user_details[0]->profile_pic);
                        	else
                        	$user_details[0]->image='';	
                          */
                        	$user_details=Users::LoginTypeResponse($user_check->id);
							$profile_setup_status=Users::ProfileSetupStatus($user_check->id);
                            return Response::json(array('status' => '1', 'msg' => 'Login Success','all_skillset'=>$all_skillset, 'user_details' => $user_details,'profile_complete_status'=>$profile_setup_status), 200);
                        }
                        // token null or invalid
                        else
                            return Response::json(array('status'=> '0','msg'=>'User not found in database!'), 200);
                    }
                    // Password incorrect
                    else
                        return Response::json(array('status'=> '0','msg'=>'Password Incorrect!'), 200);
                
            }
            else
                return Response::json(array('status'=>'0', 'msg'=>'Email and Password do not match'), 200);
        }

    }



       public static function editVideo($input){

    	 $validation = Validator::make($input, Users::$editVideoRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token']; 
   	          $profile_thumb = Input::file('profile_thumb');		                  
		      $profile_video = Input::file('profile_video');
		      $current_time = new DateTime();

		       	$user_id=Users::getUserIdByToken($access_token);
		       	 
		         if($user_id){
		            

		        	if($profile_video=="") {                
		                $video = "";
		                $thumb="";
		            }
		            else {
		                $video = Users::uploadPostFiles('profile_video');
		                $thumb = Users::uploadPostFiles('profile_thumb');
		            }

					if($video)
					DB::table('users')->where('id', $user_id)->update(['profile_thumb'=>$thumb,'profile_video'=>$video]);

                    $profile_setup_status=Users::ProfileSetupStatus($user_id);
					
		            return Response::json(array('status'=>'1', 'msg'=>'User Profile Updated','profile_complete_status'=>$profile_setup_status), 200);
		        	}
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        }

    }


     public static function updateUserSettings($input){


    	 $validation = Validator::make($input, Users::$userSettingsRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else{
        	  $access_token=$input['token']; 
        	  $push_notification=$input['push_notification'];

	       	  $user_id=Users::getUserIdByToken($access_token);
		      
				DB::table('users')->where('id', $user_id)->update(['push_notification'=>$push_notification]);
					
	            return Response::json(array('status'=>'1', 'msg'=>'User Settings Updated'), 200);
        	}
		          
        }

    

        //edit profile of users
    public static function editProfile($input){

    	 $validation = Validator::make($input, Users::$editProfileRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $full_name = $input['full_name'];
			  $phone_number  = isset($input['phone_number'])?$input['phone_number']:"";
			  $dob = $input['dob'];
			  $user_type = $input['user_type'];
	            if($user_type=='employer'){
	            	$company_name=isset($input['company_name'])?$input['company_name']:"";
	            	$designation=isset($input['designation'])?$input['designation']:"";
	            	$company_description=isset($input['company_description'])?$input['company_description']:"";
	            }
	            else{
	            	$masters=isset($input['majors'])?$input['majors']:"";
	            	$college=isset($input['college'])?$input['college']:"";
	            	$passout_year=isset($input['passout_year'])?$input['passout_year']:"";
					$company_name = isset($input['company_name'])?$input['company_name']:"";
					$start_year=isset($input['start_year'])?$input['start_year']:"";
					$end_year=isset($input['end_year'])?$input['end_year']:"";
					$designation=isset($input['designation'])?$input['designation']:"";
					$job_description=isset($input['job_description'])?$input['job_description']:'';
					$job_id=isset($input['job_id'])?$input['job_id']:'';
					$qual_id=isset($input['qual_id'])?$input['qual_id']:'';
					$present_job=isset($input['present_job'])?$input['present_job']:'0';
					$skillset = isset($input['skillset'])?$input['skillset']:"";
	            }
	           
	            $fbid = isset($input['fbid']) ? $input['fbid']:'0';
	            $linkedin_id = isset($input['linkedin_id'])?$input['linkedin_id']:'';
	            $bio = isset($input['bio'])?$input['bio']:'';            
		        $profile_pic = Input::file('profile_pic');      
		        $current_time = new DateTime();
		        $hash_data=array();
		       	$user_id=Users::getUserIdByToken($access_token);
		       	 if($profile_pic=="") {                
		                $image = "";
		            }
		            else {
		                $image = Users::uploadImage();
		            }

		        if($user_id){

					$profile_setup_status=Users::ProfileSetupStatus($user_id);

		            if($image)
					DB::table('users')->where('id', $user_id)->update(['full_name'=>$full_name,'dob'=>$dob,'phone_number'=>$phone_number,'profile_pic'=>$image,'bio' => $bio]);
					else
					DB::table('users')->where('id', $user_id)->update(['full_name'=>$full_name,'dob'=>$dob,'phone_number'=>$phone_number,'bio' => $bio]);
					
					if($fbid)
					DB::table('users')->where('id', $user_id)->update(['fbid'=>$fbid]);
					if($linkedin_id)
					DB::table('users')->where('id', $user_id)->update(['linkedin_id'=>$linkedin_id]);

					if($user_type=='employer'){
						$company_detail=Users::EmployerCompanyDetails($user_id);
							if($company_detail)
								DB::table('company')->where('user_id', $user_id)->update(['company_name'=>$company_name,'designation'=>$designation,'company_description'=>$company_description]);
							else
							$company_id=DB::table('company')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'company_name' => $company_name,
		            			'designation'=>$designation,
		            			'company_description'=>$company_description,
		            			'created_at' => $current_time,	
		            			)
		            	);	
					}
					else{
						$company_name=json_decode($company_name,true);
						// $job_id=json_decode($job_id,true);
						$start_year=json_decode($start_year,true);
						$end_year=json_decode($end_year,true);
						$designation=json_decode($designation,true);
						$job_description=json_decode($job_description,true);
						$present_job=json_decode($present_job,true);
						// $qual_id=json_decode($qual_id,true);
						$masters=json_decode($masters,true);
						$college=json_decode($college,true);
						$passout_year=json_decode($passout_year,true);
                        if($skillset){                          
                            // $skillset = preg_replace('/\s+/',' ',$skillset);  
                            // $skillset=str_replace(' ',"_", $skillset);
                            $skillset=json_decode($skillset,true);
                        }

						if($company_name && $skillset)
							$hash_data=array_merge_recursive($company_name,$skillset);
						elseif($company_name && !$skillset)
							$hash_data=$company_name;
						else
							$hash_data=$skillset;

						if($company_name)
						Users::UpdateUserJobs($user_id,$company_name,$start_year,$end_year,$designation,$job_description,$present_job);
						if($masters)
						Users::UpdateQualification($user_id,$masters,$college,$passout_year);
						if($company_name || $skillset)
						Users::UpdateHashTags($user_id,$hash_data);
						if($skillset){
						Users::UpdateSkillset($user_id,$skillset);							
						}
					}


		            $user_details= Users::LoginTypeResponse($user_id);

		            return Response::json(array('status'=>'1', 'msg'=>'User Profile Updated','result'=>$user_details,'profile_complete_status'=>$profile_setup_status), 200);
		        	}
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        }

    }


         //edit profile of users for IOS
    public static function updateProfile($input){
    	
    	 $validation = Validator::make($input, Users::$editProfileRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $full_name = $input['full_name'];
			  $phone_number  = isset($input['phone_number'])?$input['phone_number']:"";
			  $dob = $input['dob'];
			  $user_type = $input['user_type'];
	            if($user_type=='employer'){
	            	$company_name=isset($input['company_name'])?$input['company_name']:"";
	            	$designation=isset($input['designation'])?$input['designation']:"";
	            	$company_description=isset($input['company_description'])?$input['company_description']:"";
	            }
	            else{
	            	$masters=isset($input['majors'])?$input['majors']:"";
	            	$college=isset($input['college'])?$input['college']:"";
	            	$passout_year=isset($input['passout_year'])?$input['passout_year']:"";
					$company_name = isset($input['company_name'])?$input['company_name']:"";
					$start_year=isset($input['start_year'])?$input['start_year']:"";
					$end_year=isset($input['end_year'])?$input['end_year']:"";
					$designation=isset($input['designation'])?$input['designation']:"";
					$job_description=isset($input['job_description'])?$input['job_description']:'';
					$job_id=isset($input['job_id'])?$input['job_id']:'';
					$qual_id=isset($input['qual_id'])?$input['qual_id']:'';
					$present_job=isset($input['present_job'])?$input['present_job']:'0';
					$skillset = isset($input['skillset'])?$input['skillset']:"";
	            }
	           
	            $fbid = isset($input['fbid']) ? $input['fbid']:'0';
	            $linkedin_id = isset($input['linkedin_id'])?$input['linkedin_id']:'';
	            $bio = isset($input['bio'])?$input['bio']:'';            
		        $profile_pic = Input::file('profile_pic');    
		        $current_time = new DateTime();

		       	$user_id=Users::getUserIdByToken($access_token);
		       	 if($profile_pic=="") {                
		                $image = "";
		            }
		            else {
		                $image = Users::uploadImage();
		            }

		          if($user_id){

                    $profile_setup_status=Users::ProfileSetupStatus($user_id);
                    
		            if($image)
					DB::table('users')->where('id', $user_id)->update(['full_name'=>$full_name,'dob'=>$dob,'phone_number'=>$phone_number,'profile_pic'=>$image,'bio' => $bio]);
					else
					DB::table('users')->where('id', $user_id)->update(['full_name'=>$full_name,'dob'=>$dob,'phone_number'=>$phone_number,'bio' => $bio]);
					
					if($fbid)
					DB::table('users')->where('id', $user_id)->update(['fbid'=>$fbid]);
					if($linkedin_id)
					DB::table('users')->where('id', $user_id)->update(['linkedin_id'=>$linkedin_id]);

					if($user_type=='employer'){
						$company_detail=Users::EmployerCompanyDetails($user_id);
							if($company_detail)
								DB::table('company')->where('user_id', $user_id)->update(['company_name'=>$company_name,'designation'=>$designation,'company_description'=>$company_description]);
							else
							$company_id=DB::table('company')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'company_name' => $company_name,
		            			'designation'=>$designation,
		            			'company_description'=>$company_description,
		            			'created_at' => $current_time,	
		            			)
		            	);	
					}
					else{
						if($company_name){
							$company_name=str_replace('"',"", $company_name);
							$company_name=explode(",",$company_name);
						}
						$start_year=str_replace('"',"", $start_year);
						$start_year=explode(",",$start_year);
						$end_year=str_replace('"',"", $end_year);
						$end_year=explode(",",$end_year);
						$designation=str_replace('"',"", $designation);
						$designation=explode(",",$designation);
						$job_description=str_replace('"',"", $job_description);
						$job_description=explode(",",$job_description);
						$present_job=str_replace('"',"", $present_job);
						$present_job=explode(",",$present_job);
						$masters=str_replace('"',"", $masters);
						$masters=explode(",",$masters);
						$college=str_replace('"',"", $college);
						$college=explode(",",$college);
						$passout_year=str_replace('"',"", $passout_year);
						$passout_year=explode(",",$passout_year);
						if($skillset){
							$skillset=str_replace('"',"", $skillset); 
                            // $skillset = preg_replace('/\s+/',' ',$skillset);                     
                            // $skillset=str_replace(' ',"_", $skillset);
							$skillset=explode(",",$skillset);
						}
						

						if($company_name && $skillset)
							$hash_data=array_merge_recursive($company_name,$skillset);
						elseif($company_name && !$skillset)
							$hash_data=$company_name;
						else
							$hash_data=$skillset;

						if($company_name)
						Users::UpdateUserJobs($user_id,$company_name,$start_year,$end_year,$designation,$job_description,$present_job);
						if($masters)
						Users::UpdateQualification($user_id,$masters,$college,$passout_year);
						if($company_name || $skillset)
						Users::UpdateHashTags($user_id,$hash_data);
						if($skillset){
						Users::UpdateSkillset($user_id,$skillset);							
						}

					}

		            $user_details= Users::LoginTypeResponse($user_id);

		            return Response::json(array('status'=>'1', 'msg'=>'User Profile Updated','result'=>$user_details,'profile_complete_status'=>$profile_setup_status), 200);
		        	}
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }  
        }
    }


    public static function logout($input) {

        $validation = Validator::make($input, Users::$accessTokenRequired);
        if($validation->fails()) {
            return Response::json(array('status'=>0, 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {

            $access_token = $input['token'];
            $user_id = Users::getUserIdByToken($access_token);

            DB::table('users')->where('id', $user_id)->update(['access_token' => "","reg_id"=>"","apn_id"=>""]);
            
            return Response::json(array('status'=>1, 'msg'=>'Successfully logged out'), 200);
        }

    }


    public static function searchUsers($input){

    	 $validation = Validator::make($input, Users::$searchRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $searchkey = $input['searchkey'];
			  $user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		          	$search_data= DB::table('users')
						->selectRaw("users.id as user_id,users.full_name,users.phone_number,users.user_type,users.profile_pic,
									(CASE WHEN user_type='employer' THEN (SELECT company.designation FROM company WHERE company.user_id=users.id LIMIT 1) 
									ELSE (SELECT user_jobs.job_designation FROM user_jobs WHERE user_jobs.user_id=users.id LIMIT 1) END ) AS designation")
						->where('full_name', 'LIKE', '%'.$searchkey.'%')
						->where('users.id','!=',$user_id)
						->get();

					foreach($search_data as $row){
						$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
						$row->designation=$row->designation?$row->designation:"";
					}

		            if($search_data)
		            return Response::json(array('status'=>'1', 'msg'=>'Users Found','search_data'=>$search_data), 200);
		        	else
		        	return Response::json(array('status'=>'2','msg'=>"No User Found"), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        }

    }


    public static function HashUsers($input){

    	 	 $validation = Validator::make($input, Users::$searchRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $searchkey = $input['searchkey'];
			  $user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		       		$search_data= DB::table('users')
						->selectRaw("users.id as user_id,users.full_name,users.phone_number,users.user_type,users.profile_pic,
									(CASE WHEN user_type='employer' THEN (SELECT company.designation FROM company WHERE company.user_id=users.id LIMIT 1) 
									ELSE (SELECT user_jobs.job_designation FROM user_jobs WHERE user_jobs.user_id=users.id LIMIT 1) END ) AS designation")
						->leftJoin('hash_tags','user_id','=','users.id')
						->where('hash_tag', 'LIKE', '%'.$searchkey.'%')
						->get();

					foreach($search_data as $row){
						$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
						$row->designation=$row->designation?$row->designation:"";
					}

		            if($search_data)
		            return Response::json(array('status'=>'1', 'msg'=>'Users Found','search_data'=>$search_data), 200);
		        	else
		        	return Response::json(array('status'=>'2','msg'=>"No User Found"), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        }

    }


    public static function searchHash($input){

    	 $validation = Validator::make($input, Users::$searchRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $searchkey = $input['searchkey'];
			  $user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		          	$hash_data=DB::table('hash_tags')
		          				->selectRaw('hash_tag,count(*) as user_count')
		          				->where('hash_tag','LIKE','%'.$searchkey.'%')
		          				->groupBy('hash_tag')
		          				->get();

		    			// $search_data= DB::table('users')
						// ->selectRaw("users.id as user_id,users.full_name,users.phone_number,users.user_type,users.profile_pic,
						// 			(CASE WHEN user_type='employer' THEN (SELECT company.designation FROM company WHERE company.user_id=users.id LIMIT 1) 
						// 			ELSE (SELECT user_jobs.job_designation FROM user_jobs WHERE user_jobs.user_id=users.id LIMIT 1) END ) AS designation")
						// ->leftJoin('hash_tags','user_id','=','users.id')
						// ->where('hash_tag', 'LIKE', '%'.$searchkey.'%')
						// ->get();

		          	$search_data= DB::table('users')
						->selectRaw("users.id as user_id,users.full_name,users.phone_number,users.user_type,users.profile_pic,
									(CASE WHEN user_type='employer' THEN (SELECT company.designation FROM company WHERE company.user_id=users.id LIMIT 1) 
									ELSE (SELECT user_jobs.job_designation FROM user_jobs WHERE user_jobs.user_id=users.id LIMIT 1) END ) AS designation")
						->where('full_name', 'LIKE', '%'.$searchkey.'%')
						->where('users.id','!=',$user_id)
						->get();

					foreach($search_data as $row){
						$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
						$row->designation=$row->designation?$row->designation:"";
					}

		            if($search_data || $hash_data)
		            return Response::json(array('status'=>'1', 'msg'=>'Users Found','search_data'=>$search_data,'hash_data'=>$hash_data), 200);
		        	else
		        	return Response::json(array('status'=>'2','msg'=>"No User Found"), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        }

    }


    public static function addAttachments($input){
    	
    	 $validation = Validator::make($input, Users::$addAttachmentsRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $filename = Input::file('filename');

		      $current_time = new DateTime();


		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		          	$attachment_file = Users::uploadFile();
		            

		            $attach_id=DB::table('user_attachments')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'filename' => $attachment_file,
		            			'created_at' => $current_time,	
		            			)
		            	);

		            if($attachment_file)
		            return Response::json(array('status'=>'1', 'msg'=>'File Uploaded'), 200);
		        	else
		        	return Response::json(array('status'=>'0','msg'=>"Uploading Error"), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
        }
    }


        public static function deleteAttachment($input){
        
         $validation = Validator::make($input, Users::$deleteAttachmentRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
              $access_token=$input['token'];
              $attachment_id = $input['attachment_id'];

                $user_id=Users::getUserIdByToken($access_token);

                  if($user_id){
                    
                    $att_data= DB::table('user_attachments')
                                ->select('*')
                                ->where('id', $attachment_id)
                                ->where('user_id', $user_id)
                                ->delete();

                     
                    return Response::json(array('status'=>'1', 'msg'=>'Attachment Removed'), 200);
                   }
                  else{
                    return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
                  }
           
        }

    }


    //add jobs of employee
    public static function addJob($input){
    	
    	 $validation = Validator::make($input, Users::$addJobRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $company_name = $input['company_name'];
        	  $start_year=$input['start_year'];
        	  $end_year=$input['end_year'];
        	  $designation=$input['designation'];
        	  $job_description=$input['job_description']?$input['job_description']:'';
        	  $present_job=$input['present_job']?$input['present_job']:'0';

		      $current_time = new DateTime();

		      $user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            
		            $job_data= DB::table('user_jobs')
		            			->select('*')
		            			->where('company_name', $company_name)
		            			->where('user_id', $user_id)
		            			->first();

		            if(!$job_data){
		            $job_id=DB::table('user_jobs')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'company_name' => $company_name,
		            			'start_year' => $start_year,
		            			'end_year' => $end_year,
		            			'job_designation' => $designation,		            			
		            			'job_description' => $job_description,
		            			'present_job' => $present_job,
		            			'created_at' => $current_time,	
		            			)
		            	);

		            if($job_id)
		            return Response::json(array('status'=>'1', 'msg'=>'Job Added'), 200);
		        	else
		        	return Response::json(array('status'=>'0','msg'=>"Error"), 200);
		        	}
		        	else{
		        		return Response::json(array('status'=>'0', 'msg'=>'Job Already Added'),200);
		        	}
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }  
        }
    }



  	public static function addQualification($input){
    	
    	 $validation = Validator::make($input, Users::$addQualificationRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $masters = $input['masters'];
        	  $college=$input['college'];

		      $current_time = new DateTime();


		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            
		            $qual_data= DB::table('user_qualification')
		            			->select('*')
		            			->where('masters', $masters)
		            			->where('college',$college)
		            			->where('user_id', $user_id)
		            			->first();

		            if(!$qual_data){
		            $qual_id=DB::table('user_qualification')->insertGetId(
		            		array(
		            			'user_id' => $user_id,
		            			'masters' => $masters,
		            			'college' => $college,
		            			'active' =>  '0',
		            			'created_at' => $current_time,	
		            			)
		            	);

		            if($qual_id)
		            return Response::json(array('status'=>'1', 'msg'=>'Qualification Linked with User'), 200);
		        	else
		        	return Response::json(array('status'=>'0','msg'=>"Error"), 200);
		        	}
		        	else{
		        		return Response::json(array('status'=>'0', 'msg'=>'Qualification Already Linked with User'),200);
		        	}


		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		            
        }

    }

	public static function getUserProfile($input){
	
		 $validation = Validator::make($input, Posts::$UserPostsRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
			
			  $access_token=$input['token'];
			  $other_id=$input['other_id'];
			  
			  $user_id=Users::getUserIdByToken($access_token);
			   if($user_id){
				  
		            $user_details= Users::LoginTypeResponse($other_id);
					$user_posts=Posts::FetchPosts($user_id,$other_id);	
					$friends=Users::GetFollowers($other_id);
					$skillset=Users::Getskillset();
					$user_skillset=Users::GetUserSkills($other_id);	
					$follower_data=$friends['followers'];
					$following_data=$friends['following'];
					$profile_setup_status=Users::ProfileSetupStatus($user_id);
					$is_follow_status=Users::getIsFollow($user_id,$other_id);
				
		            if($user_details){
						return Response::json(array('status'=>'1', 'msg'=>'User Details','profile_complete_status'=>$profile_setup_status,'result'=>$user_details,'user_posts'=>$user_posts,'followers'=>$follower_data,'following'=>$following_data,'all_skillset'=>$skillset,'user_skillset'=>$user_skillset,'is_follow_status'=>$is_follow_status), 200);
		        	}
					else
						return Response::json(array('status'=>'0','msg'=>'User Not Found'),200);
		        	
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
			
		}
	
	
	}

    //add testimonials of employee
    public static function addTestimonial($input){
    	
    	 $validation = Validator::make($input, Users::$addTestimonialRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $other_id = $input['user_id'];
        	  $description=$input['description'];

		      $current_time = new DateTime();

		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){

		            $testimonial_id=DB::table('testimonials')->insertGetId(
		            		array(
		            			'user_id1' => $user_id,
		            			'user_id2' => $other_id,
		            			'description' => $description,
		            			'created_at' => $current_time,	
		            			)
		            	);

		            if($testimonial_id)
		            return Response::json(array('status'=>'1', 'msg'=>'Testimonial Added','testimonial_id'=>$testimonial_id), 200);
		        	else
		        	return Response::json(array('status'=>'0','msg'=>"Error"), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		   
            }

    }


        //edit testimonials of employee
    public static function editTestimonial($input){
    	
    	 $validation = Validator::make($input, Users::$editTestimonialRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $other_id = $input['user_id'];
        	  $description=$input['description'];
        	  $testimonial_id=$input['testimonial_id'];

		      $current_time = new DateTime();

		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            
		            $testimonial_data= DB::table('testimonials')
		            			->select('*')
		            			->where('user_id2', $other_id)
		            			->where('user_id1', $user_id)
		            			->where('id',$testimonial_id)
		            			->first();

		            if($testimonial_data){
		             DB::table('testimonials')->where('user_id1', $user_id)->where('user_id2',$other_id)->where('id',$testimonial_id)->update(['description' => $description,'created_at' => $current_time]);

		            return Response::json(array('status'=>'1', 'msg'=>'Testimonial Updated'), 200);
		        	
		        	}
		        	else{
		        		return Response::json(array('status'=>'0', 'msg'=>'Testimonial Doesnot Exists'),200);
		        	}


		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		   
        }

    }



    public static function deleteTestimonial($input){
    	
    	 $validation = Validator::make($input, Users::$deleteTestimonialRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $testimonial_id = $input['testimonial_id'];

		      	$user_id=Users::getUserIdByToken($access_token);

		          if($user_id){
		            
		            $testimonial_data= DB::table('testimonials')
		            			->select('*')
		            			->where('id', $testimonial_id)
		            			->where('user_id1', $user_id)
		            			->delete();

		            if($testimonial_data){
		             
		            return Response::json(array('status'=>'1', 'msg'=>'Testimonial Removed'), 200);
		        	
		        	}
		        	else{
		        		return Response::json(array('status'=>'0', 'msg'=>'Testimonial Doesnot Exists'),200);
		        	}


		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		   
        }

    }


	public static function FollowUser($input){
    	
    	 $validation = Validator::make($input, Posts::$UserPostsRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
        	  $other_id = $input['other_id'];
		      $user_id=Users::getUserIdByToken($access_token);
			  $current_time = new DateTime();
			  
		          if($user_id){
		            $uname=Users::getUsername($user_id);
		            $push_status=Users::getPushSettings($other_id);
		            $user_det=Users::getUserType($user_id);
	          		$user_type=$user_det->user_type;

	            	$push_ids=Users::getUserPushIds($other_id);
	            	$reg_id=$push_ids->reg_id;
	            	$device_token=$push_ids->apn_id;
					
		            $friend_data= DB::table('follow')
		            			->select('*')
		            			->where('follow_by', $user_id)
		            			->where('follow_to', $other_id)
		            			->first();
					
						  $notify_message=$uname." has started following you";
					 	  $Ninput['sender_id']=$user_id;
						  $Ninput['receiver_id']= $other_id;
						  if($friend_data)
						  $Ninput['type_id']=$friend_data->id;
						  $Ninput['type']='follow';
						  $Ninput['title']=$notify_message;
						  
						 
					
		            if(!$friend_data){
		            $follow_id=DB::table('follow')->insertGetId(
		            		array(
		            			'follow_by' => $user_id,
		            			'follow_to' => $other_id,
		            			'status' => '1',
		            			'created_at' => $current_time,	
		            			)
		            	);
						
						$Ninput['type_id']=$follow_id;
					 	Notifications::addNotification($Ninput);

					 	if($push_status)
				    		Notifications::SendPushNotification($notify_message,$reg_id,$device_token,5,$user_id,$user_type);
					
					}
					else{
						 $follow_id= DB::table('follow')
		            			->select('*')
		            			->where('follow_by', $user_id)
		            			->where('follow_to', $other_id)
		            			->delete();
								
						 Notifications::RemoveNotification($Ninput);
					}
					
					$friends=Users::GetFollowers($user_id);	
					$follower_data=$friends['followers'];
					$following_data=$friends['following'];

		            if($follow_id)
		            return Response::json(array('status'=>'1', 'msg'=>'Friend List Updated','followers'=>$follower_data,'following'=>$following_data), 200);
		        	else
		        	return Response::json(array('status'=>'0','msg'=>"Error"), 200);
		          }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		   
        }

    }


	public static function getFriends($input){
    	
    	 $validation = Validator::make($input, Posts::$FeedRules);
        if($validation->fails()) {
            return Response::json(array('status'=>'0', 'msg'=>$validation->getMessageBag()->first()), 200);
        }
        else {
        	  $access_token=$input['token'];
			  
		      $user_id=Users::getUserIdByToken($access_token);
			  
		          if($user_id){
		            
		            $follower_data= DB::table('follow')
		            			->selectRaw('users.id,users.full_name,users.phone_number,ifnull((users.place),"") as place, users.user_type,users.profile_pic,
									ifnull((SELECT status from follow WHERE follow.follow_by='.$user_id.' AND follow.follow_to=users.id),"0") as is_followed')
								->leftJoin('users','users.id','=','follow.follow_by')
		            			->where('follow_to', $user_id)
		            			->get();
								
					$following_data= DB::table('follow')
		            			->selectRaw('users.id,users.full_name,users.phone_number,ifnull((users.place),"") as place,users.user_type,users.profile_pic,
									ifnull((SELECT status from follow WHERE follow.follow_by='.$user_id.' AND follow.follow_to=users.id),"0") as is_followed')
								->leftJoin('users','users.id','=','follow.follow_to')
		            			->where('follow_by', $user_id)
		            			->get();
					
					foreach($follower_data as $row){
						$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
					}
				
					foreach($following_data as $row){
						$row->profile_pic=$row->profile_pic?Users::getFormattedImage($row->profile_pic):"";
					}
					
		            return Response::json(array('status'=>'1', 'msg'=>'Friends List','followers'=>$follower_data,'following'=>$following_data), 200);
		        }
		          else{
		          	return Response::json(array('status'=>'0','msg'=>"Token Expired"), 200);
		          }
		   
        }

    }





}
