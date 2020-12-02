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
use yii\helpers\ArrayHelper;
//use common\models\lab\Request;
//use kartik\grid\GridView;
use yii\web\NotFoundHttpException;
//use common\models\lab\Analysisextend;
//use common\models\system\LogSync;
//use common\models\system\ApiSettings;
use linslin\yii2\curl;
use common\models\lab\exRequestreferral;
use common\models\lab\Analysis;
use common\models\lab\Sample;
use common\models\lab\Lab;//used to point to old referral
use common\models\lab\Samplecode;//used to point to old referral
use common\models\lab\Referralrequest;
use yii\helpers\Json;


/**
 * Description of Referral Component
 * Get Data from Referral API for local eULIMS
 * @author OneLab
 */
class ReferralComponent extends Component {

    public $source = 'http://www.eulims.local/api/restreferral';
    // public $source = 'https://eulims.dost9.ph/api/restreferral';
    // public $source = 'https://eulims.onelab.dost.gov.ph/api/restreferral';

    /**
     * Get Source
     * @return string
     */
    function getSource(){
        return $this->source;
    }


    /**
    *
    * Notifies refered agency about the referral
    * @return boolean
    */
    function notifyAgency($referral_id,$agency_id){
        //salvaged from sTG
        $mi = !empty(Yii::$app->user->identity->profile->middleinitial) ? " ".substr(Yii::$app->user->identity->profile->middleinitial, 0, 1).". " : " ";
        $senderName = Yii::$app->user->identity->profile->firstname.$mi.Yii::$app->user->identity->profile->lastname;

        $details = [
            'referral_id' => $referral_id,
            'sender_id' => Yii::$app->user->identity->profile->rstl_id,
            'recipient_id' => $agency_id,
            'sender_user_id' => Yii::$app->user->identity->profile->user_id,
            'sender_name' => $senderName,
            'remarks' => "Notification sent"
        ];
        
        $notificationData = Json::encode(['notice_details'=>$details],JSON_NUMERIC_CHECK);

        //trying to contact the mothership as API :D oh GOD how long do i need to read these code
        $apiUrl=$this->source.'/notify';
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
        $curl->setOption(CURLOPT_TIMEOUT, 180);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $response = $curl->setRequestBody($notificationData)
        ->setHeaders([
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($notificationData),
        ])->post($apiUrl);


        //check the response
        if($response)
            return $response;

        return false;
    }

