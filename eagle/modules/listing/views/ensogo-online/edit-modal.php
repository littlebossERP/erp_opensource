<div id="batch-edit-modal" style="display:none;position:relative;">
    <div style="width:900px;height:500px;">
        <ul class="nav nav-tabs" style="margin:30px;">
            <li role="presentation" data-modify="price" class="active">
                <a href="#">售价（$）</a>
            </li>
            <li role="presentation" data-modify="msrp">
                <a href="#">售价和市场价（$）</a>
            </li>
            <li role="presentation" data-modify="inventory">
                <a href="#">库存</a>
            </li>
            <li role="presentation" data-modify="shipping">
                <a href="#">运费</a>
            </li>
            <li role="presentation" data-modify="shipping_time">
                <a href="#">运输时间</a>
            </li>
            <li role="presentation" data-modify="site">
                <a href="#">站点售价（$）</a>
            </li>
        </ul>
        <form action="" class="block">
            <!--price-modify-->
            <div class="modify-modal price-modal-content">
                <div class="filter-bar">
                    <span style="margin-right:35px;"> 
                        <label class="radio-box" style="width:20px;text-align:left;">
                            <span class="radio-checked"><i></i></span>
                            <input type="radio" name="price_modify_type" data-checked="price" data-type="add" checked/>
                        </label>
                        按
                    </span>
                    <select class="iv-select money_unit_select" style="width:200px" placeholder="金额">
                        <option value="pri">金额</option>
                        <option value="per">百分比</option>
                    </select> 
                </div>
                <div class="filter-bar" style="margin-top:10px;">
                    <label style="width:50px;text-align:left;margin-left:20px;">
                        增加
                    </label>
                    <div class='input-group iv-input'>
                        <input type="number" class="iv-input radio-input add modify_val" style="width:160px;" min="0" placeholder="示例：1.00">
                        <span class="money_unit" style="padding-right:10px;">美元</span>
                    </div>
                    <span style="margin-left:5px;color:#969696;">提示: 如果减少，可输入负数。</span>
                </div>
                <div class="filter-bar" style="margin-top:10px;">
                    <label class="radio-box" style="width:20px;text-align:left;">
                        <span class="radio"><i></i></span>
                        <input type="radio" name="price_modify_type" data-checked="price" data-type="mod"/>
                    </label>
                    <input type="number" class="iv-input radio-input mod modify_val  disabled" min="0" style="width:230px;" disabled/>
                    <span style="margin-left:5px;color:#969696;">提示: 直接修改价格。</span>
                </div>
                <div class="filter-bar" style="background-color: #F9F9F9;padding: 20px;border-radius: 5px;margin-bottom:20px;">
                    <span class="error_tip" style="color:#FF0600;"></span>
                </div>
            </div>
            <!--msrp-modify-->
            <div class="modify-modal msrp-modal-content" style="display:none">
                <div class="filter-bar" style="background-color: #F9F9F9;padding: 20px;border-radius: 5px;margin-bottom:20px;">
                    <span style="color:#03D661;">提示：此处修改售价会使市场价等比例增加，如售价为25，市场价为100，售价被改为50后（增加一倍），市场价将变为200（增加一倍）</span>
                </div>
                <div class="filter-bar">
                    <span style="margin-right:35px;">
                        <label class="radio-box" style="width:20px;text-align:left;">
                            <span class="radio-checked"><i></i></span>
                            <input type="radio" data-checked="msrp" name="msrp_modify_type" data-type="add" checked/>
                        </label>
                        按
                    </span>
                    <select class="iv-select money_unit_select" style="width:200px" placeholder="金额">
                        <option value="pri">金额</option>
                        <option value="per">百分比</option>
                    </select> 
                </div>
                <div class="filter-bar" style="margin-top:10px;">
                    <label style="width:50px;text-align:left;margin-left:20px;">
                        增加
                    </label>
                    <div class='input-group iv-input'>
                        <input type="number" class="iv-input radio-input add" style="width:160px;" min="0" placeholder="示例：1.00">
                        <span class="money_unit" style="padding-right:10px;">美元</span>
                    </div>
                    <span style="margin-left:5px;color:#969696;">提示: 如果减少，可输入负数。</span>
                </div>
                <div class="filter-bar" style="margin-top:10px;">
                    <label class="radio-box" style="width:20px;text-align:left;">
                        <span class="radio"><i></i></span>
                        <input type="radio" name="msrp_modify_type" data-checked="msrp" data-type="mod"/>
                    </label>
                    <input type="number" class="iv-input radio-input mod  disabled" style="width:230px;" min="0" disabled/>
                    <span style="margin-left:5px;color:#969696;">提示: 直接修改价格。</span>
                </div>
                <div class="filter-bar" style="background-color: #F9F9F9;padding: 20px;border-radius: 5px;margin-bottom:20px;">
                    <span class="error_tip" style="color:#FF0600;"></span>
                </div>
            </div>
            <div class="modify-modal inventory-modal-content" style="display:none;">
                <div class="filter-bar">
                    <span style="margin-right:35px;">
                        <label class="radio-box" style="width:20px;text-align:left;">
                            <span class="radio-checked"><i></i></span>
                            <input type="radio" name="inventory_modify_type" data-checked="inventory" data-type="add" checked/>
                        </label>
                        按现有库存量增加
                    </span>
                    <input type="number"  class="iv-input radio-input add" style="width:200px;" min="0" placeholder="示例: 1.00">
                    <span style="margin-left:5px;color:#969696;">提示: 如减少，可输负数</span>
                </div>
                <div class="filter-bar" style="margin-top:10px;">
                    <label class="radio-box" style="width:20px;text-align:left;">
                        <span class="radio"><i></i></span>
                        <input type="radio" name="inventory_modify_type" data-checked="inventory" data-type="mod"/>
                    </label>
                    <input type="number" class="iv-input radio-input mod  disabled" style="width:230px;" min="0" disabled/>
                    <span style="margin-left:5px;color:#969696;">提示: 直接修改库存量</span>
                </div>
                <div class="filter-bar" style="background-color: #F9F9F9;padding: 20px;border-radius: 5px;margin-bottom:20px;">
                    <span class="error_tip" style="color:#FF0600;"></span>
                </div>
            </div>
            <div class="modify-modal shipping-modal-content" style="display:none">
                <div class="filter-bar">
                    <span style="margin-right:35px;">
                        <label class="radio-box" style="width:20px;text-align:left;">
                            <span class="radio-checked"><i></i></span>
                            <input type="radio" name="shipping_modify_type" data-checked="shipping" data-type="add" checked/>
                        </label>
                        按
                    </span>
                    <select class="iv-select money_unit_select" style="width:200px" placeholder="金额">
                        <option value="pri">金额</option>
                        <option value="per">百分比</option>
                    </select> 
                </div>
                <div class="filter-bar" style="margin-top:10px;">
                    <label style="width:50px;text-align:left;margin-left:20px;">
                        增加
                    </label>
                    <div class='input-group iv-input'>
                        <input type="number" class="iv-input radio-input add" style="width:160px;" min="0" placeholder="示例：1.00">
                        <span class="money_unit" style="padding-right:10px;">美元</span>
                    </div>
                    <span style="margin-left:5px;color:#969696;">提示: 如果减少，可输入负数。</span>
                </div>
                <div class="filter-bar" style="margin-top:10px;">
                    <label class="radio-box" style="width:20px;text-align:left;">
                        <span class="radio"><i></i></span>
                        <input type="radio" name="shipping_modify_type" data-checked="shipping" data-type="mod"/>
                    </label>
                    <input type="number" class="iv-input radio-input mod  disabled" style="width:230px;" min="0" disabled/>
                    <span style="margin-left:5px;color:#969696;">提示: 直接修改运费。</span>
                </div>
                <div class="filter-bar" style="background-color: #F9F9F9;padding: 20px;border-radius: 5px;margin-bottom:20px;">
                    <span class="error_tip" style="color:#FF0600;"></span>
                </div>
            </div>
            <?php 
                $shipping_time = ['5-10','7-14','10-15','14-21','21-28'];
            ?>
            <div class="modify-modal shipping_time-modal-content" style="display:none">
                <div class="filter-bar">
                    <?php foreach($shipping_time as $k => $shipping_time): ?>
                        <span style="margin-right:20px;">
                            <label class="radio-box" style="width:20px;text-align:left;">
                                <?php if($k): ?>
                                    <span class="radio"><i></i></span>
                                   <input type="radio" name="shipping_time" data-checked="shipping_time" value="<?=$shipping_time?>"/>
                                <?php else: ?>
                                    <span class="radio-checked"><i></i></span>
                                    <input type="radio" name="shipping_time" data-checked="shipping_time" value="<?=$shipping_time?>" checked/>
                                <?php endif;?>
                            </label>
                            <?=$shipping_time ?>
                        </span>
                    <?php endforeach;?>
                </div>
                <div class="filter-bar" style="margin-top:15px;">
                    <span style="margin-right:20px;">
                        <label class="radio-box" style="width:20px;text-align:left;">
                            <span class="radio"><i></i></span>
                            <input type="radio" name="shipping_time" data-checked="shipping_time" value="other" data-type="other" />
                        </label>
                        其他
                    </span>
                    <input type="number" class="iv-input radio-input disabled other" name="shipping_short_time" placeholder="最小天数" style="width:180px;" min="0" disabled>
                    -
                    <input type="number" class="iv-input radio-input disabled other" name="shipping_long_time" placeholder="最大天数" style="width:180px;" min="0" disabled>

                </div>
                <div class="filter-bar" style="background-color: #F9F9F9;padding: 20px;border-radius: 5px;margin-bottom:20px;">
                    <span class="error_tip" style="color:#FF0600;"></span>
                </div>
            </div>
            <?php 
                $sites = eagle\modules\listing\config\params::$ensogo_sites;
            ?>
            <div class="modify-modal site-modal-content" style="display:none">
                <div class="filter-bar">
                    <span style="margin-right: 22px;">站点选择</span>
                    <select class="iv-select site_select" style="width:200px;">
                        <?php foreach($sites as $k_s => $site): ?>
                            <option value="<?= $k_s ?>"><?= $site ?></option>
                        <?php endforeach;?>
                    </select>
                </div>
                 <div class="filter-bar">
                    <span style="margin-right:35px;">
                        <label class="radio-box" style="width:20px;text-align:left;">
                            <span class="radio-checked"><i></i></span>
                            <input type="radio" name="site_modify_type" data-checked="site" data-type="add" checked/>
                        </label>
                        按
                    </span>
                    <select class="iv-select money_unit_select" style="width:200px" placeholder="金额">
                        <option value="pri">金额</option>
                        <option value="per">百分比</option>
                    </select> 
                </div>
                <div class="filter-bar" style="margin-top:10px;">
                    <label style="width:50px;text-align:left;margin-left:20px;">
                        增加
                    </label>
                    <div class='input-group iv-input'>
                        <input type="text" class="iv-input radio-input add" style="width:160px;" placeholder="示例：1.00">
                        <span class="money_unit" style="padding-right:10px;">美元</span>
                    </div>
                    <span style="margin-left:5px;color:#969696;">提示: 如果减少，可输入负数。</span>
                </div>
                <div class="filter-bar" style="margin-top:10px;">
                    <label class="radio-box" style="width:20px;text-align:left;">
                        <span class="radio"><i></i></span>
                        <input type="radio" name="site_modify_type" data-checked="site" data-type="mod"/>
                    </label>
                    <input type="text" class="iv-input radio-input mod  disabled" style="width:230px;" disabled/>
                    <span style="margin-left:5px;color:#969696;">提示: 直接修改价格。</span>
                </div>
                 <div class="filter-bar" style="background-color: #F9F9F9;padding: 20px;border-radius: 5px;margin-bottom:20px;">
                    <span class="error_tip" style="color:#FF0600;"></span>
                </div>
            </div>
        </form>


        <div class="filter-bar" style="text-align:center;margin-top:30px;position:absolute;bottom:10px;left:32%;">
            <button class="btn btn-success" id="modify_ensure" style="margin-right:50px;background-color:#444444;border:1px solid #444444;" disabled>确定</button>
            <button class="btn btn-background modal-close">取消</button>
        </div>
    </div>
</div>