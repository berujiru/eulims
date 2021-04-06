<?php

/*
 * Project Name: eulims_ * 
 * Copyright(C)2018 Department of Science & Technology -IX * 
 * 06 7, 18 , 4:05:19 PM * 
 * Module: AjaxController * 
 */

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use common\models\lab\Discount;
use common\models\lab\Businessnature;
use common\models\finance\Client;
use common\models\lab\Customer;
use common\models\lab\Request;
use common\models\finance\PostedOp;
use frontend\modules\finance\components\epayment\ePayment;
use common\models\finance\Op;
use common\models\system\ApiSettings;
use linslin\yii2\curl;
use common\components\ReferralComponent;
use common\models\system\Rstl;
use yii\helpers\Url;
use yii\helpers\Json;


/**
 * Description of AjaxController
 *
 * @author OneLab
 */
class AjaxController extends Controller{
    public function behaviors(){
        return [
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'index'  => ['GET'],
                    'view'   => ['GET'],
                    'create' => ['GET', 'POST'],
                    'update' => ['GET', 'PUT', 'POST'],
                    'delete' => ['POST', 'DELETE'],
                ],
            ],
        ];
    }
    public function actionTestcurl($id){
        /*$apiUrl="https://api3.onelab.ph/lab/get-lab?id=11";
        $curl = new curl\Curl();
        $response = $curl->get($apiUrl);
        return $response;
         * 
         */
        $func=new \common\components\Functions();
        return $func->GetAccessToken(11);
    }
     public function actionSetwallet($customer_id,$amount,$source,$transactiontype){
        //$myvar = setTransaction($customer_id,$amount,$source,$transactiontype);
        return 200;
    }
    
    public function actionPostonlinepayment(){
        $post= \Yii::$app->request->post();
        $op_id=$post['op_id'];
        $PostedOp=new PostedOp();
        $ePayment=new ePayment();
        $result=[
            'status'=>'error',
            'description'=>'No Internet'
        ];
        $result=$ePayment->PostOnlinePayment($op_id);
        $response=json_decode($result);
        if($response->status=='error'){
            $posted=0;
            $success=false;
        }else{
            $posted=1;
            $PostedOp->orderofpayment_id=$op_id;
            $PostedOp->posted_datetime=date("Y-m-d H:i:s");
            $PostedOp->user_id= Yii::$app->user->id;
            $PostedOp->posted=$posted;
            $PostedOp->description=$response->description;
            $success=$PostedOp->save();
            if($success){
                $Op= Op::findOne($op_id);
                $Op->payment_mode_id=5;//Online Payment
                $Op->save(false);
            }
        } 
        Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        return $response;
    }
    public function actionGetdiscount(){
        $post= \Yii::$app->request->post();
        $id=$post['discountid'];
        $discount= Discount::find()->where(['discount_id'=>$id])->one();
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        return $discount;
    }
    public function actionGetcustomer(){
        $post= \Yii::$app->request->post();
        $id=$post['discountid'];

        $request = Request::find()->where(['request_ref_num'=>$id])->one();
        $customer= Customer::find()->where(['customer_id'=>2])->one();
        $nob = Businessnature::find()->where(['business_nature_id'=>$customer->business_nature_id])->one();

        $customer_name = $customer->customer_name;

        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        return ["customer_name"=> $customer_name, "nob"=>$nob->nature];
    }
    public function actionGetdiscountreferral(){
        $id=(int) \Yii::$app->request->get('discountid');
        $apiUrl='https://eulimsapi.onelab.ph/api/web/referral/listdatas/discountbyid?discount_id='.$id;
        //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/discountbyid?discount_id='.$id;
        $curl = new curl\Curl();
        $discount = $curl->get($apiUrl);
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        return json_decode($discount);
    }
    public function actionGetcustomerhead($id){
        \Yii::$app->response->format =\yii\web\Response::FORMAT_JSON;
        $Customers=Customer::find()->where(['customer_id'=>$id])->one();
        return $Customers;
    }
    public function actionTogglemenu(){
        $session = Yii::$app->session;
        $hideMenu= $session->get("hideMenu");
        if(!isset($hideMenu)){
           $hideMenu=false; 
        }
        $b=!$hideMenu;
        $session->set('hideMenu',$b);
        //return $hideMenu;
        echo $session->get("hideMenu");
    }
    public function actionGetaccountnumber(){
        $post= Yii::$app->request->post();
        $id=(int)$post['customer_id'];
        $AccNumber="<no accountnumber>";
        $Client= Client::find()->where(['customer_id'=>$id])->one();
        if($Client){
            $AccNumber=$Client->account_number;
        }else{
            $AccNumber="<no account number>";
        }
        return $AccNumber;
    }
    public function actionGetsoabalance(){
        $Connection=Yii::$app->financedb;
        $post= Yii::$app->request->post();
        $id=(int)$post['customer_id'];
        $Proc="CALL spGetSoaPreviousAccount(:mCustomerID)";
        $Command=$Connection->createCommand($Proc);
        $Command->bindValue(':mCustomerID',$id);
        $Row=$Command->queryOne();
        $Balance=(float)$Row['Balance'];
        return $Balance;
    }


    //toremove uneccessary and unsused code above
    //lazy load the following 
    public function actionGetunrespondednotification(){
        $items ='<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-bell-o"></i><span class="label label-success" id="referralcount">0</span></a><ul class="dropdown-menu" style="width: 700px!important;"><li class="header" id="referralheader">You have 0 new notification</li><li></li><li class="footer">
                            <a href="'.Url::to($GLOBALS["frontend_base_uri"]."/referrals/notification").'">View all Notification</a>
                        </li></ul>';
        if(isset($_SESSION['usertoken'])&&(!Yii::$app->user->isGuest)){
            //get the unresponded notification of the referral
            $referralcomp = new ReferralComponent;
            if(isset(Yii::$app->user->identity->profile->rstl_id)){
                $unresponded = $referralcomp->listUnrespondedNofication((int) Yii::$app->user->identity->profile->rstl_id);
                if($unresponded['count_notification']==0)
                    return $items; //stops here
                $items='';
                $items.='<a href="#" class="dropdown-toggle" data-toggle="dropdown">';
                $items.='<i class="fa fa-bell-o"></i>';
                $items.='<span class="label label-warning" id="referralcount">'.$unresponded['count_notification'].'</span>';
                $items.='</a>';
                $items.='<ul class="dropdown-menu" style="width: 700px!important;">';
                $items.='<li class="header" id="referralheader">You have '.$unresponded['count_notification'].' Notification</li>';
                $items.='<li>';
                $items.='<ul class="menu">';
                foreach ($unresponded['notification'] as $item) {
                    //get the agency they came from
                    $rstlcode = Rstl::find()->select('code')->where(['rstl_id'=>$item['sender_id']])->one();
                    $items.= '<li>';
                        $textcontent ="";
                        switch ($item['notification_type_id']) {
                            case 1:
                                $items.= '<a href="/referrals/referral/view?id='.$item['referral_id'].'&notice_id='.$item['notification_id'].'">';
                                $textcontent = "Sent Referral Notification!";
                                break;
                            case 2:
                                //get referral local id
                                $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;
                                $referral = $referralcomp->getReferralOne($item['referral_id'],$rstlId);
                                $items.= '<a href="/lab/request/view?id='.$referral['local_request_id'].'&notice_id='.$item['notification_id'].'">';
                                $textcontent= "Accepted the Referral Notification!";
                                break;
                            case 3:
                                $items.= '<a href="/referrals/referral/view?id='.$item['referral_id'].'&notice_id='.$item['notification_id'].'">';
                                $textcontent ="Sent a Referral!";
                                break;
                            
                            default:
                                $items.= '<a href="#">';
                                $textcontent="Unregistered Notification!";
                                break;
                        }
                            $items.= '<div class="pull-left">';
                                $items.= '<img src="images/avatar04.png" class="img-circle" alt="User Image">';
                            $items.= '</div>';
                            $items.= '<h4>';
                                $items.= $item['sender_name'].($rstlcode?' of '.$rstlcode->code:'');
                                $items.= '<small><i class="fa fa-clock-o"></i> '.$item['notification_date'].'</small>';
                            $items.= '</h4>';
                            $items.= '<p>'.$textcontent.'</p>';
                        $items.= '</a>';
                    $items.= '</li>';
                    
                }

                //the footer
                $items.='</ul>';
                $items.='</li>';
                //$items.='<li class="footer"><a href="#">See All Messages</a></li>';
                $items.='<li class="footer">
                            <a href="'.Url::to($GLOBALS["frontend_base_uri"]."/referrals/notification").'">View all Notification</a>
                        </li>';
                $items.='</ul>';
            }
        }
        //else return with empty <li>
        return $items;


    }
	
	public function actionGetcontactuser(){
		if(isset($_SESSION['usertoken'])){
			$sourcetoken=$_SESSION['usertoken'];
			$userid= $_SESSION['userid'];
			$authorization = "Authorization: Bearer ".$sourcetoken; 
			$apiUrl=$GLOBALS['newapi_url'].'message/getuser';
			//$apiUrl=$source.'getuser';
			$curl = new curl\Curl();
			$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
			$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
			$curl->setOption(CURLOPT_TIMEOUT, 180);
			$list = $curl->get($apiUrl);
			$decode=Json::decode($list);
			
			$y="";
			$y.= "<h4>List of Contacts </h4>";
			 
			$y.=  "<div>";
			$y.= "<input type='text' id='txtsearch' placeholder='Search' style='width:90%;'>";
			$y.= "<button id='btnsearch' name='btnsearch' onclick='search();'>";
			$y.= "<i class='fa fa-search'></i>";
			$y.= "</button>";
			$y.= "</div>";
			$y.= "</br>";
			if($decode){
				foreach ($decode as $data){ 
					 $profuserid=$data['user_id'];
					 if(isset($_SESSION['usertoken'])){
						$userid2=($_SESSION['usertoken']);
					 }
				
					$y.="<a class='thismessage' onclick='mes(".$data['user_id'].",1)'>";
					$y.="<div class='first'><img src='/uploads/user/photo/user.png' alt='/uploads/user/photo/user.png' width='42' height='42'>&nbsp;<b>". $data['fullname']."</div>";
					$y.="</a>";
				}
			}
			return $y;
		}else{
			return;
		}
	}
	
	public function actionGetgroup(){
		if(isset($_SESSION['usertoken'])){
		$sourcetoken=$_SESSION['usertoken'];
		$userid= $_SESSION['userid'];
		$authorization = "Authorization: Bearer ".$sourcetoken; 
		$groupUrl=$GLOBALS['newapi_url'].'message/getgroup?userid='.$userid;
		$curlgroup = new curl\Curl();
		$curlgroup->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
		$curlgroup->setOption(CURLOPT_SSL_VERIFYPEER, false);
		$curlgroup->setOption(CURLOPT_CONNECTTIMEOUT, 180);
		$curlgroup->setOption(CURLOPT_TIMEOUT, 180);
		$grouplist = $curlgroup->get($groupUrl);
		$group=Json::decode($grouplist);
		
		$y="";
		$y.= "<h4>List of Contacts </h4>";
		 
		$y.=  "<div>";
		$y.= "<input type='text' id='txtsearch' placeholder='Search' style='width:90%;'>";
		$y.= "<button id='btnsearch' name='btnsearch' onclick='search();'>";
		$y.= "<i class='fa fa-search'></i>";
		$y.= "</button>";
		$y.= "</div>";
		$y.= "</br>";
		if($group){
			foreach ($group as $data){ 
				$y.="<a class='thismessage1' onclick='mes(".$data['chat_group_id'].",2)'>";
				$y.= "<div class='first'><img src='/uploads/user/photo/group.png' alt='/uploads/user/photo/user.png' width='42' height='42'>&nbsp;<b>". $data['chatGroup']['group_name'] ."</div>";
				$y.="</a>";
			}
		}
		return $y;
		}else{
			return;	
		}
	}
}
