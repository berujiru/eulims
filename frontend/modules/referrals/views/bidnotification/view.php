<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\referral\Bidnotification */

$this->title = $model->bid_notification_id;
$this->params['breadcrumbs'][] = ['label' => 'Bidnotifications', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="bidnotification-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->bid_notification_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->bid_notification_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'bid_notification_id',
            'referral_id',
            'postedby_agency_id',
            'posted_at',
            'recipient_agency_id',
            'seen',
            'seen_date',
        ],
    ]) ?>

</div>
