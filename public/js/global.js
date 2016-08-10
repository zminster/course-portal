var greetings = ["Hello", "Hey there", "Hi", "Howdy", "Hey", "How's it going", "What's up", "How's life", "Good to see you", "Nice to see you", "It's been a while", "Long time no see", "Greetings", "Bonjour", "Hola", "Guten Tag", "Welcome"];
$(function() {
	var r = Math.floor(Math.random() * (greetings.length + 1));
	$("#greeting").html(greetings[r]);
});

$("#user").click(function(e) {
	$("#user_flyout").toggle('slow');
});