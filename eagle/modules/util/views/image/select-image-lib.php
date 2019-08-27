<?php 
echo $this->render('//layouts/new/left_menu_2',$menu);
?>
<style>
ul.chooseTree {
    list-style: none;
    margin: 0;
    padding-left: 10px;
    width: 100%;
    margin-bottom: 5px;
    /*color: #000;*/
	font-size: 12px;
}
ul.chooseTree ul {
    list-style: none;
    margin: 0;
    padding-left: 17px;
}
ul.chooseTree li div.outDiv {
    height: 22px;
    padding-top: 3px;
}
ul.chooseTree li span.chooseTreeName {
    cursor: pointer;
    padding-left: 3px;
    padding-right: 3px;
}
ul.chooseTree span.glyphicon-triangle-right, ul.chooseTree span.glyphicon-triangle-bottom {
    cursor: pointer;
}
.bgColor {
    background: rgb(1,189,240);
	color: rgb(255,255,255);
}
.disabled { pointer-events: none; }
</style>
<div class="iv-select-img-lib" role="form">
	<div class="iv-select-img-lib-header clearfix" style="padding-bottom: 0px; ">
			<div class="pull-left iv-select-img-lib-search">
				<!-- <div class="input-group iv-input">
					<input type="text" class="iv-input" placeholder="请输入图片名称" style="width:300px;" />
					<a class="iv-btn btn-search iconfont icon-sousuo"></a>
				</div> -->
				<!-- <label>
					<input type="checkbox" /> 全选
				</label> -->
				<a class="iv-select-img-lib-batch-del">
					<span class="iconfont icon-shanchu"></span> 批量删除
				</a>
				<span class="text-danger pull-right">提示：请注意及时清理图片库图片！</span>
			</div>
		<div class="pull-right iv-select-img-lib-info">
			<table>
				<tr>
					<td>总容量：<span class="data-img-lib-totalSize"></span>M</td>
					<td>剩余空间：<span class="data-img-lib-leftSize"></span>M</td>
				</tr>
				<tr>
					<td>已使用：<span class="data-img-lib-usedSize"></span>M</td>
					<td>图片张数：<span class="data-img-lib-count"></span>张</td>
				</tr>
			</table>
			<a class="iv-select-img-lib-add-storage pull-right" onclick="$.alertBox('敬请期待')">
				扩容
			</a>
		</div>
		
		<div  class="pull-left iv-select-img-lib-search" style="padding-top: 10px;">
				<table class="table table-bordered">
					<tbody>
						<tr>
							<td>
								<ul class="dropdown-menuTree col-xs-12" id="treeTab">
										<div id="liOne">
											<li>
												<div class="pRight10" id="categoryTreeA">
													<ul class="chooseTree">
														<li groupid="0" groupname="所有分类">
															<div class="outDiv"><span class="gly glyphicon glyphicon-triangle-bottom pull-left" data-isleaf="open"></span><div class="pull-left"><label><span class="chooseTreeName" onclick="null" data-groupid="0">所有分类<span class="num"></span></span><span class=""></span></label></div></div>
															<ul data-cid="0" style="display: block;">
																<?php echo $imagesClassifica['html'];
																?>
															</ul>
														</li>
													</ul>
												</div>
											</li>
										</div>
								</ul>
							</td>
						</tr>
					</tbody>
				</table>	
			</div>
	</div>
	<div class="iv-select-img-lib-body">
		<ul class="clearfix select-image-re" role="ajax-page" pagename="image-lib" pageurl="/util/image/select-image-lib-list" perpage="12">
			<!-- 列表内容，ajax加载 -->
		</ul>
		<div role="ajax-page-bar" pagename="image-lib" class="ajax-page-bar">
			<!-- 页码部分，js自动生成 -->
		</div>
	</div>
	<!-- <div class="iv-select-img-lib-footer">
		<input type="submit" value="确定" class="iv-btn btn-success" />
		<a class="iv-btn btn-default modal-close">取消</a>
	</div> -->
</div>