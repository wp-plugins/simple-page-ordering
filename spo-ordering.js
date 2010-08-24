jQuery("table.widefat tbody").sortable({  
	cursor: 'move',
	axis: 'y',
	containment: 'table.widefat',
	scrollSensitivity: 40,
	helper: function(e, ui) {					
		ui.children().each(function() { jQuery(this).width(jQuery(this).width()); });
		return ui;
	},
	start: function(event, ui) {
		if ( ! ui.item.hasClass('alternate') ) ui.item.css( 'background-color', '#ffffff' );
		ui.item.children('td, th').css('border','none');
		ui.item.css( 'outline', '1px solid #dfdfdf' );
	},
	stop: function(event, ui) {		
		ui.item.removeAttr('style');
		ui.item.children('td, th').removeAttr('style');
	},
	update: function(event, ui) {	
		if ( ui.item.hasClass('inline-editor') ) {
			jQuery("table.widefat tbody").sortable('cancel');
			alert( 'Please close the quick editor before reordering this item.' );
			return;
		}
		
		var postid = ui.item.find('.check-column input').val();	// this post id
		var postparent = ui.item.find('.post_parent').html(); 	// post parent
		
		var prevpostid = ui.item.prev().find('.check-column input').val();
		var nextpostid = ui.item.next().find('.check-column input').val();
		
		// can only sort in same tree
				
		var prevpostparent = undefined;
		if ( prevpostid != undefined ) {
			var prevpostparent = ui.item.prev().find('.post_parent').html()
			if ( prevpostparent != postparent) prevpostid = undefined;
		}
		
		var nextpostparent = undefined;
		if ( nextpostid != undefined ) {
			nextpostparent = ui.item.next().find('.post_parent').html();
			if ( nextpostparent != postparent) nextpostid = undefined;
		}	
		
		// if previous and next not at same tree level, or next not at same tree level and the previous is the parent of the next, or just moved item beneath its own children 					
		if ( ( prevpostid == undefined && nextpostid == undefined ) || ( nextpostid == undefined && nextpostparent == prevpostid ) || ( nextpostid != undefined && prevpostparent == postid ) ) {
			jQuery("table.widefat tbody").sortable('cancel');
			alert( "Items can only be repositioned within their current branch in the page tree / hierarchy (next to pages with the same parent).\n\nIf you want to move this item into a different part of the page tree, use the Quick Edit feature to change the parent before continuing." );
			return;
		}
					
		// show spinner
		ui.item.find('.check-column input').hide().after('<img alt="processing" src="images/wpspin_light.gif" class="waiting" style="margin-left: 6px;" />');
		
		// go do the sorting stuff via ajax
		jQuery.post( ajaxurl, { action: 'simple_page_ordering', id: postid, previd: prevpostid, nextid: nextpostid }, function(response){			
			if ( response == 'children' ) window.location.reload();
			else ui.item.find('.check-column input').show().siblings('img').remove();
		});
		
		// fix cell colors
		jQuery( 'table.widefat tbody tr' ).each(function(){
			var i = jQuery('table.widefat tbody tr').index(this);
			if ( i%2 == 0 ) jQuery(this).addClass('alternate');
			else jQuery(this).removeClass('alternate');
		});
	}
}).disableSelection();