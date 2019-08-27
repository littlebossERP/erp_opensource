<div class="modal fade bs-modal-lg"  id="edit_modal" tabindex="5">
    <div class="modal-dialog modal-lg"  >
        <div class="modal-content" >
            <div class="modal-body">
                <div class="cite_goods_box">
                    <div class="container">
                        <div class="row" style="width: 600px; height: 30px">
                            <div style="width: 600px; float: left">
                                <span style="float: left; line-height: 30px; width: 100px">待修改选项：</span>
                                <select class=" col-xs-12 p0 form-control" id="wish_edit_model" style="margin-top:0; width: 400px;">
                                    <option value="0">请选择</option>
                                    <?php foreach($wish_model as $key => $value):?>
                                        <option value="<?=$key?>"><?=$value?></option>
                                    <?php endforeach;?>
                                </select>
                            </div>

                            <div style="width: 600px; float: left;">
                                <span style="float: left; line-height: 30px; width: 100px">修改方式：</span>
                                <select class=" col-xs-12 p0 form-control" id="wish_edit_type" style="margin-top:0;width: 400px;">
                                    <option value="0">请选择</option>
                                </select>
                            </div>

                            <div id="wish_content" style="width: 600px; float: left; margin-left: 100px">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="wish_modal_footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" id="close" class="btn btn-default" data-dismiss="modal" style="display: none">关闭</button>
                <button type="button" class="btn btn-primary" id="wish_edit_commit">确定</button>
            </div>
        </div>
    </div>
</div>