(function($){

	/**
	 * 事件
	 * ready
	 * init
	 * 
	 */

	var ImageLib = function(dom,data,option){
		var self = this,
		__modules = $.merge([
			'base',
			// 'iv-local-upload',
			// 'meitu',
		],option.modules);
		this.$dom = $(dom);
		this.$queue = [];
		this.modules = {};
		this.$option = $.extend(true,{
			checkbox:false,
			name:'extra_images',
			maxLength:8
		},option),
		this.$option.modules = __modules;
		this.data = data || [];
		this.maxLength = this.$option.maxLength;

		// 加载模块
		this.$option.modules.forEach(function(module){
			var modName = module.toCamelCase();

			self.$queue.push(function(){
				return $.promise(function(resolve){
					$.require(['/js/project/util/image_lib/'+module+'.js'],function(exports){
						self.modules[modName] = new exports(self);
						resolve();
					},modName);
				})
			});

			// self.$queue.push($.promise(function(resolve){
			// 	$.require(['/js/project/util/image_lib/'+module+'.js'],function(exports){
			// 		self.modules[modName] = new exports(self);
			// 		resolve();
			// 	},modName);
			// }));
		});

		// 加载图片
		self.on('ready',function(){
			self.data.forEach(function(img){
				if(typeof img === 'string'){
					img = {
						src:img,
						thumb:img
					};
				}
				self.m('base').addOne(img.src,img.thumb);
			});
			
		});

		// 加载模板
		this.$dom.loadTmpl('/tmpl/ImageLib').then(function($tpl){
			self.$header = self.$dom.find('.iv-image-header');
			self.$btnDiv = self.$header.find('.pull-right');
			self.$ul = self.$dom.find('.iv-image-body').find('ul');
			// 加载模块完毕
			$.asyncQueue(self.$queue,function(){}).done(function(){
			// $.when.apply(self,self.$queue).done(function(){
				// console.log('trigger ready')
				self.trigger('ready',[]);
			});
		});


	};


	// 获取挂载的插件对象
	ImageLib.prototype.m = function(modName){
		return this.modules[modName];
	};

	ImageLib.prototype.createBtn = function(str,fn){
		var $btn = $(str);
		if(!fn || (false !== fn.apply($btn,[]))){
			this.$btnDiv.$append($btn);
		}
		// $btn.css('margin-right',5)
		return $btn;
	};

	$.classObserver(ImageLib); 	// 增加观察者模式


	window.SelectImageLib = ImageLib;

})(jQuery);