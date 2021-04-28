<?php

namespace frontend\modules\chat\controllers;

use Yii;
use common\models\message\Chat;
use common\models\message\ChatGroup;
use common\models\message\ChatSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\system\LoginForm;
use linslin\yii2\curl;
use yii\helpers\Json;
use yii\data\ActiveDataProvider;

use common\components\Notification;

use  yii\web\Session;


/**
 * InfoController implements the CRUD actions for Chat model.
 */
class InfoController extends Controller
{
	public $source = 'https://eulims.onelab.ph/api/message/';
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Chat models.
     * @return mixed
     */
    public function actionIndex()
    {
		 
		$session = Yii::$app->session;

		if(isset($_SESSION['usertoken'])){
			

			
			$token=$_SESSION['usertoken'];
			$userid= Yii::$app->user->identity->profile->user_id;
			//get profile
			$authorization = "Authorization: Bearer ".$token; 
			$apiUrl=$this->source.'/getuser';
			$curl = new curl\Curl();
			$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curl->setOption(CURLOPT_TIMEOUT, 180);
			$list = $curl->get($apiUrl);
			$decode=Json::decode($list);
		
		
		
			//GROUPLIST
			$groupUrl=$this->source.'/getgroup?userid='.$userid;
			$curlgroup = new curl\Curl();
			$curlgroup->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curlgroup->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curlgroup->setOption(CURLOPT_TIMEOUT, 180);
			$grouplist = $curlgroup->get($groupUrl);
			$group=Json::decode($grouplist);
			//var_dump($group);
			
			//exit; 
			//
			$chat = new Chat();
			$searchModel = new ChatSearch();
			$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
			return $this->render('index', [
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider,
				'contacts' => $decode,
				'chat' => $chat,
				'group'=> $group
				
			]);
		}else{
			$model = new LoginForm();
			if ($model->load(Yii::$app->request->post())){
			}else{
				return $this->render('login', [
				'model' => $model
				]);
			}	
		}	
    }

    /**
     * Displays a single Chat model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Chat model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Chat();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->chat_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Chat model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->chat_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Chat model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Chat model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Chat the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Chat::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
	
	public function Settoken($token,$userid,$pstcid)
    {
		$session = Yii::$app->session;
		
		$session->set('usertoken', $token);
		$session->set('userid', $userid);
		$session->set('pstcid', $pstcid);
		//return;
	}	
    /*public function beforeAction($action) 
	{ 
		$this->enableCsrfValidation = false; 
		return parent::beforeAction($action); 
	}	*/
	
	 public function actionGroup()
    {
	    $model = new ChatGroup();
		
		if(isset($_SESSION['usertoken'])){
			$token=$_SESSION['usertoken'];
			$userid= $_SESSION['userid'];
			//get profile
			$authorization = "Authorization: Bearer ".$token; 
			$apiUrl=$this->source.'possiblerecipients?userid='.$userid;
			$curl = new curl\Curl();
			$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curl->setOption(CURLOPT_TIMEOUT, 180);
			$list = $curl->get($apiUrl);
			$decode=Json::decode($list);
			
		
			if ($model->load(Yii::$app->request->post())) {
				
			}
			else{
				return $this->renderAjax('group', [
				'model' => $model,
				'possible_recipients' => $decode,
				]);
			}
		
		}	
		else{
			$login = new LoginForm();
			if ($login->load(Yii::$app->request->post())){
			}else{
				return $this->render('login', [
				'model' => $login
				]);
			}	
		}
        
    }
	
	public function actionLogin(){
		$model = new LoginForm();
		if ($model->load(Yii::$app->request->post())){
			$email=$model->email;
			$password= $model->password;
			
			$params = [
				'email' => $email,
				'password' => $password
			];

			
				// $authorization = "Authorization: Bearer ".$token; 
				$apiUrl=$GLOBALS['newapi_url']."message/login";
				$curl = new curl\Curl();
				$curl->setRequestBody(json_encode($params));
				$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
				$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
				$curl->setOption(CURLOPT_TIMEOUT, 180);
				$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			    $response = $curl->post($apiUrl);
			    if(!$response){
			    	Yii::$app->session->setFlash('error', "Can't establish a connection to the referral network!");
			    }else{
			    	$decode=Json::decode($response);
				    $res=$decode['success'];
					if($res == 'true'){
						$token=$decode['token'];
						$userid=$decode['userid'];
						$pstcid=$decode['pstcid'];
						$this->Settoken($token,$userid,$pstcid);
						 Yii::$app->session->setFlash('success', 'Successfully logged in!');
					} else{
						 Yii::$app->session->setFlash('error', 'Login failed!');
					}
			    }
				return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
			
		}else{
			return $this->render('login', [
			'model' => $model
			]);
		}	
	} 
	
