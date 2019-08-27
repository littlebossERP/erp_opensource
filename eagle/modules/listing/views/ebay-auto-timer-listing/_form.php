<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoTimerListing */
/* @var $form yii\widgets\ActiveForm */
$gmtarr=array(
        '-12'=>'(GMT-12:00)Eniwetok, Kwajalein',
        '-11'=>'(GMT-11:00)Midway Island, Samoa',
        '-10'=>'(GMT-10:00)Hawaii',
        '-9'=>'(GMT-09:00)Alaska',
        '-8'=>'(GMT-08:00)Pacific Time (US & Canada), Tijuana',
        '-7'=>'(GMT-07:00)Mountain Time (US & Canada), Arizona',
        '-6'=>'(GMT-06:00)Central Time (US & Canada), Mexico City',
        '-5'=>'(GMT-05:00)Eastern Time (US & Canada), Bogota, Lima, Quito',
        '-4'=>'(GMT-04:00)Atlantic Time (Canada), Caracas, La Paz',
        '-3'=>'(GMT-03:00)Brassila, Buenos Aires, Georgetown, Falkland Is',
        '-2'=>'(GMT-02:00)Mid-Atlantic, Ascension Is., St. Helena',
        '-1'=>'(GMT-01:00)Azores, Cape Verde Islands',
        '+0'=>'(GMT+00:00)Casablanca,Dublin, Edinburgh, London, Lisbon, Monrovia',
        '+1'=>'(GMT+01:00)Amsterdam, Berlin, Brussels, Madrid, Paris, Rome',
        '+2'=>'(GMT+02:00)Cairo, Helsinki, Kaliningrad, South Africa',
        '+3'=>'(GMT+03:00)Baghdad, Riyadh, Moscow, Nairobi',
        '+4'=>'(GMT+04:00)Abu Dhabi, Baku, Muscat, Tbilisi',
        '+5'=>'(GMT+05:00)Ekaterinburg, Islamabad, Karachi, Tashkent',
        '+6'=>'(GMT+06:00)Almaty, Colombo, Dhaka, Novosibirsk',
        '+7'=>'(GMT+07:00)Bangkok, Hanoi, Jakarta',
        '+8'=>'(GMT+08:00)Beijing, Hong Kong, Perth, Singapore, Taipei',
        '+9'=>'(GMT+09:00)Osaka, Sapporo, Seoul, Tokyo, Yakutsk',
        '+10'=>'(GMT+10:00)Canberra, Guam, Melbourne, Sydney, Vladivostok',
        '+11'=>'(GMT+11:00)Magadan, New Caledonia, Solomon Islands',
        '+12'=>'(GMT+12:00)Auckland, Wellington, Fiji, Marshall Island',
        );
$gmtcnArr=array(
        '+12'=>'(GMT-12:00)恩尼托托克,夸贾耶林',
        '+11'=>'(GMT-11:00)中途岛,萨摩亚',
        '+10'=>'(GMT-10:00)夏威夷',
        '+9'=>'(GMT-09:00)阿拉斯加',
        '+8'=>'(GMT-08:00)太平洋时间(美国和加拿大),蒂华纳',
        '+7'=>'(GMT-07:00)山区时间(美国和加拿大),亚利桑那州',
        '+6'=>'(GMT-06:00)中部时间(美国和加拿大),墨西哥城',
        '+5'=>'(GMT-05:00)东部时间(美国和加拿大),波哥大,利马,基多',
        '+4'=>'(GMT-04:00)大西洋时间(加拿大),加拉加斯,拉巴斯',
        '+3'=>'(GMT-03:00)布拉西拉,布宜诺斯艾利斯,乔治敦,福克兰是',
        '+2'=>'(GMT-02:00)中大西洋,阿森松岛,圣赫勒拿岛',
        '+1'=>'(GMT-01:00)亚速尔群岛,佛得角群岛',
        '+0'=>'(GMT+00:00)卡萨布兰卡,都柏林,爱丁堡,伦敦,里斯本,蒙罗维亚',
        '-1'=>'(GMT+01:00)阿姆斯特丹,柏林,布鲁塞尔,马德里,巴黎,罗马',
        '-2'=>'(GMT+02:00)开罗,赫尔辛基,加里宁格勒,南非',
        '-3'=>'(GMT+03:00)巴格达,利雅得,莫斯科,内罗毕',
        '-4'=>'(GMT+04:00)阿布扎比,巴库,马斯喀特,第比利斯',
        '-5'=>'(GMT+05:00)叶卡捷琳堡,伊斯兰堡,卡拉奇,塔什干',
        '-6'=>'(GMT+06:00)阿拉木图,科伦坡,达卡,新西伯利亚',
        '-7'=>'(GMT+07:00)曼谷,河内,雅加达',
        '-8'=>'(GMT+08:00)北京,香港,珀斯,新加坡,台北',
        '-9'=>'(GMT+09:00)大阪,札幌,首尔,东京,雅库茨克',
        '-10'=>'(GMT+10:00)堪培拉,关岛,墨尔本,悉尼,符拉迪沃斯托克',
        '-11'=>'(GMT+11:00)马加丹,新喀里多尼亚,所罗门群岛',
        '-12'=>'(GMT+12:00)奥克兰,惠灵顿,斐济,马绍尔岛',);
