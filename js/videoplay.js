// When user clicks a video
$('body').on('click', 'a.video', function(){

	var myVideo = $(this).attr('href');
	var videotitle = $(this).attr('title');

	$('#info a, #paginationdiv').hide();
	$('#frame').attr("src", "http://www.youtube.com/embed/"+ myVideo +"?rel=0&autoplay=1&vq=hd360").show();

	$('#videoinfo').text(videotitle);

	return false;         
});