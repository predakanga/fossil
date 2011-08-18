function jQueryLoaded() {
    $('a[id$="_toggle"]').click(function() {
    	// Grab the name
    	var id = $(this).attr('id');
    	var trimmed = id.substr(0, id.lastIndexOf('_'));
    	var hide_div = $('#' + trimmed);
    	if(hide_div.is(':visible')) {
    		// Make the text show +
    		$(this).text($(this).text().replace("[-]", "[+]"));
    	} else {
    		// Make the text show -
    		$(this).text($(this).text().replace("[+]", "[-]"));
    	}
    	hide_div.slideToggle();
    	return false;
    });
    $('#errors .bt').click(function() {
    	$(this).parent().parent().next().toggle();
    });
}
google.setOnLoadCallback(jQueryLoaded);