$hourarr=array(
        '00'=>'0',
        '01'=>'1',
        '02'=>'2',
        '03'=>'3',
        '04'=>'4',
        '05'=>'5',
        '06'=>'6',
        '07'=>'7',
        '08'=>'8',
        '09'=>'9',
        '10'=>'10',
        '11'=>'11',
        '12'=>'12',
        '13'=>'13',
        '14'=>'14',
        '15'=>'15',
        '16'=>'16',
        '17'=>'17',
        '18'=>'18',
        '19'=>'19',
        '20'=>'20',
        '21'=>'21',
        '22'=>'22',
        '23'=>'23',);
$minarr=array(
        '0'=>'0',
        // '5'=>'5',
        // '10'=>'10',
        '15'=>'15',
        // '20'=>'20',
        // '25'=>'25',
        '30'=>'30',
        // '35'=>'35',
        // '40'=>'40',
        '45'=>'45',
        // '50'=>'50',
        // '55'=>'55'
        );

?>

<div class="ebay-auto-timing-listing-form col-sm-12" style="font-size:14px">
    <?php $form = ActiveForm::begin(); ?>
    <!-- No.1-filed 启用定时 -->
    <div class='form-group'>
        <?php echo Html::hiddenInput('type','save') ?>
        <?php echo Html::hiddenInput('EbayAutoTimerListing[draft_id]',
            $model->isNewRecord ?$draft_model->mubanid:$model->draft_id) ?>
        <?php echo Html::hiddenInput('EbayAutoTimerListing[selleruserid]',
            $model->isNewRecord ?$draft_model->selleruserid:$model->selleruserid) ?>
        <?php if (!$model->isNewRecord): ?>
            <?php echo Html::hiddenInput('id',$model->id) ?>
        <?php endif ?>
        <div class="row">
            <div class="col-lg-3">
            <?php echo Html::label('启用定时',"ebayautotimerlisting-status",
                                    [
                                    'class'=>'control-label col-md-offset-2',
                                    'style'=>'padding:12px;font-weight:900;'
                                    ]) ?>
            </div>
            <div class="col-lg-4">
            <?php echo Html::radioList('EbayAutoTimerListing[status]',
                                $model->isNewRecord ?1:$model->status,
                                ['0'=>'暂停','1'=>'开启'],
                                [
                                // 'class'=>'',
                                'style'=>'vertical-align: middle;padding:12px',
                                'itemOptions' => ['class' => 'radio-inline'],
                                ]) ?>
            </div>
        </div>
    </div>
    <!-- No.2-filed 时区选择 -->
    <div class='form-group'>
        <div class="row">
            <div class="col-lg-3">
            <?php echo Html::label('时区选择',"ebayautotimerlisting-set_gmt",
                                    [
                                    'class'=>'control-label col-md-offset-2',
                                    'style'=>'vertical-align: middle;padding:12px;font-weight:900;'
                                    ]) ?>
            </div>
            <div class="col-lg-9">
            <?php echo Html::dropDownList('EbayAutoTimerListing[set_gmt]',
                                $model->isNewRecord ?'-8':$model->set_gmt,
                                $gmtcnArr,
                                [
                                'id'=>'ebayautotimerlisting-set_gmt',
                                'class'=>'form-control'
                                ]) ?>
            </div>
        </div>
    </div>
    <!-- No.3-filed 刊登时间 -->
    <div class='form-group'>
        <div class="row">
            <div class='col-lg-3'>
            <?php echo Html::label('刊登时间',"ebayautotimerlisting-set_date",
                                    [
                                    'class'=>'control-label col-md-offset-2',
                                    'style'=>'vertical-align: middle;padding:12px;font-weight:900;'
                                    ]) ?>
            </div>
            <div class="col-lg-3">
            <?php echo Html::input('date','EbayAutoTimerListing[set_date]',
                                $model->isNewRecord ?date('Y-m-d'):$model->set_date,
                                [
                                'id'=>"ebayautotimerlisting-set_date",
                                'class'=>'form-control',
                                'style'=>'padding:5px'
                                ]) ?>
            </div>
            <div class="col-lg-1">
            <?php echo Html::label('时',"ebayautotimerlisting-set_hour",
                                    [
                                    'class'=>'control-label',
                                    'style'=>'vertical-align: middle;padding:12px'
                                    ]) ?>
            </div>
            <div class="col-lg-2">
            <?php echo Html::dropDownList('EbayAutoTimerListing[set_hour]',
                                        $model->isNewRecord ?date('H'):$model->set_hour,
                                        $hourarr,
                                        [
                                        'id'=>'ebayautotimerlisting-set_hour',
                                        'class'=>'form-control'
                                        ]) ?>
            </div>
            <div class="col-lg-1">
            <?php echo Html::label('分',"ebayautotimerlisting-set_min",
                                    [
                                    'class'=>'control-label',
                                    'style'=>'vertical-align: middle;padding:12px'
                                    ]) ?>
            </div>
            <div class="col-lg-2">
            <?php echo Html::dropDownList('EbayAutoTimerListing[set_min]','请选择',
                                        $minarr,
                                        [
                                        'id'=>'ebayautotimerlisting-set_min',
                                        'class'=>'form-control'
                                        ]) ?>
            </div>
        </div>
    </div>
    <!-- No.4-filed submit -->
    <div class="form-group" style="text-align: center;margin: 10px;">
<!--         <?/*= Html::submitButton($model->isNewRecord ? '新增' : '更新',
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary', 
            'onclick'=>'autoTimerListing.create_save()']) */?> -->
        <?php echo  Html::button($model->isNewRecord ? '新增' : '更新',
            [
            'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
            'onclick'=>'autoTimerListing.save()'
            ])?>
    </div>
    <?php ActiveForm::end(); ?>

</div>
