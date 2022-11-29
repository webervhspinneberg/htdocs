function glossar_optimierung() {
	var m_container = jQuery("#magnify_container");

	if(m_container.length > 0) {
		
		jQuery(".faqelement img").mouseenter(
		  function() {
	
		    try {
		      jQuery("#magnify_container img").remove();
		      m_container.hide();
		    }
		    catch(err) {
			;
		    }
		    
		    
		    m_container.append("<img src='"+jQuery(this).attr("src").replace(/.jpg/, '_big.jpg')+"'/><div id='schl'>X</div>")
		    .click( 
		      function() { 
						jQuery(this).find("img").remove();
						jQuery(this).find("#schl").remove();
						m_container.hide();
		      }
		    );
		    
		});
		
		jQuery(".faqelement img").click(
		  function() {
			m_container.show();
			jQuery(".wisyp_glossar #magnify_container #schl").css("margin-top", (-1)*(jQuery(".wisyp_glossar #magnify_container img").height()+24));
		});
		
	}
}

$(document).ready(function(){
	glossar_optimierung();
	
	// Insert Alle Angebote des Anbieters oben
	if(jQuery(".wisyr_anbieter_kurselink").length)
		jQuery(".wisyr_anbieter_kurselink").clone().insertBefore(".wisyr_anbieter_firmenportraet").addClass("clone");
});

