$(document).ready(function() {
	

	// Metainfo ein- und ausklappen mobil
	$('#metainfo h1').on('click', function() {
		$(this).parent().toggleClass('open');
	});
	
	// Kursdetails: Inhaltsabschnitte in Tabs anordnen
	if($('body').hasClass('wisyp_kurs')) {
		tabbify('.wisyr_kursinfos');
	}
	
	// Anbieterdetails: Inhaltsabschnitte in Tabs anordnen
	if($('body').hasClass('wisyp_anbieter')) {
		tabbify('.wisyr_anbieterinfos');
	}
	
	function tabbify(tabclass) {
		var $articles = $(tabclass + ' article');
		
		if($articles.length > 0) {
			var $tabs = $('<div id="wisyr_tabnav" class="wisyr_tabnav_' + $articles.length + 'tabs"></div>');
			$articles.each(function(index) {
				var $tabpane = $(this);
				var tabpane_id = $tabpane.attr('id');
				if(tabpane_id == undefined) { 
					tabpane_id = 'wisyr_tabnav_tabpane' + index;
					$tabpane.attr('id', tabpane_id);
				}
				var tabtitle = $tabpane.data('tabtitle');
				if(tabtitle == undefined) tabtitle = $tabpane.children('h1').html();
				var $tab = $('<a href="#' + $tabpane.attr('id') + '">' + tabtitle + '</a>');
				if(index == 0) {
					$tabpane.addClass('active');
					$tab.addClass('active');
				}
				$tabs.append($tab);
			});
			$(tabclass).addClass('wisyr_tabbed').prepend($tabs);
			
			// Bei Klick Tabs umschalten
			$(tabclass + ' a').on('click', function() {
				$(tabclass + ' .active').removeClass('active');
				$(this).addClass('active');
				$($(this).attr('href')).addClass('active');
				return false;
			});
		}
	}
});