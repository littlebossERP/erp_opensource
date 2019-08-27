(function(){

	var primary = function(ImageLib){
		var self = this,
			str = '<label class="primary">' + 
				'<input class="primary_radio" type="radio" name="main_image" value="">' + 
				'<span class="text-danger">主图</span>' + 
				'<a>设为主图</a>' + 
			'</label>';

		ImageLib.on('ready',function(){
			
			var Base = ImageLib.m('base');

			if(!('primary' in ImageLib.$option)){
				throw 'ImageLib.$option need primary';
			};

			Base.on('addOne',function($li,img){
				// 插入
				var $header = $li.find('.iv-image-box-header'),
					$radio;
				$header.$append(str);
				$radio = $header.find('.primary_radio');
				$radio.attr('name',ImageLib.$option.primary.name).val(img[0]);
				if(img[0] == ImageLib.$option.primary.val){
					$radio.attr('checked','checked').prop('checked',true);
				}
				// console.log(ImageLib.$option,img[0]);

			});


		});
	};

	window.primary = primary;

})();