    /**
     * Sync Referral
     * BTC
     * Saves the Referral information on the central server
     * @return boolean
     */
    function syncReferral($request_id,$agency_id){

        $sample_data = [];
        $analysis_data = [];

        //gets the request information in the local server 
        $request = exRequestreferral::find()->where(['request_id'=>$request_id,'request_type_id'=>2])->asArray()->one();
        //gets the referral information which is related to request above
        $ref_request = Referralrequest::find()->where('request_id =:requestId',[':requestId'=>$request_id])->one();
        //get the associated sample of the request above
        $samples = Sample::find()->where(['request_id'=>$request_id])->asArray()->all();
        //get the associated analyses of the request above
        $analyses = Analysis::find()->where(['request_id'=>$request_id])->asArray()->all();

        if(count($request) > 0 && count($ref_request) > 0 && count($samples) > 0 && count($analyses) > 0){

            //perform the extraction of request data, salvaged the data from STG
            // BTC why do we have to reformat given that the returned value of the query is already in array, maybe because the structure of the local and Centralize dbase is not the same??????
            $requestData = [
                    'request_id' => $request['request_id'],
                    'request_datetime' => $request['request_datetime'],
                    'rstl_id' => $request['rstl_id'],
                    'lab_id' => $request['lab_id'],
                    'customer_id' => $request['customer_id'],
                    'payment_type_id' => $request['payment_type_id'],
                    'modeofrelease_ids' => $request['modeofrelease_ids'],
                    'discount_id' => $request['discount_id'],
                    'discount' => $request['discount'],
                    'purpose_id' => $request['purpose_id'],
                    'total' => $request['total'],
                    'report_due' => $request['report_due'], //initial estimated due date sent by the receiving lab
                    'conforme' => $request['conforme'],
                    'receivedBy' => $request['receivedBy'],
                    'status_id' => $request['status_id'],
                    'request_type_id' => $request['request_type_id'],
                    'created_at' => $request['created_at'],
                    'sample_received_date' => $ref_request['sample_received_date'],
                    'user_id_receiving' => Yii::$app->user->identity->profile->user_id,
                    'bid'=>0
                ];

            //perform the extraction of the sample data, salvaged code from STG
            foreach ($samples as $sample) {
                $sampleData = [
                    'sample_id' => $sample['sample_id'],
                    'rstl_id' => $sample['rstl_id'],
                    'sampletype_id' => $sample['sampletype_id'],
                    'sample_code' => $sample['sample_code'],
                    'samplename' => $sample['samplename'],
                    'description' => $sample['description'],
                    'customer_description' => $sample['customer_description'],
                    'sampling_date' => $sample['sampling_date'],
                    'remarks' => $sample['remarks'],
                    'request_id' => $sample['request_id'],
                    'sample_month' => $sample['sample_month'],
                    'sample_year' => $sample['sample_year'],
                    'active' => $sample['active'],
                    'completed' => $sample['completed']
                ];
                array_push($sample_data, $sampleData);
            }

            //another one for analyses
            foreach ($analyses as $analysis) {
                $analysisData = [
                    'analysis_id' => $analysis['analysis_id'],
                    'date_analysis' => $analysis['date_analysis'],
                    'rstl_id' => $analysis['rstl_id'],
                    'request_id' => $analysis['request_id'],
                    'sample_id' => $analysis['sample_id'],
                    'sample_code' => $analysis['sample_code'],
                    'package_id' => $analysis['package_id'],
                    'testname' => $analysis['testname'],
                    'method' => $analysis['method'],
                    'methodref_id' => $analysis['methodref_id'],
                    'references' => $analysis['references'],
                    'fee' => $analysis['fee'],
                    'test_id' => $analysis['test_id'],
                    'cancelled' => $analysis['cancelled'],
                    'is_package' => $analysis['is_package'],
                    // 'is_package_name' => $analysis['is_package_name'],
                    'type_fee_id' => $analysis['type_fee_id']
                ];
                array_push($analysis_data, $analysisData);
            }

            //obviously encoding the 
            $data = Json::encode(['request_data'=>$requestData,'sample_data'=>$sample_data,'analysis_data'=>$analysis_data,'agency_id'=>$agency_id],JSON_NUMERIC_CHECK);

            //trying to contact the mothership as API :D oh GOD how long do i need to read these code
            $apiUrl=$this->source.'/insertreferraldata';
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $response = $curl->setRequestBody($data)
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($data),
            ])->post($apiUrl);

            //next in line sigh just decoding
            $response = Json::decode($response); //returns response and referralID

                //if response is false , information weren't saved and not notified
                if($response){
                    return $response;
                }

