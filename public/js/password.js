$("#new_password").focus(function(e) {
	$("#tips").fadeIn(100);
});

// check that new password follows rules
$("#new_password").focusout(function(e) {
	$("#tips").fadeOut(100);
	if (!checkPassword($("#new_password").val())) {
		$("#tips").addClass("error").fadeIn(100).fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
		$("#new_password").addClass("error");
	} else {
		$("#tips").removeClass("error");
		$("#new_password").removeClass("error");
	}
});

// check new password for repeat match
$("#new_password_repeat").keyup(checkMatch);
$("#new_password_repeat").focus(checkMatch);

function checkMatch (e) {
	if ($("#new_password_repeat").val() != $("#new_password").val()) {
		$("#match").addClass("error");
		$("#new_password_repeat").addClass("error");
	}
	else {
		$("#match").removeClass("error");
		$("#new_password_repeat").removeClass("error");
	}
}

function checkPassword(str)
{
	// at least one number, one lowercase and one uppercase letter
	// at least six characters that are letters, numbers or the underscore
	var re = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])\w{6,}$/;
	return re.test(str);
}