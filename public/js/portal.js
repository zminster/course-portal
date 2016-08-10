var mouseX;
var mouseY;
$(document).mousemove( function(e) {
	mouseX = e.pageX; 
	mouseY = e.pageY;
});

$(".title").mouseenter(function(e) {
	$(this).children(".description").css({
		'top': e.pageY,
		'left':  e.pageX
	}).fadeIn('fast');
})

$(".title").mouseleave(function(e) {
	$(this).children(".description").fadeOut('fast');
})