                return false;
            
        }else{
            return false;
        }
    }

    /**
     * FindOne testname
     * @param integer $testnameId
     * @return array
     */
    function getTestnameOne($testnameId){
        if($testnameId > 0){
            $apiUrl=$this->source.'/testnameone?testname_id='.$testnameId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return json_decode($list);
        } else {
            return "Not valid testname";
        }
    }
    /**
     * FindOne Method reference
     * @param integer $methodrefId
     * @return array
     */
    function getMethodrefOne($methodrefId){
        if($methodrefId > 0){
            $apiUrl=$this->source.'/methodreferenceone?methodref_id='.$methodrefId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return json_decode($list);
        } else {
            return "Not valid method reference";
        }
    }



     function getAgencybyMethodrefOne($methodrefId){
        if($methodrefId > 0){
            $apiUrl=$this->source.'/agencymethodreferenceone?methodref_id='.$methodrefId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return json_decode($list);
        } else {
            return "Not valid method reference";
        }
    }


    /**
     * FindOne Discount
     * @param integer $discountId
     * @return array
     */
    function getDiscountOne($discountId){
        if($discountId >= 0){
            $apiUrl=$this->source.'/discountbyid?discount_id='.$discountId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return json_decode($list);
        } else {
            return "Not valid discount";
        }
    }
    /**
     * FindOne Customer
     * @param integer $customerId
     * @return array
     */
    function getCustomerOne($customerId){
        if($customerId > 0){
            $apiUrl=$this->source.'/getcustomer?customer_id='.$customerId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return "Not valid customer";
        }
    }
    //get referral sample type by lab
    function getSampletype($labId)
    {
        if($labId > 0){
            $apiUrl=$this->source.'/sampletypebylab?lab_id='.$labId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return "Not valid lab";
        }
    }
    //get referral testname by sampletype
    function getTestnames($labId,$sampletypeId){
        if($labId > 0 && $sampletypeId > 0){
            $apiUrl=$this->source.'/api/web/referral/listdatas/testnamebylab_sampletype?lab_id='.$labId.'&sampletype_id='.$sampletypeId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return "Not valid lab or sampletype";
        }
    }

    //btc use in the referral view button add analysis
    function getTestnamesbysampletypeidsonly($sampletypeId){
        if($sampletypeId > 0){
            $apiUrl=$this->source.'/testnamebysampletypeids?sampletype_ids='.$sampletypeId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return "Not valid lab or sampletype";
        }
    }

    //get referral methodref by testname
    function getMethodrefs($labId,$sampletypeId,$testnameId){
        if($labId > 0 && $sampletypeId > 0 && $testnameId > 0){
            //$apiUrl=$this->source.'/api/web/referral/listdatas/testnamemethodref?testname_id='.$testnameId.'&sampletype_id='.$sampletypeId.'&lab_id='.$labId;
            $apiUrl=$this->source.'/api/web/referral/services/methodrefs?testname_id='.$testnameId.'&sampletype_id='.$sampletypeId.'&lab_id='.$labId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return "Not valid testname";
        }
    }

    //btc this function here is same with the top but with only testname_id only as argument
    function getMethodrefbytestnameidonly($testnameId){

        $apiUrl=$this->source.'/testnamemethodref?testname_id='.$testnameId;
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
        $curl->setOption(CURLOPT_TIMEOUT, 120);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $data = $curl->get($apiUrl);
        return $data;
    }

    //get referral laboratory list
    function listLabreferral()
    {
        $apiUrl=$this->source.'/labs';
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
        $curl->setOption(CURLOPT_TIMEOUT, 180);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $list = $curl->get($apiUrl);
        return $list;
    }
    //get referral discount list
    function listDiscountreferral()
    {
        $apiUrl=$this->source.'/discounts';
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
        $curl->setOption(CURLOPT_TIMEOUT, 180);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $list = $curl->get($apiUrl);
        return $list;
    }
    //get referral purpose list
    function listPurposereferral()
    {
        $apiUrl=$this->source.'/purposes';
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
        $curl->setOption(CURLOPT_TIMEOUT, 180);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $list = $curl->get($apiUrl);
        return $list;
    }
    //get referral mode of release list
    function listModereleasereferral()
    {
        $apiUrl=$this->source.'/modesrelease';
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
        $curl->setOption(CURLOPT_TIMEOUT, 180);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $list = $curl->get($apiUrl);
        return $list;
    }

    //get matching services
    function listMatchAgency($requestId){

        //only get the method_reference used by this request then call the listcmatch agency to return list of agency use and status if the referral can be submitted to the target rstl agency.

        
        $request = exRequestreferral::findOne($requestId);

        $analysis = Analysis::find()
            ->joinWith('sample')
            ->where('tbl_sample.request_id =:requestId AND is_package =:packageName',[':requestId'=>$requestId,':packageName'=>0])
            ->groupBy(['test_id','methodref_id'])
            ->asArray()->all();

        //if there are no tests then dont proceed
        if(!$analysis)
            return false;

        //also check the package if the testmethodrefs are done
        // $package = Analysis::find()
        //     ->joinWith('sample')
        //     ->where('tbl_sample.request_id =:requestId AND is_package =:packageName AND type_fee_id =:typeFee',[':requestId'=>$requestId,':packageName'=>1,':typeFee'=>2])
        //     ->groupBy(['package_id'])
        //     ->asArray()->all();

        $methodrefId = implode(',', array_map(function ($data) {
            return $data['methodref_id'];
        }, $analysis));

        $packageId=""; //empty for now


        // $packageId = implode(',', array_map(function ($data) {
        //     return $data['package_id'];
        // }, $package));


        //this should not trigger if there are no anlyses , packages or samples, throws err if no test found
        // $apiUrl=$this->source.'/listmatchagency?rstl_id='.$request->rstl_id.'&lab_id='.$request->lab_id.'&sampletype_id='.$sampletypeId.'&testname_id='.$testnameId.'&methodref_id='.$methodrefId.'&package_id='.$packageId;

        $apiUrl=$this->source.'/listmatchagency?rstl_id='.$request->rstl_id.'&lab_id='.$request->lab_id.'&methodref_id='.$methodrefId.'&package_id='.$packageId;

        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
        $curl->setOption(CURLOPT_TIMEOUT, 180);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $data = $curl->get($apiUrl); //data now holds a list of rstls
    
        $list_agency = $this->listAgency(json_decode($data));
        return $list_agency;        
    }
    
    //get list agencies
    function listAgency($agencyId)
    {   
        if(!empty($agencyId)){
            $agencies = rtrim($agencyId);
            $apiUrl=$this->source.'/listagency?agency_id='.$agencies;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return null;
        }
    }
    //check if notified //btc was here XD
    function checkNotify($requestId,$agencyId)
    {
        if($requestId > 0 && $agencyId > 0) {
            $apiUrl=$this->source.'/checknotify?request_id='.$requestId.'&agency_id='.$agencyId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //check if bid notified
    /*function checkBidNotify($requestId,$agencyId)
    {
        if($requestId > 0 && $agencyId > 0) {
            $apiUrl=$this->source.'/api/web/referral/bidnotifications/checknotify?request_id='.$requestId.'&agency_id='.$agencyId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }*/
    //check if confirmed //btc has been here
    function checkConfirm($requestId,$rstlId,$testingAgencyId)
    {
        if($requestId > 0 && $rstlId > 0 && $testingAgencyId > 0) {
            $apiUrl=$this->source.'/api/web/referral/notifications/checkconfirm?request_id='.$requestId.'&receiving_id='.$rstlId.'&testing_id='.$testingAgencyId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //check if agency participated the bidding
    /*function checkBidder($referralId,$agencyId)
    {
        if($agencyId > 0 && $referralId > 0){
            $bid = Bid::find()->where('bidder_agency_id =:bidderAgencyId AND referral_id =:referralId',[':bidderAgencyId'=>$agencyId,':referralId'=>$referralId])->count();
            if($bid > 0){
                return 1; 
            } else {
                return 0;
            }
        } else {
            return 'false';
        }
    }*/
    //check if active lab
    function checkActiveLab($labId, $agencyId)
    {
        if($labId > 0 && $agencyId > 0) {
            $apiUrl=$this->source.'/checkactivelab?lab_id='.$labId.'&agency_id='.$agencyId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //check if agency is active
    function checkActiveAgency($agencyId)
    {
        if($agencyId > 0) {
            $apiUrl=$this->source.'/api/web/referral/referrals/checkactiveagency?agency_id='.$agencyId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //post delete request
    function removeReferral($agencyId,$requestId)
    {
        if($agencyId > 0 && $requestId > 0){
            $apiUrl=$this->source.'/api/web/referral/referrals/deletereferral';
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $data = Json::encode(['request_id'=>$requestId,'rstl_id'=>$agencyId],JSON_NUMERIC_CHECK);
            $response = $curl->setRequestBody($data)
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($data),
            ])->post($apiUrl);

            return $response;
        } else {
            return 0;
        }
    }
    //get referral notifications
    function listUnrespondedNofication($rstlId)
    {
        if($rstlId > 0) {
            $apiUrl=$this->source.'/countnotification?rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return Json::decode($list);
        } else {
            return false;
        }
    }
    //get bid notifications
    function listUnseenBidNofication($rstlId)
    {
        if($rstlId > 0) {
            $apiUrl=$this->source.'/countbidnotification?rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return Json::decode($list);
        } else {
            return false;
        }
    }
    //get bidder agency
    function getBidderAgency($requestId,$rstlId)
    {
        return false;
        if($requestId > 0 && $rstlId > 0){
            $apiUrl=$this->source.'/bidderagency?request_id='.$requestId.'&rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return false;
        }
    }
    //list bidders
    function listBidders($agencyId){
        if($agencyId > 0){
            $apiUrl=$this->source.'/api/web/referral/bidnotifications/listbidder?agency_id='.$agencyId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return false;
        }
    }
    //count bid notices
    function countBidnotice($requestId,$rstlId){
        return false;
        if($requestId > 0 && $rstlId > 0){
            $apiUrl=$this->source.'/bidnotice?request_id='.$requestId.'&rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return false;
        }
    }
    //get bid estimated due date
    function getBidDuedate($requestId,$rstlId,$senderId)
    {
        if($rstlId > 0 && $requestId > 0 && $senderId > 0) {
            $apiUrl=$this->source.'/api/web/referral/bidnotifications/showdue?request_id='.$requestId.'&rstl_id='.$rstlId.'&sender_id='.$senderId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //get referral details via referral_id
    function getReferraldetails($referralId,$rstlId)
    {
        if($referralId > 0 && $rstlId > 0) {
            //api pointing to the new referral system
            $apiUrl=$this->source.'/api/web/referral/referrals/viewdetail?referral_id='.$referralId.'&rstl_id='.$rstlId;
            //api pointing to the old referral system
            $apiUrl=$this->source.'/lab/api/view/model/referrals/id/'.$referralId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //get referral,sample,analysis details for saving in eulims local
    function getReferralRequestDetails($referralId,$rstlId)
    {
        if($referralId > 0 && $rstlId > 0) {
            $apiUrl=$this->source.'/api/web/referral/referrals/get_referral_detail?referral_id='.$referralId.'&rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //get all notifications of rstl
    function getNotificationAll($rstlId)
    {
        if($rstlId > 0) {
            $apiUrl=$this->source.'/api/web/referral/notifications/listall?rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //get all bid notifications of rstl
    function getBidNotificationAll($rstlId)
    {
        if($rstlId > 0) {
            $apiUrl=$this->source.'/api/web/referral/bidnotifications/listall?rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //get notification
    function getNotificationOne($notificationId,$rstlId)
    {
        if($rstlId > 0 && $notificationId > 0) {
            $apiUrl=$this->source.'/api/web/referral/notifications/notification_one?notification_id='.$notificationId.'&rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //get estimated due date
    function getDuedate($requestId,$rstlId,$senderId)
    {
        if($rstlId > 0 && $requestId > 0 && $senderId > 0) {
            $apiUrl=$this->source.'/showdue?request_id='.$requestId.'&rstl_id='.$rstlId.'&sender_id='.$senderId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return json_decode($list);
        } else {
            return 'Not valid request!';
        }
    }
    //get checkowner
    function checkOwner($referralId,$rstlId)
    {
        if($rstlId > 0 && $referralId > 0) {
            $apiUrl=$this->source.'/api/web/referral/referrals/checkowner?referral_id='.$referralId.'&sender_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //get only referral
    function getReferralOne($referralId,$rstlId)
    {
        if($referralId > 0){
            $apiUrl=$this->source.'/api/web/referral/referrals/referral_one?referral_id='.$referralId.'&rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Invalid referral!';
        }
    }
    //get details for sample code
    function getSamplecode_details($requestId,$rstlId){
        if($requestId > 0 && $rstlId > 0) {
            $apiUrl=$this->source.'/api/web/referral/referrals/get_samplecode?request_id='.$requestId.'&rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Invalid request!';
        }
    }
    //get attachment
    function getAttachment($referralId,$rstlId,$type){
        return false;
        if($referralId > 0 && $rstlId > 0 && $type > 0) {
            $apiUrl=$this->source.'/show_upload?referral_id='.$referralId.'&rstl_id='.$rstlId.'&type='.$type;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Invalid referral!';
        }
    }
    function downloadAttachment($referralId,$rstlId,$fileId){
        if($referralId > 0 && $rstlId > 0 && $fileId > 0) {
            $apiUrl=$this->source.'/api/web/referral/attachments/download?referral_id='.$referralId.'&rstl_id='.$rstlId.'&file='.$fileId;
            //$curl = new curl\Curl();
            //$file = $curl->get($apiUrl);
            //return $apiUrl;
            //if($file == 0){
            //    return 'false';
            //} else {
            //    return $apiUrl;
            //}
            return $apiUrl;
        } else {
            return 'false';
        }
    }
    function getReferredAgency($referralId,$rstlId){
        return false;
        if($referralId > 0 && $rstlId > 0) {
            $apiUrl=$this->source.'/referred_agency?referral_id='.$referralId.'&rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'false';
        }
    }
    function getReferralAll($rstlId){
        if($rstlId > 0) {
            $apiUrl=$this->source.'/api/web/referral/referrals/referral_all?rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'false';
        }
    }
    function getIncomingReferral($rstlId) {
        if($rstlId > 0) {
            //api for new referral
            // $apiUrl=$this->source.'/api/web/referral/referrals/incoming_referral?rstl_id='.$rstlId;
            //direct the api to the old referral
            $apiUrl=$this->source.'/lab/api/list/model/referrals/agency/'.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list; exit;
        } else {
            return 'false';
        }
    }
    function getSentReferral($rstlId) {
        if($rstlId > 0) {
            $apiUrl=$this->source.'/api/web/referral/referrals/sent_referral?rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'false';
        }
    }
    //offer service
    function offerService($data){
        $referralUrl=$this->source.'/api/web/referral/services/offer';
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
        $curl->setOption(CURLOPT_TIMEOUT, 180);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $referralreturn = $curl->setRequestBody($data)
        ->setHeaders([
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($data),
        ])->post($referralUrl);

        return $referralreturn;
    }
    //remove service
    function removeService($data){
        $referralUrl=$this->source.'/api/web/referral/services/remove';
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
        $curl->setOption(CURLOPT_TIMEOUT, 180);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $referralreturn = $curl->setRequestBody($data)
        ->setHeaders([
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($data),
        ])->post($referralUrl);

        return $referralreturn;
    }
    //check if service is already offered
    function checkOffered($methodrefId,$rstlId){
        if($rstlId > 0 && $methodrefId > 0) {
            $apiUrl=$this->source.'/api/web/referral/services/check_offered?methodref_id='.$methodrefId.'&rstl_id='.$rstlId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $check = $curl->get($apiUrl);
            return $check;
        } else {
            return 0;
        }
    }
    //return method reference offered by
    function offeredBy($methodrefId){
        if($methodrefId > 0){
            $apiUrl=$this->source.'/api/web/referral/services/offeredby?methodref_id='.$methodrefId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $offeredby = $curl->get($apiUrl);
            return $offeredby;
        } else {
            return 0;
        }
    }
    //get package one
    function getPackageOne($packageId)
    {
        if($packageId > 0){
            $apiUrl=$this->source.'/package_detail?package_id='.$packageId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 0;
        }
    }
    //get list of packages
    function getPackages($labId,$sampletypeId)
    {
        if($labId > 0 && $sampletypeId > 0){
            $apiUrl=$this->source.'/listpackage?lab_id='.$labId.'&sampletype_id='.$sampletypeId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 0;
        }
    }
    //check if test bid added
    /*function checkTestbid($referralId,$analysisId,$bidderAgencyId)
    {
        if($referralId > 0 && $analysisId > 0 && $bidderAgencyId > 0) {
            $testBid = Testbid::find()->where('referral_id =:referralId AND analysis_id =:analysisId AND bidder_agency_id =:bidderAgencyId',[':referralId'=>$referralId,':analysisId'=>$analysisId,':bidderAgencyId'=>$bidderAgencyId])->count();
            return $testBid;
        } else {
            return 'false';
        }
    }*/
    //function count all both unresponded referral and unseen bid notifications
    function countAllNotification($rstlId)
    {
        if($rstlId > 0){
            $bid =  $this->listUnseenBidNofication($rstlId);
            $referral = $this->listUnrespondedNofication($rstlId);
            $bid_notification = $bid['count_bidnotification'];
            $referral_notification = $referral['count_notification'];
            $allNotification = $bid_notification + $referral_notification;

            return $allNotification;
        } else {
            return 'false'; //why false when u can return 0, anyway ... btc here
        }
    }
    //function to get agency bid details for update to local ulims
    function getBidDetails($referralId,$rstlId,$bidderAgencyId,$bidId)
    {
        if($referralId > 0 && $rstlId > 0 && $bidderAgencyId > 0 && $bidId > 0) {
            $apiUrl=$this->source.'/api/web/referral/bids/bid_details?referral_id='.$referralId.'&rstl_id='.$rstlId.'&bidder_id='.$bidderAgencyId.'&bid_id='.$bidId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'false';
        }
    }
    //function to get agency bid details for update to local ulims
    function getTestbidDetails($referralId,$rstlId,$bidderAgencyId)
    {
        if($referralId > 0 && $rstlId > 0 && $bidderAgencyId > 0) {
            $apiUrl=$this->source.'/api/web/referral/bids/testbid_details?referral_id='.$referralId.'&rstl_id='.$rstlId.'&bidder_id='.$bidderAgencyId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'false';
        }
    }

    //function bid notification details
    function getBidNoticeDetails($referralId,$rstlId,$noticeId,$seen)
    {
        if($referralId > 0 && $rstlId > 0 && $noticeId > 0 && $seen == 1) {
            $apiUrl=$this->source.'/api/web/referral/bids/notice_details?referral_id='.$referralId.'&agency_id='.$rstlId.'&notice_id='.$noticeId.'&seen='.$seen;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'false';
        }
    }
    //get referral track receiving

    //get referral track receiving by referral id

    function getTrackreceiving($referralId)
    {
       
        if($referralId > 0) {
            $apiUrl=$this->source.'/api/web/referral/referraltrackreceivings/detail?referral_id='.$referralId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //get referral track testing by referral id
    function getTracktesting($referralId)
    {
       
        if($referralId > 0) {
            $apiUrl=$this->source.'/api/web/referral/referraltracktestings/detail?referral_id='.$referralId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    
    //get Status Logs
    function getStatuslogs($Id)
    {
       
        if($Id > 0) {
            $apiUrl=$this->source.'/api/web/referral/referrals/logs?id='.$Id;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //get referral track testing by referral id
    function getTracktestingdata($Id)
    {
       
        if($Id > 0) {
            $apiUrl=$this->source.'/api/web/referral/referraltracktestings/getdata?id='.$Id;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'Not valid request!';
        }
    }
    //get Courier
    function getCourierdata()
    {
       
            $apiUrl=$this->source.'/api/web/referral/couriers/getdata';
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            if($list){
                return $list;
            }else {
                return 'Error in connection!';
            }
    }
    function getCourierone($id)
    {
       
            $apiUrl=$this->source.'/api/web/referral/couriers/getone?id='.$id;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 120);
            $curl->setOption(CURLOPT_TIMEOUT, 120);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            if($list){
                return $list;
            }else {
                return 'Error in connection!';
            }
    }
    
    function getCheckstatus($referralId,$statusId)
    {
        if($referralId > 0 && $statusId > 0) {
            //$apiUrl=$this->source.'/api/web/referral/bids/notice_details?referral_id='.$referralId.'&agency_id='.$rstlId.'&notice_id='.$noticeId.'&seen='.$seen;
            $apiUrl=$this->source.'/api/web/referral/statuslogs/checkstatuslogs?referralId='.$referralId.'&statusId='.$statusId;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $list = $curl->get($apiUrl);
            return $list;
        } else {
            return 'false';
        }
    }

    function GenerateSampleCode($request_id){

        $apiUrl=$this->source.'/lab/api/view/model/referrals/id/'.$request_id;
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
        $curl->setOption(CURLOPT_TIMEOUT, 180);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $request = json_decode($curl->get($apiUrl),true);

        $lab = Lab::findOne($request['lab_id']);

        $year = date('Y', strtotime($request['referralDate']));

        $connection= Yii::$app->labdb;
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        
        foreach ($request['samples'] as $samp){
            // var_dump($samp['id']);
            $transaction = $connection->beginTransaction();
            $return="false";
            try {
                $proc = 'spGetNextGenerateReferralSampleCode(:rstlId,:labId,:requestId,:year)';
                $params = [':rstlId'=>$rstlId,':labId'=>$request['lab_id'],':requestId'=>$request_id,':year'=>$year];
                $row = $this->ExecuteStoredProcedureOne($proc, $params, $connection);
                $samplecodeGenerated = $row['GeneratedSampleCode'];
                $samplecodeIncrement = $row['SampleIncrement'];
                $sampleId = $samp['id'];
                // $sample= Sample::find()->where(['sample_id'=>$sampleId])->one();
                
                //insert to tbl_samplecode
                $samplecode = new Samplecode();
                $samplecode->rstl_id = $rstlId;
                $samplecode->reference_num = $request['referralCode'];
                $samplecode->sample_id = $sampleId;
                $samplecode->lab_id = $request['lab_id'];
                $samplecode->number = $samplecodeIncrement;
                $samplecode->year = $year;
                $samplecode->source = 2;
                
                if($samplecode->save())
                {
                    //update samplecode of the sample in the api 
                    $apiUrl=$this->source.'/lab/api/update/model/samples/id/'.$sampleId;
                    //the data to pass

                    $response = $curl->setPostParams([
                            'sampleCode' => $samplecodeGenerated,
                         ])
                         ->get($apiUrl);
                    $transaction->commit();
                    $return="true";
                } else {
                    //error
                    $transaction->rollBack();
                    $samplecode->getErrors();
                    $return="false";
                }
                
                //$transaction->commit();

            } catch (\Exception $e) {
               $transaction->rollBack();
               echo $e->getMessage();
               $return="false";
            } catch (\Throwable $e) {
               $transaction->rollBack();
               $return="false";
               echo $e->getMessage();
            }
            
        }
        return $return;
    }

        /**
     * 
     * @param string $Proc
     * @param array $Params
     * @param CDBConnection $Connection
     * @return array
     */
    public function ExecuteStoredProcedureOne($Proc,array $Params,$Connection){
        if(!isset($Connection)){
           $Connection=Yii::$app->db;
        }
        $Command=$Connection->createCommand("CALL $Proc");
        //Iterate through arrays of parameters
        foreach($Params as $Key=>$Value){
           $Command->bindValue($Key, $Value); 
        }
        $Row=$Command->queryOne();
        return $Row;
    }
    
}

