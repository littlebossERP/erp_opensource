(function($, window) {
	'use strict';

	window.bar = $.extend({
		size: 30,
		code: undefined
		// code: {
		// 	src:'/images/questionMark.png',
		// 	width:50,
		// 	height:50
		// }
	}, window.bar);

	var $bar = $("<div></div>"),
		_size = window.bar.size,
		_barCode = window.bar.barCode,
		$cert = $("<div></div>"),
		$totop = $("<div><img style='width:100%;height:100%' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC4AAAAoCAYAAACB4MgqAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2lpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo4RThCNDM4MEM3RkRFNDExQTE1Q0FFMjNDMkM5OTBFQyIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpERjE2OUQ4NTQwQjgxMUU1QUJBOUQwMzNCMzY3MkNGRSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpERjE2OUQ4NDQwQjgxMUU1QUJBOUQwMzNCMzY3MkNGRSIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ0MgKFdpbmRvd3MpIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NjhlNTI5ODMtOWY0OS1lYjQ1LTg4OTctMzRkNzZmNTA4ODI2IiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjhFOEI0MzgwQzdGREU0MTFBMTVDQUUyM0MyQzk5MEVDIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+R3kG3wAAAhNJREFUeNpifPbs2RmGIQiYGIYoGHX4qMNHHT7q8FGHjzp8eDl8165dosuXL5emlfkstDD0zJkz/GvXrpUDsTk4OP4GBga+GPQhfurUKf7Zs2erwPg7duyQ3rhxo8SgDvGTJ08KzJs3TxldfNu2baAk89/f3//loAvxEydOoDg6Njb2XkFBwQ0kx8ts3rxZfFA5/NixY4Lz58+HO9rHx+eJjY3Ne01Nza/Jycl3YeJbtmyRAWLxQeFwkKMXLlyoBOP7+vo+AWJ4kjAzM/uQlJQEdzww1GW2bt0qNqAOP3DggBCyo4GlxyNgaGOkY3Nz8w/AkL8D42/atEmW0mRDtsP37t0rAiynFWH8sLCwhx4eHq9xqQeG/Mf09PTbyMkGWNqI09Xhe/bsEVm1apU8jB8REfHA2dn5DSF9RkZGn9LS0u5QI8OS7HBgjSiyevVquKMjIyMfODo6viVWv7Gx8cfU1NQ7lGZYkhy+c+dOUWCNiOzo+w4ODm9JtdTExOQjcpoHZVig40nKsIzEDk+sWLFCev/+/fAaMDo6+r6dnd07CmtZgblz58KLUXt7+xdRUVFPqRriP3/+hKuNiYmh2NGwojIxMRFeVD5+/JiL6lV+fHz8YxYWlv98fHy/bW1tKXY0DFhYWHx4//7947t37/Lk5OTco3pSGe1IjDp81OGjDh91+KjDRx0+6vBRh48AhwMEGABooNI+M6VCVQAAAABJRU5ErkJggg==' /></div>"),

		$qq = $("<div><img style='width:100%;height:100%' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC4AAAAuCAYAAABXuSs3AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2lpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo4RThCNDM4MEM3RkRFNDExQTE1Q0FFMjNDMkM5OTBFQyIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpERjE2OUQ4OTQwQjgxMUU1QUJBOUQwMzNCMzY3MkNGRSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpERjE2OUQ4ODQwQjgxMUU1QUJBOUQwMzNCMzY3MkNGRSIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ0MgKFdpbmRvd3MpIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NjhlNTI5ODMtOWY0OS1lYjQ1LTg4OTctMzRkNzZmNTA4ODI2IiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjhFOEI0MzgwQzdGREU0MTFBMTVDQUUyM0MyQzk5MEVDIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+bChqgwAAAmBJREFUeNrsmDuLGmEUhtdxdL3hMuvsogsmEGO32HhpvIDa+DPsra0s/AfWomCnKAiKoqKFghdsgqggFgYh4A0z6k50xXsyE5LWZEYdd+E75fC9M09xznveb1iDweDLzTss6OadFgAH4AAcgANwAA7AqRR8zpfV63VRPp+/73Q6QgzDbslnKIquFQrFq9lsnqrV6sW5vsU6V8jyeDwfCoXCw7EzJpMJczqd395Mq7jd7k//giarWCyiLpfr85sAT6fT97VaDfnf881m8y4ajT5cFXy327Hi8biUqo7QyFarFXQ18F6vxx0Oh3yqOhzHOe12W3A18NlsxqGrHY/H3KuBZ7NZlK6WGGbJ4XBgHrzb7fIqlYqErr7VaomJQRUyDs7hcH6e6gzb7RZiHDyTyUhOBSe3LKPghHdLksmk7FTwcrmMhsPhR0bACSuD/X7/x3NljmAwKB+NRtyLg+dyOYRcPOcMZ4lEAr04eKPREJ87ohLuIr44+GKx+B2FiZj6nc/n708BJtPin0VGuVUo53Gr1YoplcpXh8PR83q9+1QqJaUDrVKpcDLiIgiyJayV8iY6OY/b7fbnyWRyS0UjEAh2oVCoyWazae8C2j5ODmggEJCt12s2DS3k8/meThly2uCku8Risae/PU+lNpsNROwCaSQSeWQcnMfjHe1LrVY7MxgM2NGPQ/QzHu3LssVieYFh+GupVEKm0yl3Pp/DIpFoL5fLlxqN5ofRaMTJc3q9/qVard71+33+crlkC4XCPTGQG51Oh9tstunVL8vgvwoAB+AAHIADcAAOwC9QvwQYAFR48FwgKGuNAAAAAElFTkSuQmCC' /></div>"),

		$code = $("<div><img style='width:100%;height:100%' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC4AAAAuCAYAAABXuSs3AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2lpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo4RThCNDM4MEM3RkRFNDExQTE1Q0FFMjNDMkM5OTBFQyIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpERjFDNjlGNzQwQjgxMUU1QUJBOUQwMzNCMzY3MkNGRSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpERjFDNjlGNjQwQjgxMUU1QUJBOUQwMzNCMzY3MkNGRSIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ0MgKFdpbmRvd3MpIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NjhlNTI5ODMtOWY0OS1lYjQ1LTg4OTctMzRkNzZmNTA4ODI2IiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjhFOEI0MzgwQzdGREU0MTFBMTVDQUUyM0MyQzk5MEVDIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+KHtc0AAABRhJREFUeNrsWElII00Uzh6TuCTGTCYiuEwELwZFweWgoAiCdw+KKIIeFFE8qCgIogcRvIugBwXFg7iCgorePLj83kT0HxU14xqNiYnZ//dCOtT4x05n0hlmIAVNVzr1qr9+9d73viquXq/f5/yFjcf5S1sUeBR4FHgUOLtNwGTQ3Nzcl6Wlpa9ut5vL4/E8dGNdLhdPKpU6u7u7v2dlZVkMBoOgv79fe3d3FyMUCt1BXsWFd3CKiooM7e3tV2EDR9Amk0nI1BsWi4W/s7OjQOD7+/txl5eXMnz+/v7OZ2K/ubn5hRXg8fHxTgSemJhoT0lJsaBnAo3zeDyc4+PjeKfTyYXmfYarRP2fkZFhjo2NdX5i77m/v4+5vb2NgeZiJVSo8MjPz39ua2u7phtbW1ure319FVJhIRKJ/OHR2dl5kZqaavvMdnFxMWliYiKV+mjWktNms9GOx/gEr3vfyufzPeQdm9FopHXU29sbn9XkJELB74qTkxPJzMyMBpe+paXlWiaTuSExuQHGconE9fcnJyc15+fn0oqKiseSkhIjFWoRAU624eHhb4+Pj2LsA2gXgL9haru9vS1fWFhIxv7R0ZG8sLDwEELKE8r7f5nHSVqEPifElfuZAxnENGse7+np+T41NZUMjONoaGj4EYptWVnZy9nZ2Y+rqytJVVXVAySyJ6LAwTP+F2RmZloHBwf//WkygcATYKz/GZmozc3N+gDzRyZUSGr75MP8QKlEJBMS6wGdPVRcF6sep4rI3t6eoqurS0IXu1ar1Tun3W7nkXdso6Oj6RKJ5FNwT09PIqbswgg48K+33L+8vIjwCiUByVW6uLiQMbFlIg0YAa+urr5ZXV39ajabBegxZBTSKxgieIG3eehhlAbAzwZftTVhqb+5uZFCjLsxHHAsaY+sBCHFwfnxv/Ly8oeg+fa7dvngRR4mL5nAAcQZDxmGCcsIOL+pgXAKJmkxOd2sJuf6+nri8vKyGrVKQkKCk6Q1smGogN4QJCUl2UGWXoKStKEeHxsbS4G7KBDdyeVyR19f3zn2NzY2FGtra6rc3NzXurq627CBT09Pp6Diwz5Iz6DjUQoAAGVTU5N+d3c3AS5lEBMvcNDtktPT0zi8WAGOQgqB412lUtk+oysUVtfX1xLkbtTkPh6nnVupVNqIcHKR97CBU6FRUFBg6OjooN2Z1NTU6HDTQdEgCrBQ1SfrepzczQT1iI896FjkowwA7zsiolXI8g3xKJ6fn1djGW9sbNQjFzscDi6lvwPp8UANV2d8fDwZ7UHjx378GNbpcGBgIPPh4UFMeZWJQoQPvNRqtdaDg4N4+OhkHxPxV1ZWNHSyl1U9Dp7yf/Tz8zOjE4Di4mJjdnb2W05OjimcLWJYHu/t7T2bnZ3VYAmvr6/XM7EBLheq1WpHeno6SuITUvKS1ZzB+UvIwP0vgiJhhuv0w66IQ6fHqUSFIuYCr5sjfpLFtCRjUlGAA+lx6j/ULaCRRL7K6QRR5tXpIMREGCbogLS0tHfW9Pjh4aEcNskCPIYItIlAkHiKhb+BYf6nxymGAV0fNzIyosV+ZWXlXWtrq/esZmho6BsUMCmsjBs20/+EDZwo92K8mNhQlZMsJlQVJc9PyD4kuchny05yole2trZUSFPBqhquBlbN0tLSZ/ydl5dnwmM71NpisZiKcadCobDjfBqNxl/yITwsEC4xOp3O+MfocbZb9GA/CjwKPAo8Ctzb/hNgAKsmhx/LJ0i+AAAAAElFTkSuQmCC' /></div>"),
		$barCode;

	if(window.bar.code){
		$barCode = $("<img src='"+window.bar.code.src+"' />");
		$barCode.css({
			position:'absolute',
			width:window.bar.code.width,
			height:window.bar.code.height,
			left:-window.bar.code.width-3,
			bottom:0,
			display:'none'
		})
		.appendTo($bar);
	}

	$cert
		.css({
			border: _size / 6 + 'px solid transparent',
			borderBottomColor: '#ededed',
			width: 0,
			height: 0,
			position: 'absolute',
			top: -_size / 6 * 2,
			left: _size / 2 - _size / 6
		})
		.appendTo($bar);

	$totop
		.css({
			width: _size,
			height: _size,
			marginBottom: 3
		})
		.on('click', function() {
			$(window).scrollTop(0);
		})
		.appendTo($bar);
	$qq.css({
			width: _size,
			height: _size,
			marginBottom: 3
		})
		.on('click', function() {
			location.hash = 'qq';
			$(window).scrollTop($("a[name=qq]").offset().top);
		})
		.appendTo($bar);
	if (window.bar.code) {
		$code.css({
				width: _size,
				height: _size
			})
			.on('mouseover', function() {
				$barCode.show();
			}).on('mouseout',function(){
				$barCode.hide();
			})
			.appendTo($bar);
	}
	$bar.css({
		cursor: 'pointer',
		position: 'fixed',
		top: '60%',
		right: 0
	}).appendTo($("body"));


})(jQuery, window);