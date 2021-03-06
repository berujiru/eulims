<?php

namespace common\models\finance;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\finance\Accountingcodemapping;

/**
 * AccountingcodemappingSearch represents the model behind the search form of `common\models\finance\Accountingcodemapping`.
 */
class AccountingcodemappingSearch extends Accountingcodemapping
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mapping_id', 'collectiontype_id', 'accountingcode_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Accountingcodemapping::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'mapping_id' => $this->mapping_id,
            'collectiontype_id' => $this->collectiontype_id,
            'accountingcode_id' => $this->accountingcode_id,
        ]);

        return $dataProvider;
    }
}
