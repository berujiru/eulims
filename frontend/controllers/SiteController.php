<?php
namespace frontend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\system\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use common\components\SwiftMessage;
use common\models\system\User;
use common\models\lab\Sample;
use dosamigos\google\places\Search;
use common\models\auth\AuthAssignment;
use common\models\system\Profile;
use yii\db\Query;
use common\models\inventory\Products;
use common\models\inventory\Categorytype;
use common\models\inventory\Producttype;
use yii\data\ActiveDataProvider;
use yii\data\SqlDataProvider;
use DateTime;
use common\models\lab\Loginlogs;
use common\models\lab\Request;
use common\models\lab\RequestSearch;
use console\controllers\SchedsyncController;
use yii\base\Model;
use mysqli;
//use yii\swiftmailer\Message;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup','requestpasswordreset'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
           /* 'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],*/
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
            ],
        
        ];
    }



    public function actionQuery(){
        $inventorydb= Yii::$app->get('inventorydb');
        /*$query=new Query();
        $query->select(['`product_code`, `product_name`, `tbl_producttype`.`producttype`,`tbl_categorytype`.`categorytype`'])
                ->from('`tbl_products`')
                ->innerJoin('`tbl_producttype`', '`tbl_producttype`.`producttype_id`=`tbl_products`.`producttype_id`')
                ->innerJoin('`tbl_categorytype`', '`tbl_categorytype`.`categorytype_id`=`tbl_products`.`categorytype_id`')
                ->where(['`tbl_products`.`product_id`'=>2]);
        $command=$query->createCommand($inventorydb);
        $products=$command->queryAll();
         * 
         */
        $products= Products::find()
                ->innerJoin('`tbl_producttype`', '`tbl_producttype`.`producttype_id`=`tbl_products`.`producttype_id`')
                ->innerJoin('`tbl_categorytype`', '`tbl_categorytype`.`categorytype_id`=`tbl_products`.`categorytype_id`')
                ->where(['`tbl_products`.`product_id`'=>1])
                ->all();
        echo "<pre>";
        print_r($products);
        echo "</pre>";
    }


    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex(){

       
        //return json_encode($result);
        
        global $tmpLabId;
        /*$Payment_details=[];
        $Payment_detail=[
            'request_ref_num'=>"R9-072018-MET-0230",
            'rrn_date_time'=>"2018-10-09 10:25:12",
            'amount'=>3200
        ];
        array_push($Payment_details, $Payment_detail);
        
        $TransactDetails=[
            'transaction_num'=>"R9-072018-MET-0230",
            'customer_code'=>"11-011",
            'email'=>"dostonelab@gmail.com",
            'collection_type'=>"Testing",
            'collection_code'=>'03-200',
            'order_date'=>"2018-10-09",
            'agency_code'=>"R9",
            'total_amount'=>3200.00,
            'payment_details'=>$Payment_details
        ];
        Yii::$app->response->format= yii\web\Response::FORMAT_JSON;
        return $TransactDetails;
        exit;
         * 
         */
     //   $dashDetails = new DashboardDetails();
   //     $sqlQueryString = 'Call eulims_lab.spGetDashboardDetails('. 0 .','. 11 . ',' . 0 .',' . 0 .');'; 
   //     $mysqli = new mysqli('localhost', 'eulims', 'eulims', 'eulims_lab');
   //     $sql = $sqlQueryString; 
   //     $res = $mysqli->query($sql);
             //   $dashDetails = $res->fetch_all(MYSQLI_ASSOC);
       
     //    $dashDetails = Yii::$app->db->createCommand('Call eulims_lab.spGetDashboardDetails('. 0 .','. 11 . ',' . 0 .',' . 0 .');')->queryAll();

     //    foreach ($dashDetails as $dash) {
           
      //      array_push($listLab,$dash->labname);
       //  }
      //  $listLab='';
        $now = new DateTime();

        $currentyear= $now->format('Y');
        $currentyear = 2018;
        $curYearValue =0;
        $strcurrentyear = (string)$currentyear;
        $dashArray = Yii::$app->db->createCommand('Call eulims_lab.spGetDashboardDetails('. 0 .','. 11 . ',' . 0 .',' . 0 .');')->queryAll();
        
        $listLab=array();
        $listLabCount=array();
        $listLabColor=array();
        $listYear=array();
        $listLabCode=array();
        $datatotal=array();
        
        $mainLaboratoryList=array();
        
        
      //  $dataTop10 =array();
        
       // $columnArray[] = '';// 'natureofcollection';
        foreach ($dashArray as $col)
            {
               // $columnArray[]=$col['labname'];
                array_push($listLab,$col['labname']);
                array_push($listLabCount,$col['countperlab']);
                array_push($listLabColor,$col['labcolor']);
                 array_push($listLabCode,$col['labcode']);
                
                $mainLaboratoryRec=array();
                $mainLaboratoryRec['labid']=$col['lab_id'];
                $mainLaboratoryRec['labname']=$col['labname'];
                $mainLaboratoryRec['labcolor']=$col['labcolor'];
                $mainLaboratoryRec['labcode']=$col['labcode'];
                $mainLaboratoryRec['labicon']=$col['labicon'];
                array_push($mainLaboratoryList,$mainLaboratoryRec);
                 
            }
            
         
        $arrayYear = Yii::$app->db->createCommand('Call eulims_lab.spGetDashboardDetails('. 2 .','. 11 . ',' . 0 .',' . 0 .');')->queryAll();
        $index = 0;
        foreach ($arrayYear as $colYear)
            {
               // $columnArray[]=$col['labname'];
                array_push($listYear,$colYear['iyear']);
                if($currentyear==$colYear['iyear'])
                {
                    $curYearValue=$index;
                }
                $index++;
        
            }
       // $dashDetails = Yii::$app->db->createCommand($sqlQueryString);
        
        
        // $dataTop10 = Yii::$app->db->createCommand('Call eulims_lab.spGetDashboardDetails('. 1 .','. 11 . ',' . 2018 .',' . 1 .');')->queryAll();
        
        
        
   //     $listLab = array("Chemical", "Microbiology", "Metrology", "Rubber", "Test Lab", "Test Lab2", "Test Lab3","New Members","New Referrals");
     //   $listLabCode = array("Chemical", "Microbiology", "Metrology", "Rubber", "TestLab", "TestLab2", "TestLab3","","");
     //   $listLabCount = array(400, 500, 600, 700, 800, 900, 1000,6,8);
     //   $listLabColor = array("red", "green", "blue", "orange", "aqua", "purple", "orange","gray","gray");
        $listLabIcons = array("fa fa-flask", "fa fa-flask", "fa fa-flask", "fa fa-comments-o", "fa fa-bookmark-o", "fa fa-comments-o", "fa fa-bookmark-o","fa fa-bookmark-o","fa fa-bookmark-o");
   //     $listYear = array("2015","2016","2017");
       
        $listColumn = array("Rank", "Test Name", "No. of Tests");
        
        $dataGraphCalibration=array();
        
       $arrayGraphMain=array();
       
       $arrayGraphPieQuery = Yii::$app->db->createCommand('Call eulims_lab.spGetChartDashboardDetails('. 4 .','. 11 . ',' . $currentyear .',' . 2 .');')->queryAll();
       $arrayGraphPieMain=array();
       foreach ($arrayGraphPieQuery as $rows)
            {
                $arrayGraphPieRec=array();
                $arrayGraphPieRec['name']= $rows['labname'];
                $arrayGraphPieRec['y']= (int)$rows['labtotal'];
                $arrayGraphPieRec['slice']= 'true';
                $arrayGraphPieRec['color']= $rows['labcolor'];
                array_push($arrayGraphPieMain,$arrayGraphPieRec);
            }
            
       
        foreach ($listYear as $colYear)
        {
       
            $arrayGraph = Yii::$app->db->createCommand('Call eulims_lab.spGetChartDashboardDetails('. 0 .','. 11 . ',' . $colYear .',' . 2 .');')->queryAll();
            
      
            foreach ($arrayGraph as $rows)
            {
                $arrayGraphRec=array();
                $arrayGraphRec['labid']= $rows['labid'];
                $arrayGraphRec['labtotal']=(int) $rows['labtotal'];
                $arrayGraphRec['year']= $rows['year'];
                $arrayGraphRec['labname']= $rows['labname'];
                $arrayGraphRec['labcolor']= $rows['labcolor'];
                array_push($arrayGraphMain,$arrayGraphRec);
            }
            
            
            
           
        }
        
        $index = 0;
        
         $dataGraphCalibrationTmpList=array();
        foreach ($mainLaboratoryList as $colMain)
            {
            $dataGraphCalibrationTmpRec=array();
                $dataGraphCalibrationTmpRec['type']='column';
                $dataGraphCalibrationTmpRec['name']=$colMain['labname'];
             
              
               
                $NUM =$colMain['labid'];;
               
                $filtered = array_filter($arrayGraphMain, function($item) use($NUM){
                    return $item['labid'] == $NUM;
                });
          
                   
                $datatotal =array_column($filtered, 'labtotal');
                $dataGraphCalibrationTmpRec['data']=$datatotal;
                $dataGraphCalibrationTmpRec['color']=$colMain['labcolor'];
                
                array_push($dataGraphCalibrationTmpList,$dataGraphCalibrationTmpRec);
               
             //  array_push($dataGraphCalibration,$dataGraphCalibrationTmp);
                
        
            }
            
            
            
    
        
        $dataGraphCalibration=$dataGraphCalibrationTmpList;
        
            //remove this
   //    $dataGraphCalibration = array(
    //      array('type'=>'column', 'name' => 'Chemical', 'data' => array(50, 60, 70), 'color' => 'red'),
    //       array('type'=>'column','name' => 'Microbiology', 'data' => array(150, 120, 140), 'color' => 'green'),
    //       array('type'=>'column','name' => 'Metrology', 'data' => array(200, 180, 210), 'color' => 'blue'),
    //      array('type'=>'column','name' => 'Rubber', 'data' => array(250, 240, 280), 'color' => 'orange'),
   //        array('type'=>'column','name' => 'TestLab', 'data' => array(300, 300, 350), 'color' => 'aqua')
   //  );
        
        $dataGraphPie2017 = array(
                array('name' => 'Chemical', 'y' => 20, 'sliced' => 'true','color'=>'red'),
                array('name' => 'Microbiology', 'y' => 50, 'sliced' => 'true','color'=>'green'),
                array('name' => 'Metrology', 'y' => 15, 'sliced' => 'true','color'=>'blue'),
                array('name' => 'Rubber', 'y' => 15, 'sliced' => 'true','color'=>'orange'),
            );
        
       
     
        
        $dataTop10 = new SqlDataProvider([
            'sql' => 'Call eulims_lab.spGetDashboardDetails(1,11,' . $strcurrentyear . ',1);',
            
            'totalCount' => 10,
           'pagination' => [
                'pageSize' => 0
            ],
           
        ]);

       
        
       //  print_r($dataGraphCalibrationTmpList);
        // exit;
         
        $datainitial = array();
        $datainitial['listlab']=$listLab;
        $datainitial['listlabcode']=$listLabCode;
        $datainitial['listlabcount']=$listLabCount;
        $datainitial['listlabcolor']=$listLabColor;
        $datainitial['listlabicons']=$listLabIcons;
        $datainitial['listyear']=$listYear;
        $datainitial['listcolumn']=$listColumn;
        $datainitial['datatop10']=$dataTop10;
        
        $datainitial['column'] = $dataGraphCalibration;
        $datainitial['pie'] = $arrayGraphPieMain;//$dataGraphPie2017; 
        $datainitial['arrmain'] =$datatotal;// $arrayGraphMain; 
        


        $dataProvider = $this->actionTopsample();

        return $this->render('index', array('data'=>$datainitial,'curYearValue'=>$curYearValue,'dataProvider' => $dataProvider));
     
     //   return $this->render('index');
    }

    public function actionTrack()
    {
        if (!Yii::$app->user->isGuest) {
            $model = new Request();
        $post= Yii::$app->request->post();
        if ($model->load(Yii::$app->request->post())) {

            // $searchModel = new RequestSearch();
            // $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
           
            $req = $_POST['Request']['request_ref_num'];
            $created = $_POST['Request']['created_at'];

            $request = Request::find()->where(['request_ref_num' => $req, 'created_at'=>$created])->one();     

            if ($request){
                return $this->render('viewtrack', [
                    'model' => $model,
                    // 'searchModel' => $searchModel,
                    // 'dataProvider' => $dataProvider,
                    'request'=>$request,
                ]);
            }else{
                return $this->redirect(['track']);
            }
           
        }


        return $this->render('createtrack', [
            'model' => $model,
        ]);
        }

        $model = new Request();
        $post= Yii::$app->request->post();
        if ($model->load(Yii::$app->request->post())) {

            // $searchModel = new RequestSearch();
            // $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
           
            $req = $_POST['Request']['request_ref_num'];
            $created = $_POST['Request']['created_at'];

            $request = Request::find()->where(['request_ref_num' => $req, 'created_at'=>$created])->one();     

            if ($request){
                return $this->render('viewtrack', [
                    'model' => $model,
                    // 'searchModel' => $searchModel,
                    // 'dataProvider' => $dataProvider,
                    'request'=>$request,
                ]);
            }else{
                return $this->redirect(['track']);
            }
           
        }


        return $this->render('createtrack', [
            'model' => $model,
        ]);
       
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {

            $loginlogs = new Loginlogs();
            $loginlogs->user_id = (int) Yii::$app->user->identity->profile->user_id;
            $loginlogs->rstl_id = (int) Yii::$app->user->identity->profile->rstl_id;
            $loginlogs->login_date = date('Y-m-d H:i:s');


            if($loginlogs->save()){
                return $this->goBack();
            } else {
                Yii::$app->user->logout();
            }
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }
    
     public function actionUserdashboard()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {

            $loginlogs = new Loginlogs();
            $loginlogs->user_id = (int) Yii::$app->user->identity->profile->user_id;
            $loginlogs->rstl_id = (int) Yii::$app->user->identity->profile->rstl_id;
            $loginlogs->login_date = date('Y-m-d H:i:s');

          
            $user = User::find()->Where(['user_id'=>Yii::$app->user->identity->profile->user_id])->one();
  

            if ($user) {
                $user_data = array(
                'username' => $user->username,
                'auth_key' => $user->auth_key,
                'password_hash'=>$user->password_hash,
                'email'=>$user->email,
                'user_id'=>Yii::$app->user->identity->profile->user_id,
                'lastname'=>Yii::$app->user->identity->profile->lastname,
                'firstname'=>Yii::$app->user->identity->profile->firstname,
                'designation'=>Yii::$app->user->identity->profile->designation,
                'middleinitial'=>Yii::$app->user->identity->profile->middleinitial,
                'rstl_id'=>Yii::$app->user->identity->profile->rstl_id,
                'lab_id'=>Yii::$app->user->identity->profile->lab_id,
                'contact_numbers'=>Yii::$app->user->identity->profile->contact_numbers,
              );
                $curl = curl_init();
      
                curl_setopt($curl, CURLOPT_POST, 1);
            
                curl_setopt($curl, CURLOPT_URL, 'https://eulims.onelab.ph/api/sync/passlogin');

                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_PROXY, ''); //!! FIX
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $user_data);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($curl);
                curl_close($curl);

               // $consoleController = new SchedsyncController ('schedsync', Yii::$app); 
              //  $controller = new SchedsyncController(Yii::$app->controller->id, Yii::$app);
              //  $controller->actionCroncustomer();
              //  $consoleController->runAction('croncustomer');
            
            } 

            if($loginlogs->save()){
                return $this->goBack();
            } else {
                Yii::$app->user->logout();
            }
        } else {
            return $this->render('..\admin-lte\site\userdashboard.php', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about'); 
    }
    public function actionSendmail(){
        $mailer= \Yii::$app->mailer;
        $mailer->compose('sampleEmail-html', ['message' => 'Nolan'])
                ->setFrom('dostonelab@gmail.com')
                ->setTo('dostonelab@gmail.com')
                ->setBcc('nolan@tailormadetraffic.com')
                ->attach('d:/attachment.png')
                ->attach('d:/Sample.txt')
                ->setReplyTo('admin@eulims.com')
                ->setSubject('This message was sent to you by: ' . \Yii::$app->name)
                ->send();
    }
    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user=$model->signup()) {
                //Send email Verification
                $model->SendEmail($user);
                return $this->runAction('success', ['model' => $user]);
            }
        }
        if (Yii::$app->request->isAjax)
        {
            return $this->renderAjax('signup', [
            'model' => $model,
        ]);
        }
        else
        {
            return $this->render('signup', [
            'model' => $model,
        ]);
        }
        
        
    }
    public function actionSuccess($model){
        return $this->render('success', [
            'model' => $model,
        ]);
    }
    public function actionVerify(){
        $get= Yii::$app->request->get();
        $user= User::find()->where("md5(user_id)='".$get['id']."'")->one();
        if ($user) {
            $mUser= User::findOne(['user_id'=>$user->user_id]);
            //Update User
            $mUser->status = 10;
            $mUser->save();
            //Assign default Role  basic-role
            $BasicRole = 'basic-role';
            //search existing assignment
            $AAssignment= AuthAssignment::find()->where(['item_name'=>$BasicRole]);
            if (!$AAssignment) {
                $AuthAssignment = new AuthAssignment();
                $AuthAssignment->item_name = $BasicRole;
                $AuthAssignment->user_id = (string) $user->user_id;
                $AuthAssignment->save();
            }
            return $this->render('verify');
        }else{
            return $this->render('expired');
        }
        
    }
    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestpasswordreset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
            }
        }
        
        if (Yii::$app->request->isAjax)
        {
            return $this->renderAjax('requestPasswordResetToken', [
            'model' => $model,
        ]);
     
        }
        else
        {
        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
        }
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }
    
    
    public function actionDatatop()
    {
    //    $dataTop10 = new SqlDataProvider([
     //       'sql' => 'Call eulims_lab.spGetDashboardDetails(1,11,2017,1);',
            
     //       'totalCount' => 10,
     //      'pagination' => [
      //          'pageSize' => 0
      //      ],
           
      //  ]);
        
       //  return $dataTop10;//$this->renderAjax('dataTop10' => $dataTop10);
     //      \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
      //      return $dataTop10;
        
        $paramYear ='';
        $datainitial = array();
        $iLab = (int) Yii::$app->request->get('slab') + 1;
        
        if (Yii::$app->request->get('syear')>0 && $iLab>0){
        $paramYear = Yii::$app->request->get('syear');
        $paramLab =(string) $iLab;
           //test_id = $model->test_id;
        }
        
      
       
            
       if (Yii::$app->request->isAjax) {
           
            $dataTop10 = new SqlDataProvider([
            'sql' => 'Call eulims_lab.spGetDashboardDetails(1,11,' . $paramYear . ',' .$iLab .');',
            
            'totalCount' => 10,
           'pagination' => [
                'pageSize' => 0
            ],
           
        ]);
            
             $datainitial['datatop10']=$dataTop10;
             
         return $this->renderAjax('_gridtop10', [
             'dataTop10' => $dataTop10
            // 'dataTop10'=>$datainitial['datatop10']
                 
              //  'model' => $model,
          ]);
          
        }
        
    }
    
    public function actionGraphdata()
    {
         $dashArray = Yii::$app->db->createCommand('Call eulims_lab.spGetDashboardDetails('. 0 .','. 11 . ',' . 0 .',' . 0 .');')->queryAll();
        
        $listLab=array();
        $listLabCount=array();
        $listLabColor=array();
        $listYear=array();
        $listLabCode=array();

        
        $mainLaboratoryList=array();
            
        foreach ($dashArray as $col)
            {
               // $columnArray[]=$col['labname'];
                array_push($listLab,$col['labname']);
                array_push($listLabCount,$col['countperlab']);
                array_push($listLabColor,$col['labcolor']);
                 array_push($listLabCode,$col['labcode']);
                
                $mainLaboratoryRec=array();
                $mainLaboratoryRec['labid']=$col['lab_id'];
                $mainLaboratoryRec['labname']=$col['labname'];
                $mainLaboratoryRec['labcolor']=$col['labcolor'];
                $mainLaboratoryRec['labcode']=$col['labcode'];
                $mainLaboratoryRec['labicon']=$col['labicon'];
                array_push($mainLaboratoryList,$mainLaboratoryRec);
                 
            }
        
       // $listYear=array(2013,2014,2015,2016,2017,2018);
       
            
        if (Yii::$app->request->isAjax)
        {
            
       
        
        $arrayYear = Yii::$app->db->createCommand('Call eulims_lab.spGetDashboardDetails('. 2 .','. 11 . ',' . 0 .',' . 0 .');')->queryAll();
        $index = 0;
        foreach ($arrayYear as $colYear)
            {
               // $columnArray[]=$col['labname'];
                array_push($listYear,$colYear['iyear']);
                
                $index++;
        
            }
        
        $arrayGraphMain=array();
        foreach ($listYear as $colYear)
        {
       
            $arrayGraph = Yii::$app->db->createCommand('Call eulims_lab.spGetChartDashboardDetails('. 1 .','. 11 . ',' . $colYear .',' . 0 .');')->queryAll();
            
      
            foreach ($arrayGraph as $rows)
            {
                $arrayGraphRec=array();
                $arrayGraphRec['labid']= $rows['labid'];
                $arrayGraphRec['labtotal']=(int) $rows['labtotal'];
                $arrayGraphRec['year']= $rows['year'];
                $arrayGraphRec['labname']= $rows['labname'];
                $arrayGraphRec['labcolor']= $rows['labcolor'];
                array_push($arrayGraphMain,$arrayGraphRec);
            }
           
        }
        
        $index = 0;
        //$filteredNew=array();
         $dataGraphCalibrationTmpList=array();
        foreach ($mainLaboratoryList as $colMain)
            {
            $dataGraphCalibrationTmpRec=array();
                $dataGraphCalibrationTmpRec['type']='column';
                $dataGraphCalibrationTmpRec['name']=$colMain['labname'];
             
              
               
                $NUM =$colMain['labid'];
                $filtered =array();
                $filtered = array_filter($arrayGraphMain, function($item) use($NUM){
                   return $item['labid'] == $NUM;
                });
          
                   
                $datatotal =array_column($filtered, 'labtotal');
                $dataGraphCalibrationTmpRec['data']=$datatotal;
               $dataGraphCalibrationTmpRec['color']=$colMain['labcolor'];
               
             array_push($dataGraphCalibrationTmpList,$dataGraphCalibrationTmpRec);
               
            
                
        
            }
           
          
            
      //   print_r($dataGraphCalibrationTmpList);
      //    exit;
             
         return $this->renderPartial('_graphpage', [
             'column' => $dataGraphCalibrationTmpList,'listYear'=>$listYear,'currentyear'=>$currentyear
            // 'dataTop10'=>$datainitial['datatop10']
                 
              //  'model' => $model,
          ]);
          
        }
    }
    
    public function actionRetrievechart2()
        {
        
         $pYear = Yii::$app->request->post('yearParam');
         $dataGraphPie="";
         
                  
            $dataGraphPie2015 = array(
            array('name' => 'Chemical', 'y' => 40, 'sliced' => 'true','color'=>'red'),
            array('name' => 'Microbiology', 'y' => 30, 'sliced' => 'true','color'=>'green'),
            array('name' => 'Metrology', 'y' => 20, 'sliced' => 'true','color'=>'blue'),
            array('name' => 'Rubber', 'y' => 10, 'sliced' => 'true','color'=>'orange'),
            );

            $dataGraphPie2016 = array(
                array('name' => 'Chemical', 'y' => 10, 'sliced' => 'true','color'=>'red'),
                array('name' => 'Microbiology', 'y' => 10, 'sliced' => 'true','color'=>'green'),
                array('name' => 'Metrology', 'y' => 10, 'sliced' => 'true','color'=>'blue'),
                array('name' => 'Rubber', 'y' => 70, 'sliced' => 'true','color'=>'orange'),
            );

            $dataGraphPie2017 = array(
                array('name' => 'Chemical', 'y' => 20, 'sliced' => 'true','color'=>'red'),
                array('name' => 'Microbiology', 'y' => 50, 'sliced' => 'true','color'=>'green'),
                array('name' => 'Metrology', 'y' => 15, 'sliced' => 'true','color'=>'blue'),
                array('name' => 'Rubber', 'y' => 15, 'sliced' => 'true','color'=>'orange'),
            );
      
        switch ($pYear) {
        case '2015':
           $dataGraphPie = $dataGraphPie2015;
            break;
        case '2016':
            $dataGraphPie = $dataGraphPie2016;
            break;
        case '2017':
           $dataGraphPie = $dataGraphPie2017;
            break;
        
        default:
           break;
        } 
           
            
           return print_r($dataGraphPie); 
           exit;
           
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $dataGraphPie;
            //return \yii\helpers\Json::encode($dataGraphPie2017);
           // return json_encode($dataGraphPie2017, JSON_NUMERIC_CHECK);
        }
        
       public function actionRetrievechart()
        {
        
         $pYear = Yii::$app->request->post('yearParam');
         $dataGraphPie="";
         
       
            
          
        
        $arrayGraphMain=array();
      
       
            $arrayGraph = Yii::$app->db->createCommand('Call eulims_lab.spGetChartDashboardDetails('. 4 .','. 11 . ',' . $pYear .',' . 0 .');')->queryAll();
            
      // array('name' => 'Rubber', 'y' => 70, 'sliced' => 'true','color'=>'orange')
               
            foreach ($arrayGraph as $rows)
            {
                $arrayGraphRec=array();
                $arrayGraphRec['name']= $rows['labname'];
                $arrayGraphRec['y']= (int)$rows['labtotal'];
          //      $arrayGraphRec['labid']= $rows['labid'];
                $arrayGraphRec['slice']= 'true';
              //  $arrayGraphRec['year']= $rows['year'];
                
                $arrayGraphRec['color']= $rows['labcolor'];
                array_push($arrayGraphMain,$arrayGraphRec);
            }
           
      
        
    
            
         $dataGraphPie=$arrayGraphMain;
                  
         // return print_r($dataGraphPie);  exit;
          
            if (Yii::$app->request->isAjax)
             {
                 \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $dataGraphPie;
            }
        
            
           
           
            //return \yii\helpers\Json::encode($dataGraphPie2017);
           // return json_encode($dataGraphPie2017, JSON_NUMERIC_CHECK);
        }
        
        public function actionRetrievecolumn2()
        {
        
         $cType = Yii::$app->request->post('columnType');
         $dataGraphColumn="";
         
                  
        $dataGraphIncome = array(
            array('type'=>'column', 'name' => 'Chemical', 'data' => array(194, 277, 81), 'color' => 'red'),
            array('type'=>'column', 'name' => 'Microbiology', 'data' => array(237, 334, 89), 'color' => 'green'),
            array('type'=>'column', 'name' => 'Metrology', 'data' => array(748, 270, 109), 'color' => 'blue'),
            array('type'=>'column', 'name' => 'Rubber', 'data' => array(100, 200, 300), 'color' => 'orange'),
            array('type'=>'column','name' => 'TestLab', 'data' => array(450, 200, 360), 'color' => 'aqua')
              );

        $dataGraphFirms = array(
            array('type'=>'column', 'name' => 'Chemical', 'data' => array(294, 377, 181), 'color' => 'red'),
            array('type'=>'column','name' => 'Microbiology', 'data' => array(137, 234, 189), 'color' => 'green'),
            array('type'=>'column','name' => 'Metrology', 'data' => array(648, 370, 209), 'color' => 'blue'),
            array('type'=>'column','name' => 'Rubber', 'data' => array(200, 400, 600), 'color' => 'orange'),
            array('type'=>'column','name' => 'TestLab', 'data' => array(450, 200, 360), 'color' => 'aqua')
        );
        
        $dataGraphAssistance = array(
            array('type'=>'column', 'name' => 'Chemical', 'data' => array(80, 90, 20), 'color' => 'red'),
            array('type'=>'column','name' => 'Microbiology', 'data' => array(160, 180, 40), 'color' => 'green'),
            array('type'=>'column','name' => 'Metrology', 'data' => array(240, 270, 60), 'color' => 'blue'),
            array('type'=>'column','name' => 'Rubber', 'data' => array(300, 360, 80), 'color' => 'orange'),
            array('type'=>'column','name' => 'TestLab', 'data' => array(360, 450, 100), 'color' => 'aqua')
        );
      
        
        $dataGraphCalibration = array(
            array('type'=>'column', 'name' => 'Chemical', 'data' => array(50, 60, 70), 'color' => 'red'),
            array('type'=>'column','name' => 'Microbiology', 'data' => array(150, 120, 140), 'color' => 'green'),
            array('type'=>'column','name' => 'Metrology', 'data' => array(200, 180, 210), 'color' => 'blue'),
            array('type'=>'column','name' => 'Rubber', 'data' => array(250, 240, 280), 'color' => 'orange'),
            array('type'=>'column','name' => 'TestLab', 'data' => array(300, 300, 350), 'color' => 'aqua')
        );
      
        $dataGraphCustomer = array(
            array('type'=>'column', 'name' => 'Chemical', 'data' => array(10, 100, 200), 'color' => 'red'),
            array('type'=>'column','name' => 'Microbiology', 'data' => array(20, 200, 210), 'color' => 'green'),
            array('type'=>'column','name' => 'Metrology', 'data' => array(30, 300, 220), 'color' => 'blue'),
            array('type'=>'column','name' => 'Rubber', 'data' => array(40, 400, 230), 'color' => 'orange'),
            array('type'=>'column','name' => 'TestLab', 'data' => array(50, 500, 240), 'color' => 'aqua')
        );
        
        switch ($cType) {
        case 'firms':
           $dataGraphColumn = $dataGraphFirms;
            break;
        case 'income':
            $dataGraphColumn = $dataGraphIncome;
            break;
        case 'assistance':
           $dataGraphColumn = $dataGraphAssistance;
            break;
        case 'calibration':
           $dataGraphColumn = $dataGraphCalibration;
            break;
        case 'customer':
           $dataGraphColumn = $dataGraphCustomer;
            break;
        
        default:
           break;
        } 
        
        
            
            
            
    
        
      //  $dataGraphCalibration=$dataGraphCalibrationTmpList;
      
    // $dataGraphColumn=$dataGraphCalibrationTmpList;
       
           
       //    return  print_r($dataGraphCalibration);exit;
           
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $dataGraphColumn;
        //   return \yii\helpers\Json::encode($dataGraphIncome);
           // return json_encode($dataGraphPie2017, JSON_NUMERIC_CHECK);
            
        }
        
        public function actionRetrievecolumn()
    {
         $cType = Yii::$app->request->post('columnType');
         $spChartMode = (int)Yii::$app->request->post('paramSP');
         
         $dataGraphColumn="";    
            
            
         $dashArray = Yii::$app->db->createCommand('Call eulims_lab.spGetDashboardDetails('. 0 .','. 11 . ',' . 0 .',' . 0 .');')->queryAll();
        
        $listLab=array();
        $listLabCount=array();
        $listLabColor=array();
        $listYear=array();
        $listLabCode=array();
        
        $mainLaboratoryList=array();
            
        foreach ($dashArray as $col)
            {
               // $columnArray[]=$col['labname'];
                array_push($listLab,$col['labname']);
                array_push($listLabCount,$col['countperlab']);
                array_push($listLabColor,$col['labcolor']);
                 array_push($listLabCode,$col['labcode']);
                
                $mainLaboratoryRec=array();
                $mainLaboratoryRec['labid']=$col['lab_id'];
                $mainLaboratoryRec['labname']=$col['labname'];
                $mainLaboratoryRec['labcolor']=$col['labcolor'];
                $mainLaboratoryRec['labcode']=$col['labcode'];
                $mainLaboratoryRec['labicon']=$col['labicon'];
                array_push($mainLaboratoryList,$mainLaboratoryRec);
                 
            }
            
            
            $arrayYear = Yii::$app->db->createCommand('Call eulims_lab.spGetDashboardDetails('. 2 .','. 11 . ',' . 0 .',' . 0 .');')->queryAll();
        $index = 0;
        foreach ($arrayYear as $colYear)
            {
               // $columnArray[]=$col['labname'];
                array_push($listYear,$colYear['iyear']);
                
                $index++;
        
            }
        
        $arrayGraphMain=array();
        foreach ($listYear as $colYear)
        {
       
            $arrayGraph = Yii::$app->db->createCommand('Call eulims_lab.spGetChartDashboardDetails('. $spChartMode .','. 11 . ',' . $colYear .',' . 0 .');')->queryAll();
            
      
            foreach ($arrayGraph as $rows)
            {
                $arrayGraphRec=array();
                $arrayGraphRec['labid']= $rows['labid'];
                $arrayGraphRec['labtotal']=(int) $rows['labtotal'];
                $arrayGraphRec['year']= $rows['year'];
                $arrayGraphRec['labname']= $rows['labname'];
                $arrayGraphRec['labcolor']= $rows['labcolor'];
                array_push($arrayGraphMain,$arrayGraphRec);
            }
           
        }
        
        $index = 0;
        //$filteredNew=array();
         $dataGraphCalibrationTmpList=array();
        foreach ($mainLaboratoryList as $colMain)
            {
            $dataGraphCalibrationTmpRec=array();
                $dataGraphCalibrationTmpRec['type']='column';
                $dataGraphCalibrationTmpRec['name']=$colMain['labname'];
             
              
               
                $NUM =$colMain['labid'];
                $filtered =array();
                $filtered = array_filter($arrayGraphMain, function($item) use($NUM){
                   return $item['labid'] == $NUM;
                });
          
                   
                $datatotal =array_column($filtered, 'labtotal');
                $dataGraphCalibrationTmpRec['data']=$datatotal;
               $dataGraphCalibrationTmpRec['color']=$colMain['labcolor'];
               
             array_push($dataGraphCalibrationTmpList,$dataGraphCalibrationTmpRec);
               
            
                
        
            }
        
       // $listYear=array(2013,2014,2015,2016,2017,2018);
       
            
        if (Yii::$app->request->isAjax)
        {
            
       
        
        
           
          
            
      //   print_r($dataGraphCalibrationTmpList);
      //    exit;
         //     return  print_r($dataGraphCalibrationTmpList);exit;
     //    return $this->renderPartial('_graphpage', [
       //      'column' => $dataGraphCalibrationTmpList,'listYear'=>$listYear,'test'=>'myTest'
            // 'dataTop10'=>$datainitial['datatop10']
                 
              //  'model' => $model,
      //    ]);
            
              \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $dataGraphCalibrationTmpList;
          
        }
    }
    
    public function actionTopsample(){
       
        if(!empty(\Yii::$app->request->get())){
            $post = \Yii::$app->request->get();
            $year = isset($post['year']);
            $lab = isset($post['lab']) + 1;
        }else{
            $year = date('Y');
            $lab = 1;
        }

        $modelRequest = Sample::find()
        ->select([
            'samplename',
            'package_rate' => 'count(sampletype_id)',
        ])
        ->innerJoin('tbl_request', 'tbl_sample.request_id = tbl_request.request_id')
        ->where('year(tbl_request.request_datetime) = '.$year.'')
        ->andWhere(['tbl_request.lab_id' => $lab,'tbl_request.status_id'=>1,'tbl_sample.active'=>1])
        ->groupBy('sampletype_id')
        ->orderBy('package_rate DESC')
        ->limit(10);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $modelRequest,
            'pagination' => false,
        ]);

        if(!empty(\Yii::$app->request->get())){
            return $this->renderAjax('_sample10', [
            'dataProvider' => $dataProvider
            ]);
        }else{
            return $dataProvider;
        }

        
    
    }
  
}

class DashboardDetails extends Model
{
    public $labid;
    public $labname;
    public $countperlab;
    public $labcolor;
    public $labcode;
    public $labicon;
    
    public function attributeLabels()
    {
        return [
             'labid' => 'Laboratory Id',
             'labname' => 'Laboratory Name',
             'countperlab' => 'Count per Lab',
             'labcolor' => 'Laboratory Color',
             'labcode' => 'Laboratory Code',
             'labicon' => 'Laboratory Icon'
           
          
        ];
    }
    
}
