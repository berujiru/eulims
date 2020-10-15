<?php

namespace frontend\modules\api\controllers;

use Yii;

use common\models\system\LoginForm;
use common\models\system\Profile;
use common\models\system\User;
use common\models\auth\AuthAssignment;
use common\models\system\Rstl;

use common\models\message\Chat;
use common\models\message\Contacts;
use common\models\message\GroupMember;
use common\models\message\ChatGroup;
use yii\web\UploadedFile;

use yii\data\ActiveDataProvider;
class MessageController extends \yii\rest\Controller
{
	public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \sizeg\jwt\JwtHttpBearerAuth::class,
            'except' => ['login', 'server'],
            //'user'=> [\Yii::$app->referralaccount]


        ];

        return $behaviors;
    }

    protected function verbs(){
        return [
            'login' => ['POST'],
            'logout' => ['POST'],
            'user' => ['GET'],
             'setmessage' => ['POST'],
             'data' => ['GET'],
        ];
    }
	
	 public function actionLogin()
    {
            $model = new LoginForm();
            $my_var = \Yii::$app->request->post();
            $model->email = $my_var['email'];
            $model->password = $my_var['password'];
           
            if ($model->login()) {      
                $signer = new \Lcobucci\JWT\Signer\Hmac\Sha256();
                /** @var Jwt $jwt */
                $jwt = \Yii::$app->jwt;
                $token = $jwt->getBuilder()
                    ->setIssuer('http://example.com')// Configures the issuer (iss claim)
                    ->setAudience('http://example.org')// Configures the audience (aud claim)
                    ->setId('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
                    ->setIssuedAt(time())// Configures the time that the token was issue (iat claim)
                    ->setExpiration(time() + 3600 * 2400000)// Configures the expiration time of the token (exp claim)
                    ->set('uid', \Yii::$app->user->identity->user_id)// Configures a new claim, called "uid"
                    //->set('username', \Yii::$app->user->identity->username)// Configures a new claim, called "uid"
                    ->sign($signer, $jwt->key)// creates a signature using [[Jwt::$key]]
                    ->getToken(); // Retrieves the generated token
    
                    $users = User::find()->where(['LIKE', 'email', $my_var['email']])->one();
                    $profile = Profile::find()->where(['user_id'=>$users->user_id])->one();
                    $role = AuthAssignment::find()->where(['user_id'=>$users->user_id])->one();
        
                    return $this->asJson([
                        'token' => (string)$token,
                        'user'=> (['email'=>$users->email,
                                    'firstName'=>$profile->firstname,
                                    'middleInitial' => $profile->middleinitial,
                                    'lastname' => $profile->lastname,
                                    'type' => $role->item_name,]),
						'userid'=> $profile->user_id,			
                    ]);
                } else {
                    return $this->asJson([
                        'success' => false,
                        'message' => 'Email and Password didn\'t match',
                    ]);
                }
    }

    public function actionUser()
    {  
        $user_id =\Yii::$app->user->identity->profile->user_id;
        $users = User::find()->where(['LIKE', 'user_id', $user_id])->one();
        $profile = Profile::find()->where(['user_id'=>$user_id])->one();
        $role = AuthAssignment::find()->where(['user_id'=>$users->user_id])->one();
        $signer = new \Lcobucci\JWT\Signer\Hmac\Sha256();
        /** @var Jwt $jwt */
        $jwt = \Yii::$app->jwt;
        $token = $jwt->getBuilder()
            ->setIssuer('http://example.com')// Configures the issuer (iss claim)
            ->setAudience('http://example.org')// Configures the audience (aud claim)
            ->setId('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
            ->setIssuedAt(time())// Configures the time that the token was issue (iat claim)
            ->setExpiration(time() + 3600 * 2400000)// Configures the expiration time of the token (exp claim)
            ->set('uid', \Yii::$app->user->identity->user_id)// Configures a new claim, called "uid"
            //->set('username', \Yii::$app->user->identity->username)// Configures a new claim, called "uid"
            ->sign($signer, $jwt->key)// creates a signature using [[Jwt::$key]]
            ->getToken(); // Retrieves the generated token
        return $this->asJson([
                'token' => (string)$token,
                'user'=> (['email'=>$users->email,
                'firstName'=>$profile->firstname,
                'middleInitial' => $profile->middleinitial,
                'lastname' => $profile->lastname,
                'type' => $role->item_name]),
                'user_id'=> $users->user_id
            ]);               
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLogout()
    {
        return $this->render('index');
    }

    /**
     * @return \yii\web\Response
     */
    public function actionData()
    {
        return $this->getuserid();
    }

    function getuserid(){
        $myvar = \Yii::$app->request->headers->get('Authorization');

        $rawToken = explode("Bearer ", $myvar);
        $rawToken = $rawToken[1];
        $token = \Yii::$app->jwt->getParser()->parse((string) $rawToken);
        return $token->getClaim('uid');
    }

     //************************************************
     public function actionServer(){

        $server = $_SERVER['SERVER_NAME'];
        if(!$sock = @fsockopen($server, 80))
            {
                $data = array("status" => "offline");
            }
            else
            {
                $data = array("status" => "online");
            }

           
        return $this->asJson($data);   
    }
	
    public function actionSetmessage(){ //send message
        $my_var = \Yii::$app->request->post();


       if(!$my_var){
            return $this->asJson([
                'success' => false,
                'message' => 'POST empty',
            ]); 
       }else{
			//attributes Purpose, Sample Quantity, Sample type, Sample Name and Description, schedule date and datecreated
			$chat = new Chat();
			$chat->sender_userid = $my_var['sender_userid'];
			$chat->chat_data= $my_var['message'];
			$type=$my_var['type'];
			$id=$my_var['id'];
			$dataxtype=$my_var['dataxtype'];
			$chat->status_id=1;//sent
			$chat->chat_data_type=$type; //message text or file
			$chat->message_type=$dataxtype; //personnel message or group
			//tbl_contact
			if($type == 1){
				$chat->contact_id=$id;
			}else{
				$chat->group_id=$id;
				
			}
			///////////////////////
			if($chat->save()){
				return $this->asJson([
					'success' => true,
					'message' => 'Message Sent',
				]); 
			}
			else{
				return $this->asJson([
					'success' => false,
					'message' => 'Message Failed',
				]); 
			}
	   }
        
    }
	
	public function actionGetuser(){
        $my_var = Profile::find()->all();
        return $this->asJson(
            $my_var
        );
    }
	
	public function actionPossiblerecipients($userid){
        $my_var = Profile::find()->where(['!=', 'user_id', $userid])->all();
		return $my_var;
    }
	
	public function actionGetcontact(){
       $my_var = \Yii::$app->request->post();
	   if(!$my_var){
		return $this->asJson([
			'success' => false,
			'message' => 'POST empty',
		]); 
	   }
	   
		$userid=$my_var['userid'];
		$recipientid=$my_var['recipientid'];
        $type=$my_var['type'];
		$id="";  
		if ($type == 1) { //Personnal messages
			$arr = [$userid,$recipientid];
			sort($arr);
			$str = implode(",", $arr); 
			
			$contact = Contacts::find()->where(['user_id'=>$str])->one();
			
			 
			if (!$contact){
			
				$convo= new Contacts();
				$convo->user_id=$str;
				$convo->save(false);
				$id=$convo->contact_id;
			}else{
				$id=$contact->contact_id;
			}
			
			$chat=$this->Getpersonalchat($id);
			$profile=$this->GetProfile($recipientid);	
		}
		if ($type == 2) { //Group Messages
		    $id=$recipientid;
			$chat=$this->Getgroupchat($id);
			$profile=$this->Getgrouprofile($recipientid);
		}
		
		return $this->asJson(
           [
			   'chat'=> $chat,
			   'profile'=> $profile,
			   'id'=> $id
		   ]
        );
    }
	public function Getgroupchat($id){
		
	$chat = Chat::find()
	->select(['chat_data','chat_id', 'sender_userid', 'timestamp', 'status_id', 'group_id', 'contact_id' => 'eulims.tbl_profile.fullname','chat_data_type','message_type'])
	->where(['group_id'=>$id])
	->innerJoin('eulims.tbl_profile', 'eulims.tbl_profile.user_id=eulims_message.tbl_chat.sender_userid')
	->orderBy(['timestamp' => SORT_ASC ])
	->all();

	  return $chat; 
	  
	  
	}
	
	public function Getgrouprofile($id){
        $profile = ChatGroup::find()->where(['chat_group_id'=>$id])->one();
        return $profile;
    }
	
	public function Getpersonalchat($contactid){
		
	  $my_var = \Yii::$app->request->post();
	  $chat = Chat::find()->where(['contact_id'=>$contactid])->all();
	  return $chat;
	}
	
	public function GetProfile($user_id){
        $profile = Profile::find()->where(['user_id'=>$user_id])->one();
        return $profile;
    }
	
	public function actionSavefile(){
		$valid_extensions = ['jpeg', 'jpg', 'png', 'gif', 'bmp' , 'pdf' , 'doc' , 'ppt','docx','xlsx', 'pptx']; 
		$path = 'uploads/message/'; // upload directory

		$img = $_FILES['filetoupload']['name'];
        $tmp = $_FILES['filetoupload']['tmp_name'];
	
		$ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
		// can upload same image using rand function
		$final_image = rand(1000,1000000).$img;
		// check's valid format
		if(in_array($ext, $valid_extensions)) 
		{ 
			$path = $path.strtolower($final_image); 
			if(move_uploaded_file($tmp,$path)) 
			{
				
			}
		} 
		
		return $this->asJson(
			['message' => 'success',
			'filename'=>$final_image
			]
		); 	
    }
	
	public function actionGetgroup($userid){
         $group = GroupMember::find()
		//->select('tbl_chat_group.group_name')
		->joinWith('chatGroup')
		->where('tbl_chat_group.chat_group_id =tbl_group_member.chat_group_id')
		->andWhere(['user_id'=>$userid])
		->asArray()->all();
        return $this->asJson(
            $group
        ); 
    }
   
	
	public function actionSetgroup(){ //send message
       $my_var = \Yii::$app->request->post();


       if(!$my_var){
            return $this->asJson([
                'success' => false,
                'message' => 'POST empty',
            ]); 
       }
	   else{
		    $userids=$my_var['userids'];
			$str_user = explode(',', $userids);
			$arr_length = count($str_user);
			$model = new ChatGroup();
			$model->createdby_userid=$my_var['sender_userid'];
			$model->group_name= $my_var['groupname'];
			$model->save();
			
			for($i=0;$i<$arr_length;$i++){
				$member= new GroupMember();
				$member->chat_group_id = $model->chat_group_id;
				$member->user_id=$str_user[$i];
				$member->save();
			}
			//Adding the user who created the group
			$member= new GroupMember();
			$member->chat_group_id = $model->chat_group_id;
			$member->user_id=$my_var['sender_userid'];
			$member->save(); 
			////////////
			
			return $this->asJson(
			['message' => 'success'
			]
		); 	
	   }
	}   
}
