$(document).ready(
	function() {
		$('#myFlash').flash({
				// test_flashvars.swf is the flash document
				swf: '/files/UmiVideoPlayer.swf',
				width: '465px',
				height: '330px',
				// these arguments will be passed into the flash document
				flashvars: {
					video: '/files/umi_cms_move.mp4',
					autoplay: false,
				}
			}
		);
	}

);
$(document).ready(
	function() {
		$('#myFlash2').flash({
					// test_flashvars.swf is the flash document
					swf: '/files/UmiVideoPlayer.swf',
					width: '465px',
					height: '330px',
					// these arguments will be passed into the flash document
					flashvars: {
						video: '/files/tpl-templater.mp4',
						autoplay: false,
					}
				}
			);
		}
);