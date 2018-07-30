<?php

namespace common\models\lab;

use Yii;

/**
 * This is the model class for table "tbl_sampletype".
 *
 * @property int $id
 * @property string $type
 * @property int $status_id
 *
 * @property LabSampletype[] $labSampletypes
 * @property SampletypeTestname[] $sampletypeTestnames
 */
class Sampletype extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_sampletype';
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
            [['type', 'status_id'], 'required'],
            [['status_id'], 'integer'],
            [['type'], 'string', 'max' => 75],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLabSampletypes()
    {
        return $this->hasMany(LabSampletype::className(), ['sampletypeId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSampletypeTestnames()
    {
        return $this->hasMany(SampletypeTestname::className(), ['sampletype_id' => 'id']);
    }
}
