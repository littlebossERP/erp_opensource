<?php

use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/analysis_rule.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//$this->registerCssFile($baseUrl."css/catalog/catalog.css");

$this->title = TranslateHelper::t('SKU解析规则');
//$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<style>
    .text-orange{color:orange;}
    .text-muted{color:red;}
    .text-muted{color:red;}
</style>
<div class="catalog-index col2-layout">
<!------------------------------ oms 2.1 左侧菜单  start  ----------------------------------------->
<?php echo $this->render('../leftmenu/_leftmenu');?>
<!------------------------------ oms 2.1 左侧菜单   end  ----------------------------------------->
<?php 
//判断子账号是否有权限查看，lrq20170829
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('catalog_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>

    <div class="content-wrapper" >
        <form action="" method="post" id="skuruleform">
            <h4>SKU解析规则</h4>
            <?=Html::radioList('is_active',$skurule['is_active'],['0'=>'关闭','1'=>'开启'])?>
            <br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <font size="2px" color="red">小贴士：按执行步骤对SKU进行逐步解析</font>
            <hr/>
          <table width="100%" border="1">
            <!----------- start SKU前后缀关键字 --------------->
            <tr>
                <td width="30">&nbsp;</td>
                <td width="150" align="left"><label>SKU前后缀关键字：<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;（步骤1）</label></td>
                <td width="100"><label><input name="keyword_rule" type="radio" id="keyword_open" value="open" onclick="productList.list.openKeywordRule()" <?=$skurule['keyword_rule']=='open' ? "checked" : '' ?>/>开启</label></td>
                <td ><label><input name="keyword_rule" type="radio" id="keyword_close" value="close" onclick="productList.list.closeKeywordRule()" <?=$skurule['keyword_rule']=='open' ? '' : 'checked' ?>/>关闭</label></td>

            </tr>
            <tr>
                <td width="30">&nbsp;</td>
                <td width="30">&nbsp;</td>
                <td colspan="4" id="td_keyword_rule" style="<?=$skurule['keyword_rule']=='open' ? '' : 'display:none' ?>">
                    <p class="help-block">请设置<strong>捆绑SKU</strong>组成的<strong>前后缀关键字</strong>，当同步订单时将自动<strong>替换这些捆绑SKU的前后缀为空</strong></p>
                    <div>
                        <div class="form-group" style="float:left;width:100px;vertical-align:middle;margin:0px;">
                            <label for="Product_tag" class="control-label" style="float:left;width:20%;padding:6px 0px;">
                                <a class="cursor_pointer" onclick="productList.list.addTagHtml(this)"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></a>
                            </label>
                            <input type="text" class="form-control" id="Product_tag" name="keyword[]" value="<?php echo isset($skurule['keyword'][0])?$skurule['keyword'][0]:'';?>" style="float:left;width:80%;"/>
                        </div>
                        <?php if (isset($skurule['keyword']) && !empty($skurule['keyword'])){
                            $i = 0;
                            foreach($skurule['keyword'] as $one){
                                $i++;
                                if ($i == 1) {
                                    continue;
                                }
                                echo "<div class=\"form-group\" id=\"new_form_group\" style=\"float:left;width:100px;vertical-align:middle;margin:0px;\">".
                                    "<label for=\"Product_tag\" class=\"control-label\" style=\"float:left;width:20%;padding:6px 0px;\">".
                                    "<a  class=\"cursor_pointer\"  onclick=\"productList.list.delete_form_group(this)\"><span class=\"glyphicon glyphicon-remove-circle\"  class=\"text-danger\" aria-hidden=\"true\"></span></a>".
                                    "</label>".
                                    "<input type=\"text\" class=\"form-control\" name=\"keyword[]\" value=\"".$one."\" style=\"float:left;width:80%;\"/>".
                                    "</div>";
                            }
                        }?>
                    </div>
                    <div style="clear: both;">
                        <p class="help-block">例如 捆绑SKU<strong> “A00001*1+B00002*3-ebay”</strong>其中<strong>-ebay</strong>是前后缀关键字，当同步订单时将<strong><span class="text-primary pl5 pr5">自动替换-ebay为空</span></strong>最终变成<strong>A00001*1+B00002*3</strong>再解析</p>
                    </div>
                </td>
            </tr>
            <!----------- end SKU前后缀关键字 --------------->

            <!----------- start SKU截取规则 --------------->
            <tr><td colspan="4"><hr/></td></tr>
            <tr>
                <td width="30">&nbsp;</td>
                <td width="150" align="left"><label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;SKU截取规则：<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;（步骤2）</label></td>
                <td width="100"><label><input name="substring_rule" type="radio" id="substring_open" value="open"  onclick="productList.list.openSubstringRule()" <?=$skurule['substring_rule']=='open' ? "checked" : '' ?>>开启</label></td>
                <td ><label><input name="substring_rule" type="radio" id="substring_close" value="close" onclick="productList.list.closeSubstringRule()" <?=$skurule['substring_rule']=='open' ? '' : 'checked' ?>/>关闭</label></td>
            </tr>
            <tr>
                <td width="30">&nbsp;</td>
                <td width="30">&nbsp;</td>
                <td colspan="4" id="td_substring_rule" style="<?=$skurule['substring_rule']=='open' ? '' : 'display:none' ?>">
                  <p class="help-block">请设置<strong>捆绑SKU</strong>组成的<strong>起始符号</strong>与<strong>终止符号</strong>，遵循从<strong>左到右截取规则</strong>，当同步订单时将自动获取这些捆绑SKU中存在的<strong>这2个符号之间的内容</strong></p>
                  <div>
                       <span style="float:left; margin-top: 9px;">截取&nbsp;&nbsp;</span>
                       <input type="text" placeholder="起始符号" class="form-control"  id="firstChar" name="firstChar" value="<?php echo $skurule['firstChar'];?>" style="float:left;width:120px; "/>
                       <span style="float:left; margin-top: 9px;">&nbsp;&nbsp;与&nbsp;&nbsp;</span>
                       <input type="text" placeholder="终止符号" class="form-control" id="secondChar" name="secondChar" value="<?php echo $skurule['secondChar'];?>" style="float:left;width:120px; "/>
                       <span style="float:left; margin-top: 9px;">&nbsp;&nbsp;之间的字符</span>
                  </div>
                  <div style="clear: both;">
                      <p class="help-block">例如 捆绑SKU<strong> “amazon/A00001*1+B00002*3-ebay”</strong>其中：<strong><span class="text-primary pl5 pr5">起始符号</span></strong>设为"<strong><span class="text-orange pl5 pr5">/</span></strong>"，<strong><span class="text-primary pl5 pr5">终止符号</strong></span>设为"<strong><span class="text-orange pl5 pr5">-</span></strong>"，使用以上规则所生成的捆绑SKU为：
                       <span class="substringRuleExample" id="example">
                            <strong><span class="text-primary label-first">A00001</span></strong>
                            <strong><span class="text-muted connector-first">*</span></strong>
                            <strong><span class="text-orange label-last">1</span></strong>
                            <strong><span class="text-success connector-last">+</span></strong>
                            <strong><span class="text-primary label-first">B00002</span></strong>
                            <strong><span class="text-muted connector-first">*</span></strong>
                            <strong><span class="text-orange label-last">3</span></strong>
                       </span>
                      </p>
                  </div>
                </td>
            </tr>
            <!----------- start SKU截取规则 --------------->

            <!----------- start 捆绑SKU拆分规则 --------------->
            <tr><td colspan="4"><hr/></td></tr>
            <tr>
                  <td width="30">&nbsp;</td>
                  <td width="150" align="left"><label>捆绑SKU拆分规则：<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;（步骤3）</label></td>
                  <td width="100"><label><input name="split_rule" type="radio" id="split_open" value="open"  onclick="productList.list.openSplitRule()" <?=$skurule['split_rule']=='open' ? "checked" : '' ?>>开启</label></td>
                  <td ><label><input name="split_rule" type="radio" id="split_close" value="close" onclick="productList.list.closeSplitRule()" <?=$skurule['split_rule']=='open' ? '' : 'checked' ?>/>关闭</label></td>
            </tr>
            <tr>
                  <td width="30">&nbsp;</td>
                  <td width="30">&nbsp;</td>
                  <td colspan="4" id="td_split_rule" style="<?=$skurule['split_rule']=='open' ? '' : 'display:none' ?>">
                      <p class="help-block">请设置<strong>捆绑SKU</strong>组成规则及<strong>连接符号</strong>，当同步订单时将自动解析出<strong>SKU</strong>并保存成商品<br></p>
                      <div class="form-group skugrouplabel">
                          <div class=" moreinfo dis-none" style="display: block;">
                              <div class="input-group" style="width:350px;">
                                  <div class="input-group-btn">
                                      <div class="btn-group">
                                          <button data-toggle="dropdown" class="btn btn-default" type="button">
                                              <span class="text" id="firstKeyTest">SKU</span><span class="caret ml5"></span>
                                          </button>
                                          <ul role="menu" class="dropdown-menu dropdown-menu-left copytext">
                                              <li><a onclick="setKeys2('firstKey','secondKey')" href="javascript:void(0);">SKU</a></li>
                                              <li><a onclick="setKeys2('secondKey','firstKey')" href="javascript:void(0);">数量</a></li>
                                          </ul>
                                          <input type="hidden" name="firstKey" id="firstKey" value="<?php echo $skurule['firstKey'];?>">
                                      </div>
                                  </div>
                                  <input type="text" placeholder="连接符1" id="quantityConnector" name="quantityConnector" class="form-control text-center" value="<?php echo $skurule['quantityConnector'];?>" style="height:33px;">
                                  <div class="input-group-btn">
                                      <div class="btn-group">
                                          <button id="secondKeyBtn" data-toggle="dropdown" class="btn btn-default" type="button" style="border-width:1px 0; border-radius:0;">
                                              <span class="text" id="secondKeyTest">数量</span><span class="caret ml5"></span>
                                          </button>
                                          <ul role="menu" class="dropdown-menu dropdown-menu-left copytext">
                                              <li><a id="secondKeySku" onclick="setKeys2('secondKey', 'firstKey')" href="javascript:void(0);">SKU</a></li>
                                              <li><a id="secondKeyQuantity" onclick="setKeys2('firstKey','secondKey')" href="javascript:void(0);">数量</a></li>
                                          </ul>
                                          <input type="hidden" name="secondKey" id="secondKey" value="<?php echo $skurule['secondKey'];?>">
                                      </div>
                                  </div>
                                  <input type="text" placeholder="连接符2" name="skuConnector" id="skuConnector" class="form-control text-center" value="<?php echo $skurule['skuConnector'];?>" style="height:33px;">
                                  <span class="input-group-addon">下一个sku</span>
                              </div>
                              <p class="help-block">
                                  例如
                                  <strong><span class="text-primary pl5 pr5">A00001</span></strong>和<strong>
                                      <span class="text-primary pl5 pr5">B00002</span></strong>两种
                                  <strong>SKU</strong>数量分别为 <strong><span class="text-orange pl5 pr5">1</span></strong>
                                  个和 <strong><span class="text-orange pl5 pr5">3</span></strong> 个,使用以上规则所生成的<strong>捆绑SKU</strong>为：
                          <span class="skugroupexample" id="example">
                            <strong><span class="text-primary label-first">A00001</span></strong>
                            <strong><span class="text-muted connector-first">*</span></strong>
                            <strong><span class="text-orange label-last">1</span></strong>
                            <strong><span class="text-success connector-last">+</span></strong>
                            <strong><span class="text-primary label-first">B00002</span></strong>
                            <strong><span class="text-muted connector-first">*</span></strong>
                            <strong><span class="text-orange label-last">3</span></strong>
                          </span>
                              </p>
                          </div>
                      </div>
                  </td>
            </tr>
            <!----------- end 捆绑SKU拆分规则 --------------->

            <tr><td colspan="4"><br><br><br></td></tr>

            <tr>
                <td colspan="4">
                    <font size="3px">测试SKU解析规则</font>
                </td>
            </tr>
            <tr>
                <td colspan="2" align="right">需要解析的SKU：&nbsp;&nbsp;</td>
                <td colspan="2">
                    <input type="text" placeholder="输入需要解析的SKU" class="form-control" id="sku_ago" name="sku_ago" value="<?php ?>" style="float:left;width:400px; "/>
                    &nbsp;&nbsp;
                    <input type="button" value="解  析" onclick="productList.list.testSkuRule()" style="width:80px; height: 29px; margin-top:5px; border-radius: 8px 8px;">
                </td>
            </tr>
            <tr>
                <td colspan="2" align="right">解析结果：&nbsp;&nbsp;</td>
                <td colspan="2">
<!--                    <input type="te" class="form-control" id="sku_later" name="sku_later" value="--><?php //?><!--" style="float:left;width:400px; "/>-->
                    <textarea name="sku_later" id="sku_later" cols="80" rows="4"></textarea>
                </td>
            </tr>

            <tr><td colspan="4">&nbsp;</td></tr>

            <tr>
                <td width="30">&nbsp;</td>
                <td colspan="3">
                    <div style="clear: both;">
                        <input type="button" value=" 保 存 " class="btn btn-success" onclick="productList.list.saveSkuRule()">
                    </div>
                </td>
            </tr>
          </table>
        </form>
    </div>
</div>


