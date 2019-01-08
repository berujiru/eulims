<?php

namespace frontend\modules\finance\controllers;

use Yii;
use common\models\finance\Op;
use frontend\modules\finance\components\models\CollectionSearch;
use common\models\finance\Paymentitem;
use frontend\modules\finance\components\billing\BillingPayment;
use frontend\modules\finance\components\models\Ext_Receipt as Receipt;
use common\models\finance\Receipt as mReceipt;
use common\models\finance\ReceiptSearch;
use common\models\finance\Orseries;
use common\models\finance\Collection;
use common\models\finance\Check;
use common\models\finance\CheckSearch;
use common\models\finance\Deposit;
use common\models\finance\Soa;
use common\models\finance\SoaSearch;
use yii\web\NotFoundHttpException;
use common\models\finance\DepositSearch;
use yii\data\ActiveDataProvider;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use frontend\modules\finance\components\_class\OfficialReceipt;
use common\models\finance\SoaReceipt;
use frontend\modules\finance\components\models\Ext_Request as Request;
use yii2tech\spreadsheet\Spreadsheet;
use yii\data\ArrayDataProvider;
use common\components\Functions;
use yii2tech\spreadsheet\Myspreadsheet;
use frontend\modules\reports\modules\finance\templates\Orspreadsheet;
use frontend\modules\finance\components\models\Model;
use common\models\lab\RequestSearch;

