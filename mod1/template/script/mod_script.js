var $j = jQuery.noConflict();

$j(document).ready(function($){
	$('.arrow').click(function() {
	  $(this).find('ul').show();
	  $(this).css('background-position','0px -15px');
	});
	
	$('.label').click(function() {
	  doAction('navigate', $(this).parent().attr('path'));
	});
	
	$('.breadcrumb-item').mouseenter(function() {
	  $(this).css('border-color','#000000').css('cursor', 'pointer');
	  $(this).find('.label').css('border-color','#000000');
	});
	$('.breadcrumb-item').mouseleave(function() {
	  $(this).css('border-color','transparent').css('cursor', 'auto');
	  $(this).find('.label').css('border-color','#ffffff');
	  $(this).find('.arrow').css('background-position','0px 0px').find('ul').hide();
	});
	
	$('.breadcrumb-subitem').click(function(){
	  doAction('navigate', $(this).parent().parent().parent().attr('path') + $(this).html() + '/');
	});

	$('#breadcrumb-dummy').click(function() {
	  $('#breadcrumb').hide();
	  $('#breadcrumb-textual').show().focus();
	});

	$('#breadcrumb-textual').blur(function() {
		$(this).hide();
		$(this).val($(this).attr('current'));  
	  $('#breadcrumb').show();
	});

	$('.item').mouseenter(function() {
		$(this).addClass('bgColor5').addClass('over');
		$(this).find('a').addClass('over');
	});
	$('.item').mouseleave(function() {
		$(this).removeClass('bgColor5').removeClass('over');
		$(this).find('a').removeClass('over');
	});

	$('#magicbar').click(function() {
		if ($(this).data('val')) {
			if ($(this).data('val') == $(this).val()) {
				$(this).val('');
			}
			$(this).css('color', '#000000').css('font-style', 'normal');
		}
		else {
			$(this).data('val', $(this).val());
			$(this).val('');
			$(this).css('color', '#000000').css('font-style', 'normal');
		}
	});

	$('#magicbar').blur(function() {
		if ($(this).val() == '') {
			$(this).val($(this).data('val'));
			$(this).css('color', '#c0c0c0').css('font-style', 'italic');
		}
	});

	$('#magicbar').keyup(function() {
		// If return is pressed - select list items form the entered search pattern.
	});

	$('.silk-textfield_rename').live('click', function(){
		$(this).parent().parent().find('td:eq(1) a').hide('fast');
		$(this).parent().parent().find('td:eq(1) input').show('fast').focus();
	});

});

// This is included into Typo3 BE. Typo3 (for some reason) uses the Prototype JS library.
// To avoid conflictes only code in the document.ready, can make use of $ to reference jQuery.¨
// Outside this area jQuery is referenced by "$j" and Prototype is referenced by "$".

function fetchItem(num, folder, path) {
	// ajax load stuff here...
	$j('#breadcrumb').append('<div class="breadcrumb-item" item="' + (num + 1) + '" path="' + (path + '/' + folder + '/') + '" ><div class="label">' + folder + '</div><div class="arrow"><ul><li class="breadcrumb-subitem">Mappe 1</li></ul></div><div class="div-clr">&nbsp;</div></div>');
}

function doAction(action, id) {
	
	switch(action) {
		case 'navigate':
			document.location.href = '/typo3/mod.php?M=web_txmjjfilemanagerM1&dir=' + escape(id);
		break;
		case 'fileRen':

			var row = $j('tr[fileid="' + id + '"]');
		
			var link = $j('tr[fileid="' + id + '"] .filelist-filename a');
			var field = $j('tr[fileid="' + id + '"] .filelist-filename input');
			
			if (link.text() != field.val()) {
				link.text(field.val());
				link.show('fast');
				field.hide('fast');
				
				$j('#overlay').height($j('.filemanager-table').height()).width($j('.filemanager-table').width()).show();
				$j('#message').text('Renaming ' + link.attr('href'));
				$j('#messagebox').show();
				
				alert('Rename "' + link.attr('href') + '" to ' + field.val());
	
				$j('#messagebox').hide();
				$j('#overlay').hide();
			}
			else {
				link.show('fast');
				field.hide('fast');
			}
		break;
		default:
			alert('You have chosen the action: '+ action);
		break;
		
	}
	
	return false;
}

function toggleItems(obj) {
	$j('.item').each(function(){
		$j(this).find('input[type=checkbox]').attr('checked', obj.checked);
	});
}