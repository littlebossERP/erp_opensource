(function(){

	var Base = function(ImageLib){
		var self = this;
		this.ImageLib = ImageLib;
		this.ImageLibAddOneTmpl = '/tmpl/ImageLibAddOne';
		this.ImageLib.val = [];
		this.ImageLib.toAdd = 0;
	};

	Base.prototype.remove = function($li){
		this.trigger('remove.before',[$li]);
		$li.remove();
		this.trigger('remove.after',[$li]);
	};

	Base.prototype._liHtml = '<li class="iv-image-box"></li>';

	Base.prototype.addOne = function(src,thumb){
		// 加载 模板
		var self = this,
			$li = $(this._liHtml),
			args = arguments;
		thumb = thumb || src;
		
		// dzt20170106
		if(self.size() >= self.ImageLib.maxLength){
			$.alertBox('图片不能超过 '+self.ImageLib.maxLength+' 张');
			return false;
		}
		
		this.ImageLib.$ul.$append($li);
		return $li.loadTmpl(this.ImageLibAddOneTmpl,{
			name:self.ImageLib.$option.name,
			src:src,
			thumb:thumb,
			checkbox:self.ImageLib.$option.checkbox,
		}).then(function($tpl){
			self.trigger('addOne',[$li,args]);
			self.ImageLib.$ul.sortable('refresh'); // sortable
			self.ImageLib.val.push(src);
			if(typeof self.ImageLib.toAdd != 'undefined' && self.ImageLib.toAdd >0)// dzt20170112 add 完图片之后弹出alert box
			self.ImageLib.toAdd--;
		});
	};

	Base.prototype.maxLength = function(val){
		if(typeof val === 'undefined'){
			return this.ImageLib.maxLength;
		}else{
			this.ImageLib.maxLength = val;
			return this;
		}
	};

	Base.prototype.size = function(){
		return this.ImageLib.$ul.find('li').size();
	};

	// 增加观察者模式
	$.classObserver(Base);

	window.base = Base;

})();