class CashierController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
    //Order of Payment
    public function actionCollection()
    {
        $model =new Op();
        $searchModel = new CollectionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('collection/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }
    public function actionCreateBillingReceipt($id){
        $model = new BillingPayment();
        $nPost=Yii::$app->request->post();
        if ($nPost) {// Saving
            $Billing=(object)$nPost['BillingPayment'];
            $post=(object)$nPost;
            $Receipt=new mReceipt();
            $Receipt->rstl_id=(int)$GLOBALS['rstl_id'];
            $Receipt->terminal_id=(int)$GLOBALS['terminal_id'];
            $Receipt->collection_id=NULL;
            $Receipt->deposit_type_id=(int)$Billing->deposit_type_id;
            $Receipt->or_series_id=(int)$Billing->or_series_id;
            $Receipt->or_number=$Billing->or_number;
            $Receipt->receiptDate=$Billing->receiptDate;
            $Receipt->payment_mode_id=(int)$Billing->payment_mode_id;
            $Receipt->payor=$Billing->payor;
            $Receipt->collectiontype_id=(int)$Billing->collectiontype_id;
            $Receipt->total=(float)$Billing->TotalSoa;
            $Receipt->cancelled=0;
            $Receipt->deposit_id=NULL;
            //Save
            if($Receipt->save()){
                $jsonchecks= json_decode($nPost['check_details'],true);
                foreach ($jsonchecks as $key => $value){
                    if($value['Bank']!=""){
                        $Check=new Check();
                        $Check->bank=$value['Bank'];
                        $Check->receipt_id=$Receipt->receipt_id;
                        $Check->checknumber=$value['Check #'];
                        $Check->checkdate=$value['Check Date'];
                        $chkAmount=str_replace(",","",$value['Amount']);
                        $Check->amount=$chkAmount;
                        $Check->save();
                    }
                }
                $SoaReceipt=new SoaReceipt();
                $SoaReceipt->soa_id=$Billing->soa_id;
                $SoaReceipt->receipt_id=$Receipt->receipt_id;
                $SoaReceipt->save();
                //Update SOA
                $SoaModel= Soa::find()->where(['soa_id'=>$Billing->soa_id])->one();
                $SoaModel->payment_amount=$Billing->total;
                $TotalAmount=$SoaModel->total_amount;
                $SoaModel->total_amount=$TotalAmount-$Billing->total;
                $SoaModel->active=0; //Set to Inactive
                $SoaModel->save();
                Yii::$app->session->setFlash('success', 'Billing Statement Successfully Paid!');
            }else{
                Yii::$app->session->setFlash('danger', 'Billing Statement Payment Unsuccessful!'); 
            }
            $this->redirect("/finance/cashier/billing/");
        }else{
            $model->receiptDate=date('Y-m-d');
            $model->payment_mode_id=1;
            $model->collectiontype_id=1;
            $model->deposit_type_id=1;
            $SoaModel= Soa::find()->where(['soa_id'=>$id])->one();
            $searchModel = new CheckSearch();
            //$searchModel->receipt_id=-1;
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
            if(Yii::$app->request->isAjax){

                return $this->renderAjax('billing/_form', [
                    'model' => $model,
                    'SoaModel'=> $SoaModel,
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
                ]);
            }else{
                return $this->render('billing/_form', [
                    'model' => $model,
                    'SoaModel'=> $SoaModel,
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
                ]);
            }
        }
    } 
    public function actionBilling(){
        $model=new Soa();
        $searchModel = new SoaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('billing/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
        /*$model =new Op();
        $searchModel = new CollectionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('billing/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
         * 
         */
    }
    public function actionViewOp($id)
    { 
        $paymentitem_Query = Paymentitem::find()->where(['orderofpayment_id' => $id]);
        $paymentitemDataProvider = new ActiveDataProvider([
            'query' => $paymentitem_Query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
        return $this->render('collection/view', [
            'model' => $this->findModel($id),
            'paymentitemDataProvider' => $paymentitemDataProvider,
        ]);
    }
    protected function findModel($id)
    {
        if (($model = Op::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    // End of Order of Payment
    
    //-------Receipt
   
    public function actionReceipt()
    {
        $model = new Receipt();
        $searchModel = new ReceiptSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('receipt/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }
     public function actionCreateReceipt($op_id)
    {
        $op_model=$this->findModel($op_id);
        $model = new Receipt();
        $customer_id=$op_model->customer_id;
        if ($model->load(Yii::$app->request->post())) {
            $connection=Yii::$app->financedb;
            $transaction =$connection->beginTransaction();
            $session = Yii::$app->session;
           
             try {
                 
               // $model->getDb()=$connection;
                $model->rstl_id= Yii::$app->user->identity->profile->rstl_id;//$GLOBALS['rstl_id'];
                $model->terminal_id=$GLOBALS['terminal_id'];
                $model->orderofpayment_id=$op_id;
                $this->update_nextor($model->or_series_id,$connection);
                
               // $model->or_series_id=$model->or_series_id;
                $model->or_number=$model->or;
                $model->total=0;
                $model->customer_id=$customer_id;
                $model->cancelled=0;
                $model->save(false);
                 $transaction->commit();
                $this->created_receipt($op_id,$model->receipt_id);
                $this->Savecollection($model->receipt_id,$op_id);
              
                return $this->redirect(['/finance/cashier/view-receipt?receiptid='.$model->receipt_id]); 
             } catch (Exception $e) {
                 $transaction->rollBack();
                 return $e;
             }
        }
            $model->deposit_type_id=1;
            $model->receiptDate=date('Y-m-d');
            $model->payment_mode_id=$op_model->payment_mode_id;
            $model->collectiontype_id=$op_model->collectiontype_id;
        if(Yii::$app->request->isAjax){
           return $this->renderAjax('receipt/_form', [
                'model' => $model,
                'op_model'=> $op_model,
           ]);
        }else{
            return $this->render('receipt/_form', [
                'model' => $model,
                'op_model'=> $op_model,
            ]);
        }
    }
   
    public function actionViewReceipt($receiptid)
    { 
        $receipt=$this->findModelReceipt($receiptid);
      //  $collection_id=$receipt->collection_id;
        //$collection=$this->findModelCollection($collection_id);
       /* $op_model="";
        $op_id=$receipt->orderofpayment_id;
        if($op_id == 0 || $op_id ==""){
            $op_model="";
        }else{
            $op_model=$this->findModel($op_id);
        } */
        
        $paymentitem_Query = Paymentitem::find()->where(['receipt_id' => $receiptid])->andWhere(['status' => 2]);
        if($paymentitem_Query){
            $paymentitemDataProvider = new ActiveDataProvider([
            'query' => $paymentitem_Query,
            'pagination' => [
            'pageSize' => 10,
            ],
            ]);
        }else{
            $paymentitemDataProvider ="";
        }
        
        $check_Query = Check::find()->where(['receipt_id' => $receiptid]);
        if($check_Query){
            $checkDataProvider = new ActiveDataProvider([
            'query' => $check_Query,
            'pagination' => [
            'pageSize' => 10,
            ],
            ]);
        }else{
            $checkDataProvider ="";
        }
        
        return $this->render('receipt/view', [
            'model' => $receipt,
            'paymentitemDataProvider' => $paymentitemDataProvider,
            'check_model'=>$checkDataProvider,
            'receipt'=>$receipt,
        ]);
    }
    protected function findModelReceipt($id)
    {
        if (($model = Receipt::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
   
    public function actionNextor($id)
    {
        if(!empty($id))
        {
            $model = Orseries::findOne($id);
            $nextOR=$model->nextor;
            $endOR=$model->endor;
            if($nextOR > $endOR){
                echo JSON::encode(['nxtOR'=>'Please update O.R Series','success'=>false]);
            }
            else{
                echo JSON::encode(['nxtOR'=>$nextOR,'success'=>true]);
            }
            
        } else {
            echo JSON::encode(['nxtOR'=>"OR Series not selected.",'success'=>false]);
        }
    }
    public function update_nextor($orseries_id,$connection){
       $sql_update='UPDATE tbl_orseries SET nextor=nextor +1 WHERE or_series_id=:orseries_id';
       $Command=$connection->createCommand($sql_update);
       $Command->bindValue(':orseries_id',$orseries_id);
       $Command->execute();
    }
     
    public function created_receipt($opID,$receiptid){
        if(!empty($opID))
        {
            Yii::$app->financedb->createCommand()
            ->update('tbl_orderofpayment', ['receipt_id' => $receiptid], 'orderofpayment_id= '.$opID)
            ->execute(); 
            
        }
    }
    //End of Receipt
     //------------DEPOSIT
    public function actionDeposit()
    {
        $model = new Deposit();
        $searchModel = new DepositSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('deposit/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }
    public function actionAddDeposit()
    {
        $model = new Deposit();
        
        if ($model->load(Yii::$app->request->post())) {
            $session = Yii::$app->session;
            try  {
                $model->rstl_id=$GLOBALS['rstl_id'];
                $model->start_or=$this->findModelReceipt($model->start_or)->or_number;
                $model->end_or=$this->findModelReceipt($model->end_or)->or_number;
                $model->save(false);
                $this->update_receipt_depositid($model->start_or, $model->end_or, $model->deposit_id);
                $session->set('savepopup',"executed");
                 return $this->redirect(['/finance/cashier/deposit/']);
            } catch (Exception $e) {
                return $e;
            }
        }
        $model->deposit_date=date('Y-m-d');
        if(Yii::$app->request->isAjax){
            return $this->renderAjax('deposit/_form', [
                'model' => $model,
            ]);
        }else{
            return $this->render('deposit/_form', [
                'model' => $model,
            ]);
        }
    }
    
    public function actionListorseries() 
    {
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $id = end($_POST['depdrop_parents']);
            $list = Orseries::find()->all();
            $selected  = null;
            if ($id != null && count($list) > 0) {
                $selected = '';
                foreach ($list as $i => $or) {
                    $out[] = ['id' => $or['or_series_id'], 'name' => $or['or_series_name']];
                    if ($i == 0) {
                        $selected = $or['or_series_id'];
                    }
                }
                echo Json::encode(['output' => $out, 'selected'=>$selected]);
                return;
            }
        }
        echo Json::encode(['output' => '', 'selected'=>'']);
    }
    
    public function actionStartOr() {
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $ids = $_POST['depdrop_parents'];
            $deposit_type_id = empty($ids[0]) ? null : $ids[0];
            $or_series_id= empty($ids[1]) ? null : $ids[1];
            $list = Receipt::find()->andWhere(['or_series_id'=>$or_series_id])->andWhere(['deposit_type_id'=>$deposit_type_id])->andWhere(['deposit_id'=>null])->asArray()->all();
            $selected  = null;
            if ($ids != null && count($list) > 0) {
                $selected = '';
                foreach ($list as $i => $or) {
                    $out[] = ['id' => $or['receipt_id'], 'name' => $or['or_number']];
                    if ($i == 0) {
                        $selected = $or['receipt_id'];
                    }
                }
                echo Json::encode(['output' => $out, 'selected'=>$selected]);
                return;
            }
        }
        echo Json::encode(['output' => '', 'selected'=>'']);
    }
    
    public function actionEndOr() {
        $out = [];
        if (isset($_POST['depdrop_parents']) <> '') {
            $id = end($_POST['depdrop_parents']);
            $receipt=$this->findModelReceipt($id);
             $list=(new Query)
            ->select('receipt_id,or_number')
            ->from('eulims_finance.tbl_receipt')
            ->where(['deposit_type_id' => $receipt->deposit_type_id])
            ->andWhere(['or_series_id' => $receipt->or_series_id]) 
            ->andWhere(['deposit_id'=>null]) 
            ->andWhere(['>=', 'or_number',$receipt->or_number])
            ->all();
            
            $selected  = null;
            if ($id != null && count($list) > 0) {
                $selected = '';
                foreach ($list as $i => $test) {
                    $out[] = ['id' => $test['receipt_id'], 'name' => $test['or_number']];
                    if ($i == 0) {
                        $selected = $test['receipt_id'];
                    }
                }
                echo Json::encode(['output' => $out, 'selected'=>'']);
                return;
            }
        }
        else{
            echo Json::encode(['output' => '', 'selected'=>'']);
            exit;
        }
    }
   public function actionCalculateTotalDeposit($id,$endor) {
        $total = 0;
        $start_receipt=$this->findModelReceipt($id);
        $start_or=$start_receipt->or_number;
        $deposit_type_id= $start_receipt->deposit_type_id;
        $or_series_id=$start_receipt->or_series_id;
        $end_receipt=$this->findModelReceipt($endor);
        $end_or=$end_receipt->or_number;
        
        $sum=(new Query)
            ->select('receipt_id,or_number,total')
            ->from('eulims_finance.tbl_receipt')
            ->where(['deposit_type_id' => $deposit_type_id])
            ->andWhere(['or_series_id' => $or_series_id]) 
            ->andWhere(['deposit_id'=>null]) 
            ->andWhere(['between', 'or_number',$start_or,$end_or])
            ->sum('total');
        if($id == '' ){
            echo '';
        }
        else{
            echo $sum;
        }
     }
    
     public function update_receipt_depositid($startor,$endor,$depositid)
     {
        if(!empty($startor) && !empty($endor) )
        {
            Yii::$app->financedb->createCommand()
            ->update('tbl_receipt', ['deposit_id' => $depositid], ['between', 'or_number',$startor,$endor])
            ->execute(); 
            
        }
     }
    //------------------END of DEPOSIT
    //------------COLLECTION
    protected function findModelCollection($id)
   {
       if (($model = Collection::findOne($id)) !== null) {
           return $model;
       } else {
           throw new NotFoundHttpException('The requested page does not exist.');
       }
   }
    protected function findPaymentitem($id)
   {
       if (($model = Paymentitem::findOne($id)) !== null) {
           return $model;
       } else {
           throw new NotFoundHttpException('The requested page does not exist.');
       }
   }
    public function actionAddCollection($opid,$receiptid)
    {
        $op=$this->findModel($opid);
        $collection_id=$op->collection->collection_id;
        $paymentitem_Query = Paymentitem::find()->where(['orderofpayment_id' => $opid])->andWhere(['status' => 1]);
        $paymentitemDataProvider = new ActiveDataProvider([
            'query' => $paymentitem_Query,
        ]);
        
        if(Yii::$app->request->isAjax){
            return $this->renderAjax('collection/_paymentitem', ['dataProvider'=> $paymentitemDataProvider,'receiptid'=>$receiptid,'collection_id'=>$collection_id]);
        }
        else{
            return $this->render('collection/_paymentitem', ['dataProvider'=> $paymentitemDataProvider,'receiptid'=>$receiptid,'collection_id'=>$collection_id]);
        }
    }
  
     public function Savecollection($receiptid,$orderofpayment_id){

        $op_id=$orderofpayment_id;
        $op_model=$this->findModel($op_id);
        $amount=$op_model->total_amount;
        $receipt_model=$this->findModelReceipt($receiptid);
//        var_dump($receipt_model);
//        exit;
//        
            Yii::$app->financedb->createCommand()
              ->update('tbl_paymentitem', ['status' => 2,'receipt_id'=>$receiptid], 'orderofpayment_id= '.$op_id)
              ->execute(); 
               
            
          
            //$receipt_total_amount=$receipt_total+$total;
             Yii::$app->financedb->createCommand()
                    ->update('tbl_receipt', ['total' => $amount], 'receipt_id= '.$receiptid)
                    ->execute();
             
           
            Yii::$app->financedb->createCommand()
               ->update('tbl_orderofpayment', ['payment_status_id' => 2], 'orderofpayment_id= '.$op_id)
               ->execute();
           
            
            //Update Request
            $lists=(new Query)
                ->select(['GROUP_CONCAT(`tbl_paymentitem`.`request_id`) as `ids`'])
                ->from('`eulims_finance`.`tbl_paymentitem`')
                 ->where(['orderofpayment_id' => $op_id])
                ->andWhere(['status' => 2])
                ->one();
           // var_dump($lists);
           // exit;
             if($lists){
                $str_request = explode(',', $lists['ids']);
                $arr_length = count($str_request); 
                for($i=0;$i<$arr_length;$i++){
                    
                    $request =$this->findRequestNonLab($str_request[$i]);
                    if($request){
                        $total=$request->total;
                        $amount=$request->getBalance($str_request[$i],$total);
                       // echo $amount;
                        //exit;
                        if($amount == 0){
                             Yii::$app->labdb->createCommand()
                                ->update('tbl_request', ['posted' => 1,'payment_status_id'=>2], 'request_id= '.$str_request[$i])
                                ->execute(); 
                        }
                        else{
                            Yii::$app->labdb->createCommand()
                                ->update('tbl_request', ['posted' => 0,'payment_status_id'=>3], 'request_id= '.$str_request[$i])
                                ->execute(); 
                        }   
                    }
                    
                }
             } 
           
             //End Update Request
             //finance/cashier/view-receipt
            return $this->redirect(['/finance/cashier/view-receipt?receiptid='.$receiptid]); 
        
    }
     public function actionCalculateTotal($id) {
        $total = 0;
        if($id == '' ){
            echo $total;
        }
        else{
            $str_total = explode(',', $id);
            $arr_length = count($str_total); 
            for($i=0;$i<$arr_length;$i++){
                $paymentitem =$this->findPaymentitem($str_total[$i]);
                $total+=$paymentitem->amount;
            }
            echo $total;
        }
     }
    //-------------END of COLLECTION
     //------CHECK
    public function actionAddCheck($receiptid)
    {
         $model = new Check();
         $receipt=$this->findModelReceipt($receiptid);
         $customer_id=$receipt->customer_id;
         $total_collection=$receipt->total;
         $sum = Check::find()->where(['receipt_id'=>$receiptid])->sum('amount');
         $func= new Functions();
         
         if ($model->load(Yii::$app->request->post())) {
            $session = Yii::$app->session;
            
            try  {
                $model->receipt_id=$receiptid;
                if($model->save()){
                   if ($model->amount > $total_collection){
                       Yii::$app->session->setFlash('info','Excess amount will be credited to customer wallet!');
                       $total= $model->amount - $total_collection;
                       $source= "From Receipt #:".$receipt->or_number.", Check #:".$model->checknumber;
                       $func->SetWallet($customer_id, $total, $source,0);
                   } 
                }
                // Yii::$app->session->setFlash('success','Successfully Saved!');
                return $this->redirect(['/finance/cashier/view-receipt?receiptid='.$receiptid]);
             } catch (Exception $e) {
                 return $e;
             }
         }
         $model->checkdate=date('Y-m-d');
         if(Yii::$app->request->isAjax){
            return $this->renderAjax('check/_form', [
                'model' => $model,
                'check_amount'=>$sum,
                'total_collection'=>$total_collection,
                
            ]);
         }else{
            return $this->render('check/_form', [
                'model' => $model,
                'check_amount'=>$sum,
                'total_collection'=>$total_collection,
            ]);
         }
    }
    
   protected function findModelCheck($checkid)
   {
       if (($model = Check::findOne($checkid)) !== null) {
           return $model;
       } else {
           throw new NotFoundHttpException('The requested page does not exist.');
       }
   }
     //-----------END of CHECK
    public function actionReports()
    {
        return $this->render('reports/index');
    }
    
   public function actionRemoveCheck($checkid)
    {
        $model = $this->findModelCheck($checkid);
        $session = Yii::$app->session;
       
        if($model->delete()) {
            Yii::$app->session->setFlash('success','Successfully Removed!');
            return $this->redirect(['/finance/cashier/view-receipt?receiptid='.$model->receipt_id]);
        }else {
            return $model->error();
        }
    }
    
    public function actionPrintOr() {
        $or=New OfficialReceipt();
        $or->PrintPDF();
    }
    
    protected function findRequest($requestId)
    {
        if (($model = Request::findOne($requestId)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    protected function findRequestNonLab($requestId)
    {
      return $model = Request::findOne($requestId);
    }
    
    public function actionPrintview($id)
    {
      $receipt =$this->findModelReceipt($id);
      $exporter = new Orspreadsheet([
        'model'=>$receipt,
        ]);
    }
    
     public function actionAddReceipt()
    {
        $model = new Receipt();
        
        if ($model->load(Yii::$app->request->post())) {
            $connection=Yii::$app->financedb;
            $transaction =$connection->beginTransaction();
            $session = Yii::$app->session;
           
             try {
                 
               // $model->getDb()=$connection;
                $model->rstl_id= Yii::$app->user->identity->profile->rstl_id;//$GLOBALS['rstl_id'];
                $model->terminal_id=$GLOBALS['terminal_id'];
                $model->orderofpayment_id=0;
                $this->update_nextor($model->or_series_id,$connection);
                
               // $model->or_series_id=$model->or_series_id;
                $model->or_number=$model->or;
                $model->total=0;
                $model->customer_id=0;
                $model->cancelled=0;
                $model->save(false);
                 $transaction->commit();
                $this->created_receipt(0,$model->receipt_id);
                $this->Savecollection($model->receipt_id,"");
              
                return $this->redirect(['/finance/cashier/view-receipt?receiptid='.$model->receipt_id]); 
             } catch (Exception $e) {
                 $transaction->rollBack();
                 return $e;
             }
        }
            $model->deposit_type_id=1;
            $model->receiptDate=date('Y-m-d');
            $model->payment_mode_id=1;
            $model->collectiontype_id=1;
        if(Yii::$app->request->isAjax){
           return $this->renderAjax('receipt/_form', [
                'model' => $model,
                'op_model'=> "",
           ]);
        }else{
            return $this->render('receipt/_form', [
                'model' => $model,
                'op_model'=> "",
            ]);
        }
    }
    
    public function actionAddpaymentitem($collectiontype_id,$receiptid) {
       
        $paymentitem = [new Paymentitem()];
        
         if (Yii::$app->request->post()) {
           
            Model::loadMultiple($paymentitem, Yii::$app->request->post());
              
            $paymentitem = Model::createMultiple(Paymentitem::classname());
            
            $valid = Model::validateMultiple($paymentitem);
            
                $connection= Yii::$app->financedb;
                $transaction = $connection->beginTransaction();
                $connection->createCommand('set foreign_key_checks=0')->execute();
                try {
                    $posts=Yii::$app->request->post();
                
                     $total=0;
                       $posts=$posts["Paymentitem"];
                        foreach ($posts as $post) {
                            $paymentitems = new Paymentitem();
                            $paymentitems->rstl_id =$GLOBALS['rstl_id'];
                            $paymentitems->request_id = 0;
                            $paymentitems->orderofpayment_id = 0;
                            $paymentitems->details=$post['details'];
                            $paymentitems->amount=$post['amount'];
                            $paymentitems->status=2; //Paid
                            $paymentitems->receipt_id=$receiptid;
                            $paymentitems->save(false);
                            $total+=$post['amount'];
                        }
                       $transaction->commit();
                       $this->UpdateTotalReceipt($receiptid);//update total of receipt
                       
                       Yii::$app->session->setFlash('success','Successfully Saved');
                       return $this->redirect(['/finance/cashier/view-receipt', 'receiptid' => $receiptid]);
                
                } catch (Exception $e) {
                    Yii::$app->session->setFlash('danger','Transaction failed');
                    $transaction->rollBack();
                }
       }
       else{
          if($collectiontype_id == 1 || $collectiontype_id == 2){
                $searchModel = new RequestSearch();
                $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
                $dataProvider->pagination->pageSize=10;
                 /* $query = Request::find()->where(['not', ['posted' => 1]]);
                    $dataProvider = new ActiveDataProvider([
                        'query' => $query,
                    ]); */
                  return $this->renderAjax('receipt/_request', ['dataProvider'=>$dataProvider, 'searchModel' => $searchModel]);
          }else{
              return $this->renderAjax('receipt/_form_paymentitem', [
                'paymentitem' => (empty($paymentitem)) ? [new Paymentitem()] : $paymentitem
                ]);
          }
       } 
    }
    
    public function UpdateTotalReceipt($receiptid) {
        $sum = Paymentitem::find()->where(['receipt_id' => $receiptid])
                 ->sum('amount');
        
        Yii::$app->financedb->createCommand()
            ->update('tbl_receipt', ['total'=>$sum], 'receipt_id= '.$receiptid)
            ->execute();
        
    }
}
