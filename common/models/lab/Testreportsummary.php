<?php

namespace common\models\lab;

use Yii;

/**
 * This is the model class for table "tbl_testreportsummary".
 *
 * @property int $testreportsum_id
 * @property string $date_created
 * @property int $testreport_id
 * @property int $request_id
 * @property int $lab_id
 * @property int $isontime
 */
class Testreportsummary extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_testreportsummary';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('labdb');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date_created'], 'safe'],
            [['testreport_id', 'request_id', 'lab_id', 'isontime'], 'required'],
            [['testreport_id', 'request_id', 'lab_id', 'isontime'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'testreportsum_id' => 'Testreportsum ID',
            'date_created' => 'Date Created',
            'testreport_id' => 'Testreport ID',
            'request_id' => 'Request ID',
            'lab_id' => 'Lab ID',
            'isontime' => 'Isontime',
        ];
    }
}