	public function actionSetmessage(){
		if(isset($_SESSION['usertoken'])){
			$token=$_SESSION['usertoken'];
			$userid= $_SESSION['userid'];
			$pstcid= $_SESSION['pstcid'];
			
			$my_var = \Yii::$app->request->post();
		
			//get profile
			$authorization = "Authorization: Bearer ".$token; 
			$apiUrl=$this->source.'setmessage';
			$params = [
				'sender_userid' => $my_var['sender_userid'],
				'message' => $my_var['message'],
				'type' => $my_var['type'],
				'id' => $my_var['id'],
				'dataxtype'=> $my_var['dataxtype'],
			];
			$curl = new curl\Curl();
			$curl->setRequestBody(json_encode($params));
			$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curl->setOption(CURLOPT_TIMEOUT, 180);
			$list = $curl->post($apiUrl);
			return $list;
			//$decode=Json::decode($list); 
		}else{
			$model = new LoginForm();
			return $this->render('login', [
			'model' => $model
			]);
		}
		
	}
	public function actionProfile(){
		if(isset($_SESSION['usertoken'])){
			
			$token=$_SESSION['usertoken'];
			$userid= $_SESSION['userid'];
			$pstcid= $_SESSION['pstcid'];
			
			$my_var = \Yii::$app->request->post();
		
			//get profile
			$authorization = "Authorization: Bearer ".$token; 
			$apiUrl=$this->source.'profile';
			$params = [
				'id' => $my_var['id']
			];
			$curl = new curl\Curl();
			$curl->setRequestBody(json_encode($params));
			$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curl->setOption(CURLOPT_TIMEOUT, 180);
			$list = $curl->post($apiUrl);
			//$decode=Json::decode($list); 
			return $list;
		}else{
			$model = new LoginForm();
			return $this->render('login', [
			'model' => $model
			]);
		}
		
	}
	
	public function actionGetcontact(){
		if(isset($_SESSION['usertoken'])){
			
			$token=$_SESSION['usertoken'];
			$userid= $_SESSION['userid'];
			
			$my_var = \Yii::$app->request->post();
			
			$authorization = "Authorization: Bearer ".$token; 
			$apiUrl=$this->source.'getcontact';
			$params = [
				'userid' => $my_var['userid'],
				'recipientid' => $my_var['recipientid'],
				'type' => $my_var['type']
			];
			$curl = new curl\Curl();
			$curl->setRequestBody(json_encode($params));
			$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curl->setOption(CURLOPT_TIMEOUT, 180);
			$list = $curl->post($apiUrl);
		
			return $list;
		}	
		else{
			$model = new LoginForm();
			return $this->render('login', [
			'model' => $model
			]);
		}
	}	
	
	public function actionGetcountunread(){
		if(isset($_SESSION['usertoken'])){
			

			$userid= $_SESSION['userid'];
			$token= $_SESSION['usertoken'];
			$my_var = \Yii::$app->request->post();
			
			$authorization = "Authorization: Bearer ".$token; 
			$apiUrl=$this->source.'countunread?userid='.$userid;
			$params = [
				'userid' => $userid
			];
			$curl = new curl\Curl();
			$curl->setRequestBody(json_encode($params));
			$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curl->setOption(CURLOPT_TIMEOUT, 180);
			$list = $curl->post($apiUrl);
		
			return $list;
		}	
		else{
			$model = new LoginForm();
			return $this->render('login', [
			'model' => $model
			]);
		}
	}
	
	public function actionReadmessage(){
		if(isset($_SESSION['usertoken'])){
			

			$userid= $_SESSION['userid'];
			$token= $_SESSION['usertoken'];
			$my_var = \Yii::$app->request->post();
			$id=$my_var['id'];
			
			$authorization = "Authorization: Bearer ".$token; 
			$apiUrl=$this->source.'readmessage?userid='.$id;
			/*$params = [
				'userid' => $my_var['recipientid']
			]; */
			$curl = new curl\Curl();
			//$curl->setRequestBody(json_encode($params));
			$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curl->setOption(CURLOPT_TIMEOUT, 180);
			$list = $curl->post($apiUrl);
		
			return $list;
		}	
		else{
			$model = new LoginForm();
			return $this->render('login', [
			'model' => $model
			]);
		}
	}
	
	public function actionSearchcontact()
    {
		if(isset($_SESSION['usertoken'])){
			

			$userid= $_SESSION['userid'];
			$token= $_SESSION['usertoken'];
			$my_var = \Yii::$app->request->post();
			$txtsearch=$my_var['txtsearch'];
			
			$authorization = "Authorization: Bearer ".$token; 
			$apiUrl=$this->source.'searchcontact?txtsearch='.$txtsearch;
			/*$params = [
				'userid' => $my_var['recipientid']
			]; */
			$curl = new curl\Curl();
			//$curl->setRequestBody(json_encode($params));
			$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curl->setOption(CURLOPT_TIMEOUT, 180);
			$list = $curl->post($apiUrl);
		
			return $list;
		}	
		else{
			$model = new LoginForm();
			return $this->render('login', [
			'model' => $model
			]);
		}
	}
	
}
