<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

$this->title = 'Sign In';

$fieldOptions1 = [
    'options' => ['class' => 'form-group has-feedback'],
    'inputTemplate' => "{input}<span class='glyphicon glyphicon-envelope form-control-feedback'></span>"
];

$fieldOptions2 = [
    'options' => ['class' => 'form-group has-feedback'],
    'inputTemplate' => "{input}<span class='glyphicon glyphicon-lock form-control-feedback'></span>"
];
$this->registerJsFile('/js/bootbox.min.js');
$this->registerJsFile('/js/main.js');

?>
<?php
    Modal::begin([
    'options' => [
        'id' => 'modal',
        'tabindex' => false, // important for Select2 to work properly
        //'class' => 'modal draggable fade',
    ],
    'header' => '<h4 class="modal-title">New Profile</h4>'
    ]);
    echo "<div>";
    //echo "<div id='modalContent' style='margin-left: 5px; padding-bottom:10px;'><img src='/images/ajax-loader.gif' alt=''/></div>";
    echo "<div id='modalContent' style='margin-left: 5px;'><div style='text-align:center;'><img src='/images/img-loader64.gif' alt=''></div></div>";
    echo "<div>&nbsp;</div>";
    echo "</div>";
    Modal::end();
?>
<link href="/css/site.css" rel="stylesheet">
<style>
    /* latin-ext */
    @font-face {
        font-family: 'Audiowide';
        font-style: normal;
        font-weight: 400;
        src: local('Audiowide Regular'), local('Audiowide-Regular'), url('/font/audiowide_latin_ext.woff2') format('woff2');

    }
    /* latin */
    @font-face {
        font-family: 'Audiowide';
        font-style: normal;
        font-weight: 400;
        src: local('Audiowide Regular'), local('Audiowide-Regular'), url('/font/audiowide_latin.woff2') format('woff2');

    }

    .newtext {
        font-size: 72px;
        background: -webkit-linear-gradient( #1b4f72  , #3c8dbc);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font: normal 52px/1 "Audiowide", Helvetica, sans-serif;
        -o-text-overflow: ellipsis;
        text-overflow: ellipsis;
        font-family: 'Audiowide';
        font-size: 60px;
        text-align: center;
    }
      .img-responsive {
  display: block;
  max-width: 100%;
  height: auto;
  margin:auto;
 
}

</style>
<div class="body-content" style="margin:10%;background: linear-gradient(110deg,  #b1d1e4 50%,#3c8dbc 50%);"  >
    <div >
        <div class="d-flex align-items-center" >
            <div>
                <div class="row">

                    <div class="col-sm-6 ">
                        <div class="d-flex align-items-center" >
                            <div class="row" style="margin-top: 10%">
                                <div class="col-sm-12 col-md-offset-0 ">
                                    <img src="/images/onelablogonew.png" style="width: 80%;border-radius: 15px 15px;display: block; height: auto; max-width: 100%;margin:0 auto;">
                                    </div>
                                <div class="col-sm-12 col-md-offset-0 ">
                                    
                                    <h1 class="newtext">
                                        E.U.L.I.M.S
                                    </h1>
                                    <p style="font-family: 'Audiowide';font-size:18px;color:#1b4f72;text-align:center;vertical-align: central">
                                        Enhanced Unified Laboratory Information Management System
                                    </p>

                                    <p style="font-family: 'Audiowide';font-size:18px;color:#1b4f72;text-align:center;vertical-align: central">
                                        Department of Science and Technology
                                    </p> 
                                    <br><br>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="login-box" >
                            <div class="login-logo">

                            </div>
                            <!-- /.login-logo -->
                            <div class="login-box-body" style="border-radius: 15px;border: 2px solid #616a6b;">
                                <p class="login-box-msg">Sign in to start your session</p>

                                <?php $form = ActiveForm::begin(['id' => 'login-form', 'action' => '/site/login', 'enableClientValidation' => false]); ?>

                                <?=
                                        $form
                                        ->field($model, 'email', $fieldOptions1)
                                        ->label(false)
                                        ->textInput(['placeholder' => $model->getAttributeLabel('email')])
                                ?>

                                <?=
                                        $form
                                        ->field($model, 'password', $fieldOptions2)
                                        ->label(false)
                                        ->passwordInput(['placeholder' => $model->getAttributeLabel('password')])
                                ?>

                                <div class="row">
                                    <div class="col-xs-8">
                                        <?= $form->field($model, 'rememberMe')->checkbox() ?>
                                    </div>
                                    <!-- /.col -->
                                    <div class="col-xs-4">
                                        <?= Html::submitButton('Sign in', ['class' => 'btn btn-primary btn-block btn-flat', 'name' => 'login-button']) ?>
                                    </div>                                             
                                    <!-- /.col -->
                                </div>
                                <?php ActiveForm::end(); ?>
                                 <!--a href="#" onclick="ShowModal('Reset Password','/site/requestpasswordreset')">Forgot password?</a><br -->
                                <!-- a href="#" onclick="ShowModal('Sign Up','/site/signup')">Request Administrator for new password</a-->
								<a href="">Forgot password?</a><br>
                                <a href="">Request for new password on Administrator </a>
                            </div>
                            <!-- /.login-box-body -->
                        </div><!-- /.login-box -->
                    </div>
                </div> 

            </div>
        </div>
    </div>

</div>


