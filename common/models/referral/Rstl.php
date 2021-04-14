<?php

namespace common\models\referral;

use Yii;

/**
 * This is the model class for table "tbl_rstl".
 *
 * @property int $rstl_id
 * @property int $region_id
 * @property string $name
 * @property string $code
 */
class Rstl extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_rstl';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('referraldb');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['rstl_id', 'region_id', 'name', 'code'], 'required'],
            [['rstl_id', 'region_id'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['code'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'rstl_id' => 'Rstl ID',
            'region_id' => 'Region ID',
            'name' => 'Name',
            'code' => 'Code',
        ];
    }
}
