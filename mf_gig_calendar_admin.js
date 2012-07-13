//$().ready(function() {
jQuery(document).ready(function( $ ) {	
	$(".datepicker").datepick({
		dateFormat: 'yyyy-mm-dd',
		onSelect: function(dates) { 
			if ($("#multi").is(':checked')) {
				// check the end day is greater
				if ($("#start_date").val() > $("#end_date").val()) {
					$("#end_date").val($("#start_date").val());
				}
			}
			else {
				// single day! make em match
				$("#end_date").val($("#start_date").val());
			}
		}
	});
	
	if ($("#start_date").val() == $("#end_date").val()) {
		$("#end_date_row").hide();
	}
	else {
		$("#multi").attr('checked', true);
	}
	
	$("#multi").click(function() {
		if (this.checked) {
			$("#end_date").val($("#start_date").val());
			$("#end_date_row").fadeIn();
		}
		else {
			$("#end_date_row").fadeOut();
			$("#end_date").val($("#start_date").val());
		}
	});
	

});

function mfgigcal_DeleteEvent(id) {
	if (confirm("Are you sure you want to delete this event from you the database? This is a permanent action.")) {
		document.location.href = "?page=mf_gig_calendar&id=" + id + "&action=delete";
	}
}