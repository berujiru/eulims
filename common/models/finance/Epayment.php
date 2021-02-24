<?php

namespace common\models\finance;

use Yii;
use common\models\finance\Op;
use common\models\finance\Paymentitem;
use common\models\lab\Customer;
/**
 * This is the model class for table "tbl_epayment".
 *
 * @property int $epayment_id
 * @property string $mrn
 * @property string $merchant_code
 * @property string $particulars
 * @property double $amount
 * @property string $epp
 * @property string $status_code
 * @property string $op_id
 */
class Epayment extends \yii\db\ActiveRecord
{

    //setting default merchant code for this epayment
    public $merchant_code = "merchant_rix";
    public $test_key = "d0stro9";
    public $hash = "";
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_epayment';
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
            [['mrn', 'merchant_code', 'amount'], 'required'],
            [['particulars'], 'string'],
            [['amount'], 'number'],
            [['mrn', 'epp'], 'string', 'max' => 100],
            [['merchant_code'], 'string', 'max' => 50],
            [['status_code'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'epayment_id' => 'Epayment ID',
            'mrn' => 'Merchant Reference Number',
            'merchant_code' => 'Merchant Code',
            'particulars' => 'Particulars',
            'amount' => 'Amount',
            'epp' => 'Epp',
            'status_code' => 'Status Code',
            'op_id'=> 'OP'
        ];
    }

    public function generate($op_id){
        $date = date('ymd');
        //get the number of request as paymentitem of the op 2 digits
        $numres = Paymentitem::find()->where(['orderofpayment_id'=>$op_id])->count();
        $numres=sprintf("%02d", $numres);
        //get the transaction number of this op
        $op = Op::findOne($op_id);
        $split = explode('-', $op->transactionnum); 
        $this->mrn= $date.$numres.$split[1].$split[2];

        $this->amount=$op->total_amount;

        //get the reference number under this op in the paymentitem
        $items = Paymentitem::find()->where(['orderofpayment_id'=>$op_id])->all();
        $references= "";
        foreach ($items as $item) {
            $references .= $item->details;   
        } 

        //get the customer details using the OP->customer_id
        $customerdetail = Customer::findOne($op->customer_id);
        $this->particulars="Transaction_type=Testing and Calibration;Reference Number=$references;Payor Name=$customerdetail->customer_name;Email Address=$customerdetail->email;";
    }

    //only calls this function when sending to prevent end users to see the hash in the form
    public function generatehash(){
        //merchantcode

        $parts = explode(";", $this->particulars);
        //customer name
        $customerpart= explode("=", $parts[2]);
        $customername = $customerpart[1]; 
        //merxchantcode + payor + mrn + amount + test key
        $sumup = $this->merchant_code.$customername.$this->mrn.$this->amount.$this->test_key;
        $this->hash = strtolower(md5($sumup));
    }
}
