<?php

use common\models\system\LoginForm;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$fieldOptions1 = [
    'options' => ['class' => 'form-group has-feedback'],
    'inputTemplate' => "{input}<span class='glyphicon glyphicon-envelope form-control-feedback'></span>"
];

$fieldOptions2 = [
    'options' => ['class' => 'form-group has-feedback'],
    'inputTemplate' => "{input}<span class='glyphicon glyphicon-lock form-control-feedback'></span>"
];
?>

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
    }
    .img-responsive {
  display: block;
  max-width: 100%;
  height: auto;
 
}
</style>

<div style="margin:5%;background: linear-gradient(110deg,  #b1d1e4 50%,#3c8dbc 50%);"  >
    <div class="d-flex align-items-center" >



        <div>

            <div class="row">

                <div class="col-sm-6">
                    <div class="jumbotron d-flex align-items-center" >
                        
                        <img src="/images/onelablogonew.png" style="width: 70%;border-radius: 15px 15px;">
                        <h1 class="newtext">
                            E.U.L.I.M.S
                        </h1>
                        <p style="font-family: 'Audiowide';font-size:17px;color:#1b4f72">
                            Enhanced Unified Laboratory Information Management System
                        </p>

                        <p style="font-family: 'Audiowide';font-size:18px;color:#1b4f72">
                            Department of Science and Technology
                        </p>


                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="login-box" >
                        <div class="login-logo">

                        </div>
                        <!-- /.login-logo -->
                        <div class="login-box-body" style="border-radius: 15px;border: 2px solid #616a6b;">
                            <p class="login-box-msg">Sign in to start your session</p>

                            <?php $form = ActiveForm::begin(['id' => 'login-form', 'action' => '/site/userdashboard', 'enableClientValidation' => false]); ?>

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
                            <!--<a href="/site/requestpasswordreset">I forgot my password</a><br>
                            <a href="/site/signup">SignUp</a>-->
                            
                           <a href="">Forgot password?</a><br>
                           <a href="">Request for new password on Administrator </a>
                            
                           
                        </div>
                        <!-- /.login-box-body -->
                    </div>
                </div>
            </div> 

        </div>
    </div>
</div>


