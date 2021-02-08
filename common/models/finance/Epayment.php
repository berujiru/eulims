<?php

namespace common\models\finance;

use Yii;

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
 */
class Epayment extends \yii\db\ActiveRecord
{
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
            'mrn' => 'Mrn',
            'merchant_code' => 'Merchant Code',
            'particulars' => 'Particulars',
            'amount' => 'Amount',
            'epp' => 'Epp',
            'status_code' => 'Status Code',
        ];
    }
}
