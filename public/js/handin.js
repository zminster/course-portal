$("#collab").change(function(e) {
	if ($("#collab").prop("checked")) {
		$("#submit").prop("disabled", false);
		$("#submit").removeClass("disabled");
	} else {
		$("#submit").prop("disabled", true);
		$("#submit").addClass("disabled");
	}
});

$("#f_handin").submit(function(e) {
	if (!$("#handin").val()) {
		alert("You must specify at least one file to turn in!");
		return false;
	} else {
		return true;
	}
});