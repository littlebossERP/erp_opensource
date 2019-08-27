<div id="category-modal" style="display:none;">
	<div style="width:900px;height:480px;">
		<div class="input-spacing">
			<input  class="iv-input center-width search_input" placeholder="输入英文产品关键词,如“mp3”" >
			<a class="iv-btn btn-success left-spacing-m search_btn">查找类目</a>
		</div>
		<div class="cate_list" style="display:none;" show-content="CateList">
			<div class="input-spacing">
				为您匹配到
				<span class="cate_search_count">0</span>	
				个类目
				<a class="cancel_cate_search" style="float:right" hide="CateList">返回类目</a>
			</div>
			<ul class="cate_search_ul nav nav-pills nav-stacked">
				<li>
					<a data-no-permission="true" href="javascript:;" data-role="search-option" data-cateid="348" data-cateids="3,200000343,348" data-enname="Apparel &amp; Accessories>Men's Clothing>Shirts" data-cnname="服装/服饰配件>男装>衬衫" data-summary="" data-needpay3="" data-isleaf="true" data-needwhitelist="" title="服装/服饰配件>男装>衬衫">
						<span>服装/服饰配件&gt;男装&gt;衬衫</span>
					</a>
				</li>
			</ul>
		</div>
		<div class="input-spacing" style="height:340px;">
			<div class="input-spacing nav_box" style="display:none;">
				<!-- <div class="input-spacing left-spacing-m">
					<input class="min-width iv-input search" placeholder="请输入名称/拼音首字母" >
				</div> -->
				<ul class="nav nav-pills nav-stacked cate-nav col-md-4 col-xs-4" data-level="0">
				</ul>
			</div>
			<div class="input-spacing nav_box" style="display:none;">
				<!-- <div class="input-spacing left-spacing-m">
					<input class="min-width iv-input search" placeholder="请输入名称/拼音首字母" >
				</div> -->
				<ul class="nav nav-pills nav-stacked cate-nav col-md-4 col-xs-4" data-level="1">
				</ul>
			</div>
			<div class="input-spacing nav_box" style="display:none;">
				<!-- <div class="input-spacing left-spacing-m">
					<input class="min-width iv-input search" placeholder="请输入名称/拼音首字母" >
				</div> -->
				<ul class="nav nav-pills nav-stacked cate-nav col-md-4 col-xs-4" data-level="2">
				</ul>
			</div>
			<div class="input-spacing nav_box" style="display:none;">
				<!-- <div class="input-spacing left-spacing-m">
					<input class="min-width iv-input search" placeholder="请输入名称/拼音首字母" >
				</div> -->
				<ul class="nav nav-pills nav-stacked cate-nav col-md-4 col-xs-4" data-level="3">
					
				</ul>
			</div>
		</div>
		<div class="category_tip">
			<span>您当前选择的类目:</span>
			<span class="category_content strong" data-level="0"></span>
			<span class="category_content strong" data-level="1"></span>
			<span class="category_content strong" data-level="2"></span>
			<span class="category_content strong" data-level="3"></span>
		</div>
		<div class="modal-footer">
			<a class="iv-btn btn-success btn-padding category-ensure" disabled>确定</a>
			<a class="iv-btn btn-icon btn-padding left-spacing-m modal-close">取消</a>
		</div>
	</div>
</div>
<div id="select-product-group" style="display:none">
	<div role="form" class="iv-form" style="width:400px;height:260px;">
		<div class="group-list">
		</div>
		<div class="modal-bottom">
			<a class="iv-btn btn-success sync_group">同步产品分组</a>
			<a class="iv-btn btn-success select_group">选择</a>
			<a class="iv-btn btn-default modal-close">取消</a>
		</div>
	</div>
</div>

<div id="select-url-img" style="display:none;">
	<div role="form" class="iv-form">
		<div style="margin-bottom:10px;">
			URL 地址: <input type="text" name="img_url" class="iv-input" style="width:300px;">
		</div>	
		<div style="text-align:center;">
			<!-- <input type="submit"  class="iv-btn btn-success" value="确定"> -->
		</div>
	</div>
</div>
<div id="infoModule" style="display:none;">
	<div role="form" class="iv-form">
		<div class="filter-bar" style="margin-bottom:10px;">
			<span>模块名称:</span>
			<div class="iv-input input-group">
				<input type="text" class="iv-input" name="searchByName">
				<a class="iv-btn btn-success searchNameBtn">搜索</a>
			</div>
		</div>
		<div>
			<table class="table productModuleTable">
				<thead>
					<th>模块名称</th>
					<th>模块类型</th>
					<th>操作</th>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
		<div class="modal-bottom">
			<a class="iv-btn btn-success sync_infoModule">同步产品信息模块</a>
			<a class="iv-btn btn-success select_infoModule">选择</a>
			<a class="iv-btn btn-default modal-close">取消</a>
		</div>
	</div>
</div>