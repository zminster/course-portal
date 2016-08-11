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
		$("#handin").addClass("error");
		return false;
	} else {
		return true;
	}
});