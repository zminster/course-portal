$('#big-back').click(function() {
	$('#backend').attr('src', '/backend/');
});

$('#backend').on('load', function() {
	var iframe_height  = document.getElementById("backend").contentWindow.document.body.scrollHeight;
	alert(iframe_height);
	$('#backend').height(iframe_height);
})