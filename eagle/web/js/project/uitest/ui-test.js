var bianji = function($this) {
	console.log($this);
	console.log($(this));
}

$(function(){


	// $.modal({
	// 	url:''
	// },'title',{
	// 	cache:true
	// }).done(function($modal){
	// 	// console.log($modal.instanceof('$modal'));
	// 	console.log($modal);

	// 	setTimeout(function(){
	// 		$modal.close();

	// 		$.modal({
	// 			url:'colors'
	// 		},'color',{
	// 			cache:true
	// 		}).then(function($m){
	// 			setTimeout(function(){
	// 				$m.close();
	// 				$modal.open();
	// 			},2000)
	// 		})
	// 	},2000);

	// })
});


$.domReady(function($el) {
	'use strict';

	var $document = this;

	$el('.iv-editor').on('editor.ready',function(e,editor,KE){
		console.dir(this)
	});

	$el("#open-modal-test").on('click',function(){
		$.openModal('action1');
		// $.modal({
		// 	url:'action1'
		// })
	});

	$el("#showModal").on('click',function(){

		$.modal({
			url:'action1'
		}).then(function($modal){
			$modal.find("#cls").on('click',function(){

				setTimeout(function(){
					$modal.close();
				},1000);

			});


		});

	});

	
	$el(".charts").on('Highcharts.ready',function(e,hc){
		console.log(hc)
	})

	// $.pluginReady('Highcharts', function(Highcharts) {
	// 	$el("#chart").highcharts({
	// 		chart: {
	// 			type: 'line'
	// 		},
	// 		title: {
	// 			text: 'Monthly Average Temperature'
	// 		},
	// 		subtitle: {
	// 			text: 'Source: WorldClimate.com'
	// 		},
	// 		xAxis: {
	// 			categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
	// 		},
	// 		yAxis: {
	// 			title: {
	// 				text: 'Temperature (°C)'
	// 			}
	// 		},
	// 		plotOptions: {
	// 			line: {
	// 				dataLabels: {
	// 					enabled: true
	// 				},
	// 				enableMouseTracking: false
	// 			}
	// 		},
	// 		series: [{
	// 			name: 'Tokyo',
	// 			data: [7.0, 6.9, 9.5, 14.5, 18.4, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 9.6]
	// 		}, {
	// 			name: 'London',
	// 			data: [3.9, 4.2, 5.7, 8.5, 11.9, 15.2, 17.0, 16.6, 14.2, 10.3, 6.6, 4.8]
	// 		}]
	// 	});
	// });


	$.showModalBox = function(){
		
	}


	$("#showBox").on('click',function(e){
		e.preventDefault();
		var $btn = $(this);
		var $input = $("#_box").find("[name='"+$(this).data('name')+"']");
		$("#_box").css({
			display:'block',
			top:$btn.position().top + $btn.outerHeight() + 10,
			left:$btn.position().left
		});
		setTimeout(function(){
			$("#_box")._else('click',function(){
				$("#_box").hide();
				console.log('hide')
			});
		},0);
		$input.on('change input',function(){
			var key = $(this).attr('name'),
				val = [];
			$input.each(function(){
				val.push($(this).val());
			});

			console.log($input.val())
		})

	})


	

});