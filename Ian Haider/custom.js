jQuery(function(){
	
	var pagePath = window.location.pathname;
	Cookies.remove('urnsSelect', { path: pagePath})
	
    /* header js */
    jQuery(".elementor-menu-toggle").click(function(){
        jQuery("body").toggleClass("stop-scrolling");
    });

    /*next step*/
	jQuery('.populate-packages input[type=radio]').on("change", function() {
		//check if embalming enabled
		if(jQuery(this).parent().find('.isEmbalmed').length){
			jQuery('.gform_wrapper .is_embalming input').val('Yes');
		} else {
			jQuery('.gform_wrapper .is_embalming input').val('No');
		}
		
		//add pkg id to field
		var pkgID = jQuery(this).parent().find('.price-table-column').attr('data-pkgid');
		if(pkgID){
			Cookies.set('PackageID', pkgID, { expires: 7, path: pagePath });
			jQuery('.gform_wrapper .package_ID input').val(pkgID);
		} else {
			jQuery('.gform_wrapper .package_ID input').val('');
		}
		Cookies.remove('casketSelect', { path: pagePath});
		Cookies.remove('vaultSelect', { path: pagePath});
		
		jQuery(this).closest('.gform_page').find('.gform_page_footer .gform_next_button.button').click();
	});
	
	jQuery(document).bind('gform_page_loaded', function(event, form_id, current_page){
		var pagePath = window.location.pathname;
		jQuery('.populate-packages input[type=radio]').bind("click", function() {
			if(jQuery(this).parent().find('.isEmbalmed').length){
				jQuery('.gform_wrapper .is_embalming input').val('Yes');
			} else {
				jQuery('.gform_wrapper .is_embalming input').val('No');
			}
			
			//add pkg id to field
			var pkgID = jQuery(this).parent().find('.price-table-column').attr('data-pkgid');
			if(pkgID){
				Cookies.set('PackageID', pkgID, { expires: 7, path: pagePath });
				jQuery('.gform_wrapper .package_ID input').val(pkgID);
			} else {
				jQuery('.gform_wrapper .package_ID input').val('');
			}
			Cookies.remove('casketSelect', { path: pagePath});
			Cookies.remove('vaultSelect', { path: pagePath});
			jQuery(this).closest('.gform_page').find('.gform_page_footer .gform_next_button.button').click();
		});
		
		/*urns store in cookie*/
		var urnsSelect = jQuery(".merchandiseItems input:checked").map(function() {
			var pr_id = jQuery(this).parent().find('.merchandiseProduct').attr('data-id');
			return pr_id;
		}).get().join(',');

		if(urnsSelect){
			Cookies.set('urnsSelect', urnsSelect, { expires: 1, path: pagePath });
			jQuery('.urns_ids input').val(urnsSelect);
		}

		/*casket store in cookie*/
		var casketSelect = jQuery(".casketItems .selected").map(function() {
			var pr_id = jQuery(this).attr('data-id');
			return pr_id;
		}).get().join(',');

		if(casketSelect){
			Cookies.set('casketSelect', casketSelect, { expires: 1, path: pagePath });
			jQuery('.casket_ids input').val(casketSelect);
		}

		/*vault store in cookie*/
		var vaultSelect = jQuery(".vaultItems .selected").map(function() {
			var pr_id = jQuery(this).attr('data-id');
			return pr_id;
		}).get().join(',');

		if(vaultSelect){
			Cookies.set('vaultSelect', vaultSelect, { expires: 1, path: pagePath });
			jQuery('.vault_ids input').val(vaultSelect);
		}
	});

	/* Handle the basic modal */
	jQuery('.btn_modal a').click(function(event) {
		event.preventDefault();
		var id = jQuery(this).attr('data-id');
		if(id){
			jQuery('#'+id).addClass('show');
			jQuery('body').addClass('overflow');
		}
	});
	jQuery('a.modal-close').click(function(event) {
		event.preventDefault();
		jQuery('.overlay').removeClass('show');
		jQuery('body').removeClass('overflow');
	});
	jQuery('.overlay').click(function(event){
		event.preventDefault();
		jQuery('.overlay').removeClass('show');
		jQuery('body').removeClass('overflow');
	});
	jQuery('.overlay div.modal').click(function(event) {
		event.stopPropagation();
	});


	/*Select unselect mechandise items*/
	jQuery(document).on('change','.merchandiseItems input[type=radio], .casketItems input[type=radio], .vaultItems input[type=radio]', function(){
		var pagePath = window.location.pathname;
		if(jQuery(this).parent().find('.clearItem').length === 0){
			jQuery('.merchandiseItems.activeTab .clearItem').remove();
			
			/*urns store in cookie*/
			var urnsSelect = jQuery(".merchandiseItems input:checked").map(function() {
				var pr_id = jQuery(this).parent().find('.merchandiseProduct').attr('data-id');
				return pr_id;
			}).get().join(',');
			
			if(urnsSelect){
				jQuery(this).parent().append('<span class="clearItem"><i class="fas fa-times"></i></span>');
				Cookies.set('urnsSelect', urnsSelect, { expires: 1, path: pagePath });
				jQuery('.urns_ids input').val(urnsSelect);
			}
			
			/*casket store in cookie*/
			var casketSelect = jQuery(".casketItems input:checked").map(function() {
				var pr_id = jQuery(this).parent().find('.merchandiseProduct').attr('data-id');
				return pr_id;
			}).get().join(',');
	
			if(casketSelect){
				Cookies.set('casketSelect', casketSelect, { expires: 1, path: pagePath });
				jQuery('.casket_ids input').val(casketSelect);
			}

			/*vault store in cookie*/
			var vaultSelect = jQuery(".vaultItems input:checked").map(function() {
				var pr_id = jQuery(this).parent().find('.merchandiseProduct').attr('data-id');
				return pr_id;
			}).get().join(',');

			if(vaultSelect){
				Cookies.set('vaultSelect', vaultSelect, { expires: 1, path: pagePath });
				jQuery('.vault_ids input').val(vaultSelect);
			}
			
			setTimeout(function(){ 
				var $container = jQuery("html,body");
				var $scrollTo = jQuery('.merchandiseTotalField');
				if($scrollTo.length){
					$container.animate({scrollTop: $scrollTo.offset().top},800); 
				}
			}, 1000);
		}
	});

	
	
	jQuery(document).on('click','.merchandiseItems .clearItem', function(){
		jQuery(this).parent().find('input').prop('checked', false);
		jQuery(this).remove();
		var urnsarray = []; 
		jQuery(".merchandiseItems input:checked").each(function() { 
			urnsarray.push(jQuery(this).parent().find('.merchandiseProduct').attr('data-id'));
		});
		jQuery('.gform_wrapper .urns_ids input').val(urnsarray);
		jQuery(document).trigger('gform_post_render', [11,33]);
	});

	/**
	 *	Calculate age by date of birth of deceased 
	 */
	jQuery(window).load(function(){
		if(jQuery('.gform_wrapper').length !== 0){
			gform.addAction( 'gform_input_change', function( elem, formId, fieldId ) {
				if(fieldId == 85 || fieldId == 131){
					var age = '';
					var current_date = new Date(),
						birth = jQuery('#input_'+formId+'_85').val(),
						death = jQuery('#input_'+formId+'_131').val(),
						birth_date = new Date(birth);
					if(death){
						var current_date = new Date(death);
					}
					if(birth_date){
						var d = Math.abs(current_date - birth_date) / 1000;
						var age = {};
						var s = {                                                                  // structure
							year: 31536000,
							month: 2592000,
							//week: 604800, // uncomment row to ignore
							day: 86400,   // feel free to add your own row
							//hour: 3600,
							//minute: 60,
							//second: 1
						};
						Object.keys(s).forEach(function(key){
							age[key] = Math.floor(d / s[key]);
							d -= age[key] * s[key];
						});
					}
				}
				if(age){
					var _age='';
					if(age.year){
						if(age.year > 1){
							_age += age.year+' years ';
						} else {
							_age += age.year+' year ';
						}
					}
					if(age.month){
						if(age.month > 1){
							_age += age.month+' months ';
						} else {
							_age += age.month+' month ';
						}
					}
					if(age.day){
						if(age.day > 1){
							_age += age.day+' days';
						} else {
							_age += age.day+' day';
						}
					} 
					jQuery('.gf_calculateAge input').val(_age).prop('readonly', true);
				}
			}, 10, 3 );
		}
	})
	
	jQuery(document).on('gform_post_render', function(event, form_id, current_page){
 		if(form_id == 11 || form_id == 20){
			var type = jQuery("input[name='input_198']:checked").val();
			if(!type)
				return false;
			jQuery('.gf_package_type input').val(type);
		}

		if(form_id == 18){
			var type = jQuery("input[name='input_243']:checked").val();
			console.log(type);
			if(!type)
				return false;
			jQuery('.gf_package_type input').val(type);
		}

		if(form_id == 19){
			var type = jQuery("input[name='input_243']:checked").val();
			if(!type)
				return false;
			jQuery('.gf_package_type input').val(type);
		}
    });

	jQuery(document).on('gform_post_render', function(event, form_id, current_page){
		if(form_id == 11 || form_id == 20){
		   	var pkgId = jQuery('.gform_wrapper .package_ID input').val();
			if(pkgId == 1126){
				jQuery('.utilityInc input').val('true');
				jQuery('.conditionalTransportation .gchoice.gchoice_'+form_id+'_177_3').hide();
			} else {
				jQuery('.utilityInc input').val('false');
				jQuery('.conditionalTransportation .gchoice.gchoice_'+form_id+'_177_3').show();
			}
	   	}

		if(form_id == 18){
			var pkgId = jQuery('.gform_wrapper .package_ID input').val();
			if(pkgId == 1488){
				//jQuery('.utilityInc input').val('true');
				jQuery('.conditionalTransportation .gchoice.gchoice_'+form_id+'_177_1').hide();
				jQuery('.conditionalTransportation .gchoice.gchoice_'+form_id+'_177_3').hide();
			}
		}

		if(form_id == 19){
			var pkgId = jQuery('.gform_wrapper .package_ID input').val();
			if(pkgId == 1934){
				jQuery('.conditionalTransportation .gchoice.gchoice_'+form_id+'_177_1').hide();
				jQuery('.conditionalTransportation .gchoice.gchoice_'+form_id+'_177_3').hide();
			} else {
				jQuery('.conditionalTransportation .gchoice.gchoice_'+form_id+'_177_3').hide();
			}
		}
		   
   });

	jQuery(document).on('click','.merchandiseTabs a', function (e){
		e.preventDefault();
		var divClass = jQuery(this).attr('data-id');
		if(divClass){
			jQuery('.merchandiseTabs a').removeClass('active');
			jQuery(this).addClass('active');
			jQuery('.merchandiseItems').hide().removeClass('activeTab');
			jQuery('.'+divClass).show().addClass('activeTab');
		}
	})
	
	// jQuery(document).on('change', '.togglePkgType input[type="radio"]', function(e){
	// 	var type = jQuery(this).val();
	// 	jQuery(this).prop('checked', true);
	// 	if(type == 'preneed'){
	// 		jQuery('.purchaseBtn').text('Plan Now');
	// 	} else {
	// 		jQuery('.purchaseBtn').text('Purchase Now');
	// 	}
		
	// })

	jQuery(document).on('click', '.docusignPowerBtn', function(e){
		e.preventDefault();
		jQuery.ajaxSetup({cache: false});
		var redirectUrl = jQuery(this).attr('href'),
			page_id = jQuery(this).attr('data-id');
		jQuery.ajax({
			url: ajx_obj.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'store_powerform_data',
				data: { url : redirectUrl, page_id : page_id},
			},   
			beforeSend: function(){
				
			},
			success: function(response) {
				//jQuery(this).prop('disabled', false);
				if(response.success){
					window.location = response.url;
				}
			}
		});
	})

	jQuery(document).on('click','.viewMoreInfo', function (e){
		e.preventDefault();
		var divID = jQuery(this).attr('data-id');
		if(divID){
			jQuery('.package_Content#'+divID).toggleClass('active');
		}
	})
})



function GetURLParameter(sParam){
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            return sParameterName[1];
        }
    }
}