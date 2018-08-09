<?php

namespace frontend\modules\finance\controllers;

use Yii;
use common\models\finance\Op;
use common\models\finance\Paymentitem;
use common\models\finance\Collection;
use common\models\finance\OpSearch;
use frontend\modules\finance\components\models\Ext_Request as Request;
use common\models\finance\Collectiontype;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use common\components\Functions;
use kartik\editable\Editable;
use yii\helpers\Json;
use yii2mod\alert\Alert;
use frontend\modules\finance\components\models\Ext_Receipt as Receipt;
use yii2tech\spreadsheet\Spreadsheet;
use yii\data\ArrayDataProvider;
use frontend\modules\finance\components\epayment\ePayment;
use yii2tech\spreadsheet\Myspreadsheet;
use frontend\modules\reports\modules\finance\templates\Opspreadsheet;
use yii\data\SqlDataProvider;
/**
 * OrderofpaymentController implements the CRUD actions for Op model.
 */
class OpController extends Controller
{
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
     * Lists all Op models.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new Op();
        $Func=new Functions();
        $Func->CheckRSTLProfile();
        
        $searchModel = new OpSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize=10;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }

    /**
     * Displays a single Op model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    { 
        $model=$this->findModel($id);
         $paymentitem_Query = Paymentitem::find()->where(['orderofpayment_id' => $id]);
         $paymentitemDataProvider = new ActiveDataProvider([
            'query' => $paymentitem_Query,
            'pagination' => [
                'pageSize' => 10,
             ],
         ]);
//         $paymentitemDataProvider = new SqlDataProvider([
//            'sql' => 'SELECT *, eulims_lab.fnGetPaymentitemCustomer(request_id) as CustomerName FROM eulims_finance.tbl_paymentitem WHERE orderofpayment_id='.$id,
//             'pagination' => [
//                'pageSize' => 10,
//             ],
//         ]);
         return $this->render('view', [
            'model' => $model,
            'paymentitemDataProvider' => $paymentitemDataProvider,
        ]);

    }

    /**
     * Creates a new Op model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Op();
        $paymentitem = new Paymentitem();
       // $collection= new Collection();
        $searchModel = new OpSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize=5;
        
        if ($model->load(Yii::$app->request->post())) {
             $transaction = Yii::$app->financedb->beginTransaction();
             $session = Yii::$app->session;
             try  {
                $request_ids=$model->RequestIds;
                 
                $model->total_amount=0;
                $model->rstl_id= Yii::$app->user->identity->profile->rstl_id;//$GLOBALS['rstl_id'];
                $model->transactionnum= $this->Gettransactionnum();
                $model->payment_status_id=1; //unpaid
                
                $ids= implode(',',$model->subsidiary_customer_ids);
                $model->subsidiary_customer_ids=$ids;
                if ($model->payment_mode_id == 6){
                    $model->on_account=1;
                }else{
                    $model->on_account=0;
                }
                
                $model->save();
                
                $transaction->commit();
                //Saving for Paymentitem
                $total_amount=$this->actionSavePaymentitem($request_ids, $model->orderofpayment_id);
                // Update Total OP
                $this->updateTotalOP($model->orderofpayment_id, $total_amount);
                //Check if it is an online payment, if so then post to epayment portal
                if($model->payment_mode_id==5){
                    $ePayment=new ePayment();
                    $ePayment->PostOnlinePayment($id);
                }
                Yii::$app->session->setFlash('success', 'Order of Payment Successfully Created!');
                return $this->redirect(['/finance/op/view?id='.$model->orderofpayment_id]); 
                 //$session->set('savepopup',"executed");
                   
                } catch (Exception $e) {
                    $transaction->rollBack();
                   $session->set('errorpopup',"executed");
                   return $this->redirect(['/finance/op']);
                }
                //-------------------------------------------------------------//
        } 
        $model->order_date=date('Y-m-d');
        if(Yii::$app->request->isAjax){
            return $this->renderAjax('create', [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        }else{
            return $this->render('create', [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        }
        
    }

    /**
     * Updates an existing Orderofpayment model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $paymentitem_model= new Paymentitem();
        
        $query = Paymentitem::find()->where(['orderofpayment_id' => $model->orderofpayment_id]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $session = Yii::$app->session;
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $arr_id =($model->RequestIds);
            $str_request = explode(',', $model->RequestIds);
            $opid=$model->orderofpayment_id;
            $lists=(new Query)
                ->select(['GROUP_CONCAT(`tbl_paymentitem`.`request_id`) as `ids`'])
                ->from('`eulims_finance`.`tbl_paymentitem`')
                 ->where(['orderofpayment_id' => $model->orderofpayment_id])
                ->andWhere(['not', ['paymentitem_id' => $str_request]])
                ->one();
            
            if($lists['ids']){
               $sum = Paymentitem::find()->where(['orderofpayment_id' => $model->orderofpayment_id])
                 ->andWhere(['not', ['paymentitem_id' => $str_request]])
                 ->sum('amount');
               $total=$model->total_amount - $sum;

                //Update to be able to select the request in creating op
                $connection1= Yii::$app->labdb;
                $sql_query1 = $connection1->createCommand('UPDATE tbl_request set posted=0 WHERE request_id IN('.$lists['ids'].')');
                $sql_query1->execute();


                //Delete in payment item
                $connection= Yii::$app->financedb;
                $sql_query = $connection->createCommand('DELETE FROM tbl_paymentitem WHERE paymentitem_id NOT IN('.$arr_id.') AND orderofpayment_id=:op_id');
                $sql_query->bindParam(':op_id',$opid );
                $sql_query->execute();

                $sql_query2 = $connection->createCommand('UPDATE tbl_orderofpayment set total_amount=:total WHERE orderofpayment_id=:op_id');
                $sql_query2->bindParam(':total',$total);
                $sql_query2->bindParam(':op_id',$opid);
                $sql_query2->execute();

            }
            $session->set('updatepopup',"executed");
            return $this->redirect(['/finance/op']);  
         
        } else {
            return $this->renderAjax('update', [
                'model' => $model,
                'dataProvider'=>$dataProvider,
                'paymentitem_model'=>$paymentitem_model
            ]);
        }
        
       
    }
    
     public function actionGetlistrequest($id)
    {
         $dataProvider = new SqlDataProvider([
            'sql' => 'SELECT *, eulims_lab.fnGetCustomer(customer_id) as CustomerName FROM eulims_lab.tbl_request WHERE customer_id IN('.$id.') and request_ref_num IS NOT NULL and payment_status_id <> 2',
        ]);
        if(Yii::$app->request->isAjax){
            return $this->renderAjax('_request', ['dataProvider'=>$dataProvider,'stat'=>0]);
        }
        else{
            return $this->render('_request', ['dataProvider'=>$dataProvider,'stat'=>0]);
        }

    }
    /**
     * Deletes an existing Op model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
  
    /**
     * Finds the Op model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Op the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Op::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    protected function findRequest($requestId)
    {
        if (($model = Request::findOne($requestId)) !== null) {
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
    
     public function Gettransactionnum(){
          $lastyear=(new Query)
            ->select('MAX(transactionnum) AS lastnumber')
            ->from('eulims_finance.tbl_orderofpayment')
            ->one();
          $lastyear=substr($lastyear["lastnumber"],0,4);
          $year=date('Y');
          $year_month = date('Y-m');
          $last_trans_num=(new Query)
            ->select(['count(transactionnum)+ 1 AS lastnumber'])
            ->from('eulims_finance.tbl_orderofpayment')
            ->one();
          $str_trans_num=0;
          if($last_trans_num != ''){
              if($lastyear < $year){
                 $str_trans_num='0001'; 
              }
              else if($lastyear == $year){
                 $str_trans_num=str_pad($last_trans_num["lastnumber"], 4, "0", STR_PAD_LEFT);
              } 
          }
          else{
              $str_trans_num='0001';
          }
        
         $next_transnumber=$year_month."-".$str_trans_num;
         return $next_transnumber;
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
                 $request =$this->findRequest($str_total[$i]);
                 $total+=$request->total;
            }
            echo $total;
        }
     }
     
     public function actionCalculatePaymentitem($id) {
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
     public function getCollectionname($collectionid) {
         $collection_name=(new Query)
            ->select('natureofcollection')
            ->from('eulims_finance.tbl_collectiontype')
            ->where(['collectiontype_id' => $collectionid])
            ->one();
         return $collection_name;
     }
     
       // updating request as posted upon saving order of payment
     public function postRequest($reqID){
         $str_total = explode(',', $reqID);
         $arr_length = count($str_total); 
         for($i=0;$i<$arr_length;$i++){
            Yii::$app->labdb->createCommand()
            ->update('tbl_request', ['posted' => 1], 'request_id= '.$str_total[$i])
            ->execute(); 
         }
     }
     
     public function updateTotalOP($id,$total){
         Yii::$app->financedb->createCommand()
        ->update('tbl_orderofpayment', ['total_amount' => $total], 'orderofpayment_id= '.$id)
        ->execute();       
     }
     
     public function actionCheckCustomerWallet($customerid) {
         $wallet=(new Query)
            ->select('balance')
            ->from('eulims_finance.tbl_customerwallet')
            ->where(['customer_id' => $customerid])
            ->one();
         echo $wallet["balance"];
     }
    
     public function actionListpaymentmode() {
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $id = end($_POST['depdrop_parents']);
            $func=new Functions();
            $list = $func->GetPaymentModeList($id);
            $selected  = null;
            if ($id != null && count($list) > 0) {
                $selected = '';
                foreach ($list as $i => $paymentlist) {
                    $out[] = ['id' => $paymentlist['payment_mode_id'], 'name' => $paymentlist['payment_mode']];
                    if ($i == 0) {
                        $selected = $paymentlist['payment_mode_id'];
                    }
                }
                // Shows how you can preselect a value
                echo Json::encode(['output' => $out, 'selected'=>$selected]);
                return;
            }
        }
        echo Json::encode(['output' => '', 'selected'=>'']);
    }
    
    public function actionPaymentitem($id)
    {
        $model = $this->findModelCheck($checkid);
        $session = Yii::$app->session;
       
        if($model->delete()) {
            Yii::$app->session->setFlash('success','Successfully Removed!');
            return $this->redirect(['/finance/cashier/view-receipt?receiptid='.$model->receipt_id]);
        } else {
            return $model->error();
        }
    }
    
    public function actionUpdateamount(){
        if(Yii::$app->request->post('hasEditable'))
        {
            $id= Yii::$app->request->post('editableKey');
            $paymentitem= Paymentitem::findOne($id);
            $op_id=$paymentitem->orderofpayment_id;
            $out= Json::encode(['output'=> '','message'=> '']);
            $post=[];
            $posted=current($_POST['Paymentitem']);
            $post['Paymentitem']=$posted;
            if($paymentitem->load($post))
            {
                $paymentitem->save();
            }
            $sum = Paymentitem::find()->where(['orderofpayment_id' => $op_id])
                 ->andWhere(['status' => 1])
                 ->sum('amount');
            $this->updateTotalOP($op_id, $sum);
            echo $out;
            return;
        }
    }
    
    public function actionAddPaymentitem($opid,$customerid)
    {
        $op=$this->findModel($opid);
        $customer_id=$op->customer_id;
        $sdc=$op->subsidiary_customer_ids;
        $ids='';
        if ($sdc == ""){
            $ids=$customer_id;
        }
        else{
            $ids=$sdc.','.$customer_id;
        } 
        
        $paymentitemDataProvider = new SqlDataProvider([
            'sql' => 'SELECT *, eulims_lab.fnGetCustomer(customer_id) as CustomerName FROM eulims_lab.tbl_request WHERE customer_id IN('.$ids.') and request_ref_num IS NOT NULL and payment_status_id <> 2',
        ]);
        if(Yii::$app->request->isAjax){
            return $this->renderAjax('_request', ['dataProvider'=> $paymentitemDataProvider,'opid'=>$opid,'stat'=>1]);
        }
        else{
            return $this->render('_request', ['dataProvider'=> $paymentitemDataProvider,'opid'=>$opid,'stat'=>1]);
        }
    }
    public function actionSavePaymentitem($request_ids,$opid){
        $total_amount=0;
        $str_request = explode(',', $request_ids);
        $arr_length = count($str_request);
        for($i=0;$i<$arr_length;$i++){
            $request =$this->findRequest($str_request[$i]);
            $paymentitem = new Paymentitem();
            $paymentitem->rstl_id =Yii::$app->user->identity->profile ? Yii::$app->user->identity->profile->rstl_id : 11;
            $paymentitem->request_id = $str_request[$i];
            $paymentitem->orderofpayment_id = $opid;
            $paymentitem->details =$request->request_ref_num;
            $total=$request->total;
            $amount=$request->getBalance($str_request[$i],$total);
            $total_amount+=$amount;
            $paymentitem->amount = $amount;
            $paymentitem->request_type_id =$request->request_type_id;
            $paymentitem->status=1;//Unpaid
            $paymentitem->save(false); 

        }
        $this->postRequest($request_ids);
        return $total_amount;
    }
    
    protected function findModelReceipt($id)
    {
        if (($model = Receipt::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    public function actionPrintview($id)
    {
      //find the record the testreport
      $op =$this->findModel($id);
      //echo $id;
      //exit;
      $exporter = new Opspreadsheet([
        'model'=>$op,
        ]);
    }
}
