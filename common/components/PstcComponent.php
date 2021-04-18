<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\components;

use Yii;
use yii\base\Component;
use yii\web\JsExpression;
use yii\web\NotFoundHttpException;
use linslin\yii2\curl;
use common\models\system\User;
//use common\models\lab\exRequestreferral;
//use common\models\lab\Analysis;
//use common\models\lab\Sample;

/**
 * Description of Pstc Component
 * Get Data from Referral API for local eULIMS
 * @author OneLab
 */
class PstcComponent extends Component {

    // public $source = 'http://eulims.test/api/restpstc/';
    public $source = 'https://eulims.onelab.ph/api/restpstc/';
    //public $source = 'http://localhost/eulimsapi.onelab.ph';
    
    //list to view
    
    function getAll($rstlId)
	{
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];

		if($rstlId > 0) {
            $apiUrl=$this->source.'request?rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
			return $list;
        } else {
            return 0;
        }
    }
    
	function getRequest($rstlId,$accepted)
	{
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        
		if($rstlId > 0 && isset($accepted)) {
            $apiUrl=$this->source.'request?rstl_id='.$rstlId.'&accepted='.$accepted;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
			return $list;
        } else {
            return 0;
        }
    }
    
    function getUpdateref($data)
	{
        $apiUrl=$this->source.'updateref';
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $params = [
            'id' => $data['id'],
            'reference' => $data['reference'],
            'due' => $data['due']
        ];

        $curl = new curl\Curl();
        $curl->setRequestBody(json_encode($params));
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        return $data = $curl->post($apiUrl);
        
    }
    

    function getRequestcreate($data)
	{
        $apiUrl=$this->source.'requestcreate';
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $params = [
            'customer_id' => $data['customer_id'],
            'user_id' => $data['user_id'],
            'submitted' =>$data['submitted'],
            'received' => $data['received'],
            'pstc_id' => $data['pstcid'],
            'rstl_id' => $data['rstl_id']
        ];
        $curl = new curl\Curl();
        $curl->setRequestBody(json_encode($params));
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        return $data = $curl->post($apiUrl);
        
    }

    function getAccepted($id)
	{
        $apiUrl=$this->source.'pstcaccepted?id='.$id;
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        return $data = $curl->get($apiUrl);
        
    }
    
    function getSamplecreate($data)
	{
        $apiUrl=$this->source.'sample';
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];

        $params = [
            'pstc_request_id' => $data['pstc_request_id'],
            'qnty' => $data['qnty'],
            'sample_name' =>$data['sample_name'],
            'sample_description' => $data['sample_description'],
        ];
        $curl = new curl\Curl();
        $curl->setRequestBody(json_encode($params));
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        return $data = $curl->post($apiUrl);
        
    }
    
    function getAnalysiscreate($data)
	{
        $apiUrl=$this->source.'analysis';
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        //retierate every sample
        $params = [
            'rstl_id' => $data['rstl_id'],
            'pstc_id' => $data['pstc_id'],
            'sample_id' => $data['sample_id'],
            'method_id' =>$data['method_id'],
            'testname' => $data['testname'],
        ];

        $curl = new curl\Curl();
        $curl->setRequestBody(json_encode($params));
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        return $data = $curl->post($apiUrl);

        
        // var_dump($); exit();
	}
	
    //for viewing single pstc request
    function getViewRequest($requestId,$rstlId,$pstcId)
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];

        if($requestId > 0 && $rstlId > 0 && $pstcId > 0) {
            $apiUrl=$this->source.'requestview?request_id='.$requestId.'&rstl_id='.$rstlId.'&pstc_id='.$pstcId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 0;
        }
    }

    //pstc request details for saving
    function getRequestDetails($requestId,$rstlId,$pstcId)
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];

        if($requestId > 0 && $rstlId > 0 && $pstcId > 0) {
            $apiUrl=$this->source.'request_details?request_id='.$requestId.'&rstl_id='.$rstlId.'&pstc_id='.$pstcId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 0;
        }
    }

    //get sample by sample ID
    function getSampleOne($sampleId,$requestId,$rstlId,$pstcId) {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];

        if($sampleId > 0 && $requestId > 0 && $rstlId > 0 && $pstcId > 0) {
            $apiUrl=$this->source.'get_pstcsample?sample_id='.$sampleId.'&request_id='.$requestId.'&rstl_id='.$rstlId.'&pstc_id='.$pstcId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return null;
        }
    }

    //check if the agency is the owner of the pstc request
    function checkOwner($requestId,$rstlId,$pstcId)
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];

        if($requestId > 0 && $rstlId > 0 && $pstcId > 0){
            $check = Pstcrequest::find()
                ->where('pstc_request_id =:requestId', [':requestId' => $requestId])
                ->andWhere('rstl_id =:rstlId', [':rstlId' => $rstlId])
                ->andWhere('pstc_id =:pstcId', [':pstcId' => $pstcId])
                ->count();
            
            if($check > 0){
                $status = 1;
            } else {
                $status = 0;
            }
            return $status;
        } else {
            return 0;
        }
    }

    //download request form
    function downloadRequest($requestId,$rstlId,$fileId)
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];

        if($requestId > 0 && $rstlId > 0 && $fileId > 0) {
            $apiUrl=$this->source.'/api/web/referral/pstcattachments/download?request_id='.$requestId.'&rstl_id='.$rstlId.'&file='.$fileId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);

            if($list == 'false') {
                return $list;
            } else {
                return $apiUrl;
            }
        } else {
            return false;
        }
    }

    ////////////////////////////////
    //////// TEST METHOD API ///////

    function checkMethod($id)
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];

        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $response = $curl->setGetParams(['id' => Yii::$app->user->identity->profile->rstl_id.'-'.$id,])->get($this->source."checkmethod");
        
        if($curl->errorCode != null){
            $response = 'Please try again later.';
        }
        return $response;
    }

    function listLab()
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $lists = $curl->get($this->source."listlab");

        return $lists;
    }

    function testnamemethods($id,$rstl_id)
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $lists = $curl->get($this->source."testnamemethods?id=".$id."&rstl_id=".$rstl_id);

        return $lists;
    }

    function sampletest($id)
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $lists = $curl->get($this->source."sampletest?id=".$id);

        return $lists;
    }

    function getMethodreference($id)
    {

        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        return $method = $curl->get($this->source."methodreference?id=".$id);
        
    }


    function testnamemethod($testname_id,$sampletype_id,$rstl_id)
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $lists = $curl->get($this->source."testnamemethod?testname_id=".$testname_id."&sampletype_id=".$sampletype_id."&rstl_id=".$rstl_id);

        return $lists;
    }

    function syncMethod($params)
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $curl = new curl\Curl();
        $curl->setRequestBody(json_encode($params));
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        return $data = $curl->post($this->source."syncmethod");
    }
    


    ///////////////////////////////////////////////////////////////
    //////// UPDATE PSTC REQUEST # INFO WHEN SAVE TO LOCAL ////////

    function updatePstc($params)
    {
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $curl = new curl\Curl();
        $curl->setRequestBody(json_encode($params));
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);

        return $data = $curl->post($this->source."updatepstc");
    }

    function getPstc($agency)
    {
   
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $lists = $curl->get($this->source."pstclist?agency=".$agency);

        return $lists;
    }

    function getCustomer($agency)
    {
   
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $lists = $curl->get($this->source."customerlist?agency=".$agency);

        return $lists;
    }

    function createcustomer($model){

        $customer = [
            'customer_name' => $model->customer_name,
            'head' =>$model->head,
            'tel' =>$model->tel,
            'fax' =>$model->fax,
            'email' =>$model->email,
            'business_nature_id' =>$model->business_nature_id,
            'industrytype_id' =>$model->industrytype_id,
            'customer_type_id' =>$model->customer_type_id,
            'classification_id' =>$model->classification_id,
            'address' =>$model->address,
            'rstl_id' =>$model->rstl_id,
        ];

        $apiUrl=$this->source.'createcustomer';
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];

        $curl = new curl\Curl();
        $curl->setRequestBody(json_encode($customer));
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        return $data = $curl->post($apiUrl);

        return true;
    }

    function deletesample($sample_id,$request_id,$pstc_id){
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $curl = new curl\Curl();
        $curl->setRequestBody(json_encode(['sample_id'=>$sample_id]));
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $lists = $curl->post($this->source."deletesample");
        return $lists;
    }

    function deleteanalysis($analysis_id){
        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $curl = new curl\Curl();
        $curl->setRequestBody(json_encode(['analysis_id'=>$analysis_id]));
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $lists = $curl->post($this->source."deletetest");
        return $lists;
    }

    function notify($request_id,$rstlId,$pstc_id){
        $CurrentUser= User::findOne(['user_id'=> Yii::$app->user->identity->user_id]);
        $fullname = $CurrentUser->profile ? $CurrentUser->profile->fullname : $CurrentUser->username;

        $token= 'Authorization: Bearer '.$_SESSION['usertoken'];
        $curl = new curl\Curl();
        $curl->setRequestBody(json_encode(['request_id'=>$request_id,'rstl_id'=>$rstlId,'fullname'=>$fullname,'pstc_id'=>$pstc_id]));
        $curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $token]);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $lists = $curl->post($this->source."notifycro");
        return $lists;
    }
}

