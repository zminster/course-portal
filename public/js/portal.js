var mouseX;
var mouseY;
$(document).mousemove( function(e) {
	mouseX = e.pageX; 
	mouseY = e.pageY;
});

$(".overlay").mouseenter(function(e) {
	$(this).children(".description").css({
		'top': e.pageY,
		'left':  e.pageX
	}).fadeIn('fast');
});

$(".overlay").mouseleave(function(e) {
	$(this).children(".description").fadeOut('fast');
});

// frontend trimester display toggle handler
$(".trimester_select").click(function(e) {
	$(".trimester_select").each(function() {
		$(this).removeClass("selected");
	});
	$(".trimester_display").each(function() {
		$(this).removeClass("selected");
	});

	var trimester = $(this).attr("id");
	$("#" + trimester).addClass("selected");
	$("#" + trimester + "_display").addClass("selected");
});