(function($) {
	$(document).ready(function() {
		$("#linked-entry").parent().hide();
	});

	/*----	Section Box Change ----*/
	$("#context").live('change', function() {
		var sectionID = $('option:selected', $(this)).val(),
		    linked = $('#linked-section');

		linked.attr("disabled",false)
			.empty();

		linked.parent().slideDown("fast", function() {
			$("a.no-section").fadeIn("fast");
			$("#linked-entry").parent().slideUp("fast");

			$.get("../ajaxfields/", {section: sectionID}, function(data) {
				var options = "";
				$(data).find('field').each(function() {
					options += "<option value='" + $(this).attr('id') + "'>" + $(this).text() + "</option>";
				});
				if(options == "") {
					options = "<option value='' selected='selected'>No Available Links</option>";
				} else {
					options = "<option value='' selected='selected'>Where..</option>" + options;
				}
				linked.prepend(options);
			}, "xml");

		});
	});

	/*---- Linked Field Change ----*/

	$("#linked-section").live('change', function() {
		var linkedID = $('option:selected', $("#linked-section")).val(),
		    sectionID = $('option:selected', $('#context')).val(),
		    entries = $("#linked-entry");

		entries.attr("disabled",false)
				.empty();

		entries.parent().slideDown("fast", function () {

			$.get("../ajaxentries/", {section: sectionID, field: linkedID}, function(data) {
				var options = "";
				$(data).find('entry').each(function() {
					options += "<option value='" + $(this).attr('id') + "'>" + $(this).text() + "</option>";
				});
                                if(options == "") {
                                        options = "<option value='' selected='selected'>No Entries</option>";
                                } else {
                                        options = "<option value='' selected='selected'>is..</option>" + options;
                                }
                                entries.prepend(options);
			}, "xml");

		});
	});

})(jQuery);
