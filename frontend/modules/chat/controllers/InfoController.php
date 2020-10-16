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

/**
 * InfoController implements the CRUD actions for Chat model.
 */
class InfoController extends Controller
{
	public $source = 'https://eulims.onelab.dost.gov.ph/api/message/';
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
			
			/*$notif= new Notification();
			
			$res=$notif->sendSMS("", "wis", "09171044790", "title", "Hello World", "eULIMS", $this->module->id,$this->action->id);
			echo($this->module->id);
			echo($this->action->id);
			
			exit; */
			
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
	
	public function Settoken($token,$userid)
    {
		$session = Yii::$app->session;
		
		$session->set('usertoken', $token);
		$session->set('userid', $userid);
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
			$userid= Yii::$app->user->identity->profile->user_id;
			//get profile
			$authorization = "Authorization: Bearer ".$token; 
			$apiUrl=$this->source.'/possiblerecipients?userid='.$userid;
			$curl = new curl\Curl();
			$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curl->setOption(CURLOPT_TIMEOUT, 180);
			$list = $curl->get($apiUrl);
			$decode=Json::decode($list);
			
		    //var_dump($list);
			//exit;
			
			
		//	$dataProvider = New ActiveDataProvider(['query'=>$list]);
		
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
			$model = new LoginForm();
			if ($model->load(Yii::$app->request->post())){
			}else{
				return $this->render('login', [
				'model' => $model
				]);
			}	
		}
        
    }
	
	public function actionLogin(){
		$model = new LoginForm();
		if ($model->load(Yii::$app->request->post())){
			$params = [ 
			'email' => $model->email,
            'password' => $model->password
			];

			$apiUrl=$GLOBALS['api_url'].'message/login';
            $curl = new curl\Curl();
			$curl->setRequestBody(json_encode($params));
            $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $response = $curl->post($apiUrl);
			$decoderes=Json::decode($response);
			
			if($decoderes['success'] <> 0){ //false
				$token=$decoderes['token'];
				$userid=$decoderes['userid'];

				$session = \Yii::$app->session;
		
				//$session->set('usertoken', $token);
				//$session->set('userid', $userid);
				
			}
			else{
				
			}
		}else{
			return $this->render('login', [
			'model' => $model
			]);
		}	
	}
}
