<?php

namespace common\models\finance;

use Yii;
use common\models\lab\Customer;
use common\components\Functions;
/**
 * This is the model class for table "tbl_orderofpayment".
 *
 * @property int $orderofpayment_id
 * @property int $rstl_id
 * @property string $transactionnum
 * @property int $collectiontype_id
 * @property int $payment_mode_id
 * @property string $order_date
 * @property string $total_amount
 * @property int $customer_id
 * @property string $purpose
 * @property int $receipt_id
 * @property string $invoice_number 
 * @property int $on_account 
 * @property int $payment_status_id
 * @property Billing[] $billings
 * @property CancelledOp[] $cancelledOps
 * @property Collection $collection
 * @property OpBilling[] $opBillings
 * @property Collectiontype $collectiontype
 * @property Paymentmode $paymentMode
 * @property Paymentitem[] $paymentitems
 */
class Op extends \yii\db\ActiveRecord
{
    public $RequestIds;
   
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_orderofpayment';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('financedb');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transactionnum', 'collectiontype_id', 'payment_mode_id', 'order_date', 'customer_id', 'purpose'], 'required'],
	    ['RequestIds', 'required','message' => 'Please select Request.'],
            [['rstl_id', 'collectiontype_id', 'payment_mode_id', 'customer_id', 'receipt_id','on_account','payment_status_id'], 'integer'],
            [['order_date','RequestIds','payment_status_id'], 'safe'],
            [['total_amount'], 'number'],
            [['transactionnum','RequestIds','invoice_number'], 'string', 'max' => 100],
            [['purpose'], 'string', 'max' => 200],
            [['collectiontype_id'], 'exist', 'skipOnError' => true, 'targetClass' => Collectiontype::className(), 'targetAttribute' => ['collectiontype_id' => 'collectiontype_id']],
            [['payment_mode_id'], 'exist', 'skipOnError' => true, 'targetClass' => Paymentmode::className(), 'targetAttribute' => ['payment_mode_id' => 'payment_mode_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'orderofpayment_id' => 'Orderofpayment ID',
            'rstl_id' => 'Rstl ID',
            'transactionnum' => 'Transaction number',
            'collectiontype_id' => 'Collection Type',
            'invoice_number' => 'Invoice Number',
            'payment_mode_id' => 'Payment Mode',
            'on_account' => 'On Account',
            'order_date' => 'OP Date',
            'total_amount' => 'Total Amount',
            'customer_id' => 'Customer Name',
            'purpose' => 'Purpose',
            'receipt_id' => 'Receipt',
            'allow_erratum' => 'Allow Erratum',
        ];
    }

     /**
     * @return \yii\db\ActiveQuery
     */
    public function getOpBillings()
    {
        return $this->hasMany(OpBilling::className(), ['orderofpayment_id' => 'orderofpayment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReceipt()
    {
        return $this->hasOne(Receipt::className(), ['receipt_id' => 'receipt_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCollectiontype()
    {
        return $this->hasOne(Collectiontype::className(), ['collectiontype_id' => 'collectiontype_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPaymentMode()
    {
        return $this->hasOne(Paymentmode::className(), ['payment_mode_id' => 'payment_mode_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPaymentitems()
    {
        return $this->hasMany(Paymentitem::className(), ['orderofpayment_id' => 'orderofpayment_id']);
    }
    
     public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }
    
     public function getCollectionStatus($OpID){
        $func=new Functions();
        $Connection= Yii::$app->financedb;
        $rows=$func->ExecuteStoredProcedureRows("spGetCollectionStatus(:mOpID)", [':mOpID'=> $OpID], $Connection);
        //Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        return $rows;
    }
    /**
    * @return \yii\db\ActiveQuery
    */
    public function getCancelledOps()
    {
       return $this->hasMany(CancelledOp::className(), ['orderofpayment_id' => 'orderofpayment_id']);
    }
}
