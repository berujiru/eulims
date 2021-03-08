<?php
/* @var $this yii\web\View */

$this->title = 'Finance';
$this->params['breadcrumbs'][] = $this->title;
?>

<style type="text/css">

    .imgHover:hover{
        border-radius: 15px;
        box-shadow: 0 0 0 4pt #3c8dbc;
        transition: box-shadow 0.5s ease-in-out;
    }

    @media (min-width: 768px) {
        .seven-cols .col-md-1,
        .seven-cols .col-sm-1,
        .seven-cols .col-lg-1  {
            width: 100%;
        }
    }

    @media (min-width: 992px) {
        .seven-cols .col-md-1,
        .seven-cols .col-sm-1,
        .seven-cols .col-lg-1 {
            width: 14.285714285714285714285714285714%;
        }
    }

    @media (min-width: 1200px) {
        .seven-cols .col-md-1,
        .seven-cols .col-sm-1,
        .seven-cols .col-lg-1 {
            width: 14.285714285714285714285714285714%;
        }
    }
</style>

<div class="Lab-default-index">
    <div class="body-content">

        <div class="box box-primary color-palette-box" style="padding:0px">
            <div class="box-header" style="background-color: #fff;color:#000" data-widget="collapse">
                <h3 class="box-title" data-widget="collapse"><i class="fa fa-tag"></i>Finance</h3>
                <div class="box-tools pull-right" >
                    <button type="button" style="color:#000" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>      
            <div class="box-body">


                <div class="row seven-cols">
                    <div class="col-md-1">
                        <div  style="padding-top: 1px;padding-bottom: 1px;display:block;text-align: center">
                            <a href="/finance/customerwallet" title="Customer Wallet"><img class="imgHover" src="/images/customerwalletsquare.png" style="height:120px;width: 120px" ></a>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div  style="padding-top: 1px;padding-bottom: 1px;display:block;text-align: center">
                            <a href="/finance/op" title="Order of Payment"><img class="imgHover" src="/images/orderlabsquare.png" style="height:120px;width: 120px"></a>
                        </div>
                    </div>
    
                    <div class="col-md-1"> <div  style="padding-top: 1px;padding-bottom: 1px;display:block;text-align: center">
                            <a href="/finance/cashier/receipt" title="Receipt"><img class="imgHover" src="/images/receiptsquare.png" style="height:120px;width: 120px" ></a>
                        </div></div>
                    <div class="col-md-1"><div  style="padding-top: 1px;padding-bottom: 1px;display:block;text-align: center">
                            <a href="/finance/cashier/deposit" title="Deposit"><img class="imgHover" src="/images/depositsquare.png" style="height:120px;width: 120px"></a>
                        </div></div>
                    <div class="col-md-1"><div  style="padding-top: 1px;padding-bottom: 1px;display:block;text-align: center">
                            <a href="/finance/accounting/op" title="Order of Payment(Non-Lab)"><img class="imgHover" src="/images/ordernonlabsquare.png" style="height:120px;width: 120px" ></a>
                        </div></div>
                </div>




            </div>
            <!-- /.box-body -->
        </div>


    </div>
</div>
