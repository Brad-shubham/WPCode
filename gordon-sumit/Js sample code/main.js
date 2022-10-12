/* main wp, jQuery */
/**
 * File main.js.
 *
 * Theme feature enhancement mainly.
 *
 * The file contains script for the custom script to enhance or alter theme features.
 */

(function ($) {

    //preloader script
    $('#preloader').trigger('play')
    var preloader = {
        init:function (){
            window.addEventListener('load',this.onWindowReady)
        },
        onWindowReady:function () {
          $('.preloader-wrapper').addClass('preloader-hidden')
            setTimeout(function (){
                $('.preloader-wrapper').addClass('d-none').removeClass('preloader-hidden')
                $('#preloader').trigger('stop')
            },1000)
            $('body').removeClass('scroll-off')
        }
    }

    const findLocation = () => {
        const $locationForm = $('#locationForm');
        let inProgress = false;
        const $locationFormSumbit = $locationForm.find('#btn-your-location');
        const $zipInput = $locationForm.find('#zip-code1');

        $locationForm.on('submit', (e) => {
            e.preventDefault();
            let zip = $zipInput.val();

            let validationForm = !!$locationForm.find('.invalid').length;

            if (!validationForm) {
                if (inProgress) return
                inProgress = true;
                $locationFormSumbit.attr('disabled', true)
                const submitText = $locationFormSumbit.attr('value');
                $locationFormSumbit.attr('value', "Processing...")
                

                $.ajax({
                    type: "GET",
                    url: main_object.reserve_dumpster.actions.search_location.route,
                    data: {
                        zip_code: zip,
                        type: 'location'
                    },
                    complete: function () {
                        $locationFormSumbit.attr('disabled', null);
                        setTimeout(() => {
                          $('.location-form #zip-code1').val("");
                          $locationFormSumbit.attr('value', submitText);
                        }, 400);
                        inProgress = false;
                    },
                    success: function (response) {
                        $('#locationModal').modal('hide');
                        window.location.href = response['locationUrl'];
                    },
                    error: function(){
                        $('#locationModal').modal('hide');
                        $('#errorModal').modal('show');
                    }
                });
            }
        })

        $zipInput.on('input', validateField)

        function validateField() {
            $(this).parent().removeClass('invalid');
            const value = $(this).val();
            let validationField = /^[0-9]{5}[-]?[0-9]{4}$/.test(value) || /^[0-9]{5}$/.test(value);

            if (validationField){
                return validationField
            }

            $(this).parent().addClass('invalid');
            $(this).parent().find('.text-error').text("Please fill this field correctly");
            return validationField;
        }
    }

    let hasVideoTag=false;

    //how it work tab
    var tabControl = {
        init(){
            var tabs = $('.tabbed-content').find('.how-it-works-tab');
            if (tabs.is(':visible')) {
                tabs.find('a').on('click', function (event) {
                    event.preventDefault();
                    var target = $(this).attr('href'),
                        tabs = $(this).parents('.how-it-works-tab'),
                        buttons = tabs.find('a'),
                        item = tabs.parents('.tabbed-content').find('.item');
                    buttons.removeClass('active');
                    item.removeClass('active');
                    $(this).addClass('active');
                    $(target).addClass('active');
                });
            } else {
                $('.item').on('click', function () {
                    var container = $(this).parents('.tabbed-content'),
                        currId = $(this).attr('id'),
                        items = container.find('.item');
                    container.find('.how-it-works-tab a').removeClass('active');
                    items.removeClass('active');
                    items.slideDown();
                    $(this).addClass('active');
                    container.find('.how-it-works-tab a[href$="#' + currId + '"]').addClass('active');
                });
            }
        }
    }

    let mobileTab = $('.mobile-title');
    for (i = 0; i < mobileTab.length; i++) {
        let items = mobileTab.closest('.item').prev();
        mobileTab[i].addEventListener("click", function () {
            items.removeClass('active');
            if (window.matchMedia('(min-width: 768px)').matches) {
                $('html, body').animate({
                    scrollTop: $(this.closest('.item')).offset().top - 106
                }, 200);
            } else {
                $('html, body').animate({
                    scrollTop: $(this.closest('.item')).offset().top - 90
                }, 200);
        }
    });
    }


    //resource page load more
    var loadMore = {
        pagedElm: $('#currentPage'),
        maxPages: $('#maxPages'),
        landscapeCardElm: $(".col-md-6.col-xl-4"),
        loadCount:0,
        init: function () {
            $(document).on('click', '#loadMore', this.loadPosts)
            //window.addEventListener("resize", this.OnScreenResize); //remove on screen resize issue
            window.addEventListener("load", this.OnScreenResize);
            window.addEventListener("popstate", (event) => {
                event.preventDefault();

                window.location.href = document.location;
            });

        },
        loadPosts: function (e) {
            $thisElm = $(this)
            $thisElm.html(main_object.loadingText)
            var requestUrl = $thisElm.attr("href");

            e.preventDefault()
            if (loadMore.loadCount === 0) {
                let nonce = $(this).data('nonce')
                let author = $(this).data('author')
                let haveDescription = $(this).data('no-description')
                let perPage = $(this).data('per-page')
                let referenceUrl = $(this).data('reference-url')
                let currentPaged = parseInt(loadMore.pagedElm.val()) + 1
                $.ajax({
                    type: "post",
                    dataType: 'JSON',
                    url: main_object.ajax_url,
                    data: {
                        action: main_object.loadPost,
                        nonce: nonce,
                        author:(author)?author:'',
                        maxPage: 'maxPage',
                        haveDescription: haveDescription,
                        paged: currentPaged,
                        perPage,
                        referenceUrl
                    },
                    success: function (res) {
                        loadMore.loadCount = 0
                        loadMore.landscapeCardElm.removeClass('d-none')
                        $('.load-post-here').append(res.response)
                        if (currentPaged === parseInt(loadMore.maxPages.val())) {
                            $thisElm.hide()
                        }
                        $thisElm.html(main_object.showMoreText)
                        loadMore.pagedElm.val(currentPaged)
                        $thisElm.attr('href',res.requestUrl)
                    },
                    complete: function (res) {
                        window.history.pushState({}, "", requestUrl);
                    },

                });
            }

            loadMore.loadCount++
        },
        OnScreenResize: function (e) {
            var width = window.screen.width
            loadMore.landscapeCardElm.removeClass('d-none')
            if (width < 768) {
                $(".col-md-6.col-xl-4").slice(-4).addClass('d-none')
            } else {
                if (width < 1200) {
                    $(".col-md-6.col-xl-4").slice(-3).addClass('d-none')
                } else {
                    loadMore.landscapeCardElm.removeClass('d-none')
                }
            }
        }
    }

    // testimonal-section
    var loadSwiper = {
        init: function () {

            // birdEye feedback slider
            var swiper = new Swiper(".birdEye-feedback-slider", {
                pagination: {
                    el: ".swiper-pagination",
                    type: 'custom',
                    renderCustom: function (swiper, current, total) {
                        return ('0' + current).slice(-2) + '<span class = "pagination-line mb-0"></span>' + ('0' + total).slice(-2);
                    }
                },
                autoHeight:true,
                navigation: {
                    nextEl: '.testimonial-button-next',
                    prevEl: '.testimonial-button-prev',
                },
            });

            //video testimonial slider
            var thumbsSlider = new Swiper('.gallery-thumbs', {
                loop: true,
                spaceBetween: 4,
                slidesPerView: 4,
                freeMode: true,
                watchSlidesProgress: true,
            });
            var mainVideoSlider = new Swiper('.gallery-slider', {
                slidesPerView: 1,
                centeredSlides: true,
                loop: true,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                thumbs: {
                    swiper: thumbsSlider,
                },
            });

            mainVideoSlider.on('slideChange', function () {
               hasVideoTag = false
            });

            // resources-section
            var swiperResources = new Swiper(".resources-swiper", {
                on: {
                    beforeInit: function () {
                        const swiperElm = $('.resources-swiper')
                        var cloneElm = swiperElm.find('.swiper-slide:last').clone()
                        cloneElm.addClass('visibility-hidden')
                        var count = swiperElm.find('.swiper-wrapper').find('.featured-resource').length
                        for (var i = 0; i < count; i++) {
                            swiperElm.find('.swiper-wrapper').append(cloneElm.clone())
                        }
                    }
                },
                slidesPerView: 4,
                slidesPerGroup: 3,
                loop: false,
                speed: 1500,
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                navigation: {
                    nextEl: ".resources-btn-next",
                    prevEl: ".resources-btn-prev",
                },
                breakpoints: {
                    320: {
                        slidesPerView: 1.2,
                        slidesPerGroup: 1,
                    },
                    768: {
                        slidesPerView: 2.5,
                        slidesPerGroup: 2,
                    },
                    1200: {
                        slidesPerView: 4
                    }
                }
            });

            $(".resources-section .swiper-slide:has(img)").css("padding", "0 20px 32px");
        }
    }

    //video popup
    var videoPopup = {
        popupModal: $('.popup-modal'),
        iframeElm: $("iframe"),
        init() {
            //on video popup open
            videoPopup.popupModal.on('shown.bs.modal', function () {
                var elm = $('.popup-video-wrapper')
                var $this = $(this)
                var link = videoPopup.iframeElm.attr('src');
                elm.removeClass('play-btn-icon')
                $this.find('video').trigger('play')
                $this.find('iframe').attr('src', link + "?autoplay=1")
            })

            //stop youtube video on popup close
            videoPopup.popupModal.on('hidden.bs.modal', function () {
                var $this = $(this)
                $this.find('video').trigger('pause')
                var iframeSrc = $this.find('iframe').attr('src');
                if(iframeSrc){
                    var urlSplit = iframeSrc.split("?");
                    iframeSrc = urlSplit[0];
                    $this.find('iframe').attr('src', iframeSrc);
                }
            })
            $(document).on('click', '#onPlayVideo', function () {
                var slideElm = $('.single-testimonial-video')
                var $videoFrame;
                if (slideElm.find('iframe').length) {
                    $videoFrame = slideElm.find('iframe').clone().removeClass('d-none')
                } else {
                    $videoFrame = slideElm.find('video').clone().removeClass('d-none')
                }
                    $('.video-popup-area').html($videoFrame)
            })


            $(document).on('click', '.gallery-video-slide', function (e) {
                e.stopPropagation()
                var slideElm = $(this)
                var $videoFrame;
                if (slideElm.find('iframe').length) {
                    $videoFrame = slideElm.find('iframe').clone().removeClass('d-none')
                } else {
                    $videoFrame = slideElm.find('video').clone().removeClass('d-none')
                }
                    $('.video-popup-area').html($videoFrame)
                    $('#testimonialModal').modal('show');

            })

        }
    }

    preloader.init()
    tabControl.init()
    loadMore.init()
    loadSwiper.init()
    videoPopup.init()


    // Header click, go back, show menu, hide menu , stop scolls functionality handling through JS
    if (window.matchMedia('(max-width: 1199px)').matches) {
        $('.dropdown-toggle').click(function(e) {
            $(this).closest('.dropdown').toggleClass('show-dropdown');
            $('.dropdown-location').find('.dropdown-menu').removeClass('show-location-dropdown')
        })

        $('.header-loc-sm').on('click', function(){
            $('.navbar-collapse').removeClass('show');
            $('.navbar-toggler').attr('aria-expanded', 'false');
        })

        $('.go-back-btn').click(function(){
            let dropdown = $(this).closest('.dropdown');
            if (dropdown.hasClass('show-dropdown')) {
                dropdown.removeClass('show-dropdown');
            }
            $('.dropdown-menu').removeClass('show-location-dropdown');
        })

        $('.navbar-toggler').on('click', function(){
            $('.navbar-collapse').removeClass('d-none');
            let dropdown = $('.navbar-collapse .dropdown');
            if (dropdown.hasClass('show-dropdown')) {
                dropdown.removeClass('show-dropdown');
            }
            if($(this).attr("aria-expanded") === "true") {
                $('.navbar-collapse').addClass('d-none');
            }
            $('.non-collapsable .dropdown').removeClass('show-dropdown');
        })
    }

    // $('.nav-to-dropdown').click(function(e){
    //     e.preventDefault()
    //     const dropdownElm = $('.header-loc-sm').next()
    //     dropdownElm.toggleClass('show-location-dropdown');
    //     dropdownElm.mouseleave(function(){
    //         $(this).removeClass('show-location-dropdown');
    //     })
    // });

    // close dropdown when clicking outside
    $(document).click(function(e){
        if ($(e.target).is('.nav-to-dropdown, #navbarDropdown, .dropdown-menu')) {
            return;
        }
        else
        {
            $('.header-loc-sm').next().removeClass('show-location-dropdown');
        }
    });

    //Global tabs(accordion on mobile) Js
    var resizeTimer;
    $(window).on('resize', function (e) {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            tabControl.init();
        }, 250);
    });


    // autocomplete off
    $('input').attr('autocomplete', 'off');

    //toggle play-btn-icon when video play and pause on hero-banner
    let vid = document.getElementById("heroBannerVideo");

    // JS for pausing popup video outside clicking of modal
    $("#heroBannerModal").on('hidden.bs.modal', function (e) {
        $("#heroBannerModal iframe").attr("src", $("#heroBannerModal iframe").attr("src"));
    });

    $("#testimonialModal").on('hidden.bs.modal', function (e) {
        $("#testimonialModal iframe").attr("src", $("#testimonialModal iframe").attr("src"));
    });

    const textarea = $("textarea");

    textarea.oninput = function () {
        textarea.style.height = ""; /* Reset the height*/
        textarea.style.height = Math.min(textarea.scrollHeight, heightLimit) + "px";
    };


    // The function toggles more (hidden) text when the user clicks on "Read more". The IF ELSE statement ensures that the text 'read more' and 'read less' changes interchangeably when clicked on.

    let isExpanded = false
    $('.read-more-btn').on('click', function(e) {
        e.preventDefault();

        $('.expandable-text').slideToggle()

        isExpanded = !isExpanded
        if (isExpanded){
            $(this).text(main_object.learnLessText)
        }else{
            $(this).text(main_object.learnMoreText)
        }

        if($(this).hasClass('is-testimonial-expand')===false){
            if (!isExpanded) {
                $('html, body').animate({
                    scrollTop: $('.scroll-to-top').offset().top - 90
                }, 200);
            }
        }

        if($(this).hasClass('is-not-expanded')){
            let content = $(this).closest('.row');
            let contentImg = content.children('.about-img-wrapper');
            let contentHeight = content.height();
            contentImg.css("max-height", contentHeight);
        }
        $(this).toggleClass('is-not-expanded')

    });
    // Document ready ends

    //sticky logo
    let mainLogoEl = $('.main-logo-img')
    let stickyLogoEl = $('.sticky.sticky-icon')
    $(window).on("scroll", function () {
        if ($(window).scrollTop() > 50) {
            $("body").addClass("header-active");
            if ($('body').hasClass('header-active')) {
            }
        } else {
            //remove the background property so it comes transparent again (defined in your css)
            $("body").removeClass("header-active");
        }

    });

    const closeFormNegative = () => {
        $('#reserveModal #reserveForm').fadeIn(300);
        if($('.modal-negative.show').length > 0) {
            $('#info-msg').fadeIn(300);
            $('#negativeModal').modal('hide');
        }
    }


    var removeExistingPreviousButton = ()=>{
        $('.nf-next-previous').find('li.nf-previous-item').remove();
    }

    // ninja form trigger actions
    $(document).ready(function ($) {

        $('#orderNowPopupModal').on('hidden.bs.modal', function (e) {
            var responseElem = $('#orderNowFormWrapper')
            $(".contact-popup-icon").remove()
            responseElem.find('.popup-content').remove()
            responseElem.removeClass('d-xl-flex order-form-submitted')
            responseElem.find('form').show()
            responseElem.find('.nf-previous').click()
            responseElem.find('h2.hide-on-submit').show()
        });

        $('#locationModal').on('shown.bs.modal', function () {
            $('html').addClass('overflow-hidden');
        });
        findLocation();

        $('#locationModal').on('hidden.bs.modal', function (event) {
            $('html').removeClass('overflow-hidden');
        })

        $('a[data-target="#orderNowPopupModal"]').on('click', function (e) {
            var product = $(this).data('product')
            if(product){
                $('#orderNowFormWrapper').find('input[type="radio"]').each(function (){
                    if(product.includes($(this).val())){
                        $(this).click()
                    }
                })
            }
        })

        if(window.Marionette){
            var myCustomFieldController = Marionette.Object.extend({
                initialize: function () {
                    var formChannel = Backbone.Radio.channel('forms')
                    this.listenTo(formChannel,'submit:response',this.onSubmitResponse)

                    // on the Field's model value change...
                    var fieldsChannel = Backbone.Radio.channel('fields');
                    this.listenTo(fieldsChannel, 'before:submit', this.changeLabelOnSubmit);
                    this.listenTo(fieldsChannel, 'change:modelValue', this.validateFields);

                    // On the Form Submission's field validaiton...
                    var submitChannel = Backbone.Radio.channel('submit');
                    this.listenTo(submitChannel, 'validate:field', this.validateFields);
                },
                changeLabelOnSubmit:function (model){
                    jQuery('#nf-form-'+model.get('formID')+'-cont').find('input[type="button"]').val('Processing')
                },
                validateFields: function (model) {
                    // Only validate if the field is marked as required?
                    if (0 == model.get('required')) return;

                    if ('' == model.get('value')) return;

                    if ('listselect' === model.get('type')) {
                        if (model.get('value') !== 'how-did-you-hear-about-us') {
                            Backbone.Radio.channel('fields').request('remove:error', model.get('id'), 'custom-field-error');
                        } else {
                            Backbone.Radio.channel('fields').request('add:error', model.get('id'), 'custom-field-error', 'Please select any option.');
                        }
                    }


                },
                onSubmitResponse:function (response, textStatus, jqXHR,formId){
                    var form_id = response.data.form_id;
                    if(parseInt(form_id)===1){
                        if(response==='error'){
                            $('.response-icon').attr('src',main_object.errorIcon)
                            $('.response-heading').text('error')
                            $('.response-msg').html(formId)
                        }else{
                            if(response.errors.length){
                                $('.response-icon').attr('src',main_object.errorIcon)
                                $('.response-heading').text('error')
                                $('.response-msg').html(response.errors['0'])
                            }else{
                                $('.response-icon').attr('src',main_object.successIcon)
                                $('.response-heading').text('success')
                                $('.response-msg').html(jqXHR.responseJSON.data.actions.success_message)
                            }
                        }
                        closeFormNegative();
                        $('#contactPopupModal').modal('show');
                    }

                    if(parseInt(form_id)===4) {
                        var responseElem = $('#orderNowFormWrapper')
                        responseElem.find('h2.hide-on-submit').hide()
                        responseElem.addClass('d-xl-flex order-form-submitted')
                        responseElem.append(`<div class="contact-popup-icon flex-center">
                                  <img src="` + main_object.successIcon + `" class="response-icon" alt="poup-icon">
                              </div>
                              <div class="popup-content">
                                    <h2 class="heading-border response-heading text-left">`+main_object.successText+`</h2>
                                    <p class="response-msg">` + response.data.actions.success_message + `</p>
                                    <a href="#0" class="btn-global" data-dismiss="modal">`+main_object.closeText+`</a>
                              </div>`)
                    }
                }
            });
            new myCustomFieldController()
          }
    })

    //change term & condition field description



     $(document).ready(function (){

         var customUrl = null
         var locationPhone = null
         var isDocumentLoaded = false

         if(window.Marionette){
           var formRenderController = Marionette.Object.extend( {

                initialize: function() {
                    this.listenTo( nfRadio.channel( 'form' ), 'render:view', this.renderView );
                    this.listenTo( nfRadio.channel( 'nfMP' ), 'change:part', this.onChangePart );
                    $(document).on('change','.df-location-dropdown select',this.onLocationChange)
                },

                renderView: function( view ) {
                    customUrl =  main_object.dummy_term_condition_url.replace('on-change',main_object.currentLocationSlug)
                    isDocumentLoaded = true
                },
                 onLocationChange:function (){
                     var selectLocation = $(this).val()
                     getSingleLocationObject(selectLocation)
                     isDocumentLoaded = false
                 },
                onChangePart:function (){

                    var spanText = $('.nf-help').data('text')
                    $(spanText).insertBefore('.nf-field-description')

                    if(main_object.query_object.post_title){
                        $('#orderNowFormWrapper').find('input[type="radio"]').each(function (){
                            if(main_object.query_object.post_title.includes($(this).val())){
                                $(this).click()
                            }
                        })
                    }

                  if(isDocumentLoaded){
                      getSingleLocationObject(main_object.currentLocationSlug,true)
                      isDocumentLoaded = false
                  }

                    $('a._location_phone').text(locationPhone).attr('href','tel:'+locationPhone)
                   removeExistingPreviousButton()
                }

            });

            new formRenderController
         }

         if(main_object.is_location_page && main_object.has_term_content!==''){
             customUrl = main_object.location_specific_term_condition
         }

         $(document).on('mouseover','#nf-field-28-wrap .nf-field-description a._term_condition_link',function (){
             $(this).attr('href',customUrl)
         })

         function getSingleLocationObject(unique,slug=false) {
             var selectLocationItem = main_object.locationJson.find(elm=>((slug)?elm.slug:elm.locationSpecificTerm)==unique)
             if(selectLocationItem.slug !==undefined) {
                 locationPhone = selectLocationItem._location_phone
                 customUrl = main_object.dummy_term_condition_url.replace('on-change', selectLocationItem.slug)
             }

             console.log(locationPhone,customUrl)
         }
     })



}(jQuery));

//map init
if(jQuery(document).find('.location-section').length){
    function globalMapInit() {
        const mapElm = jQuery('#map')
        let locationPageCoords = mapElm.data('location-page-coords')
        const isLocationSpecific = mapElm.data('is-location-specific')
        const location = mapElm.data('coordinates')
        if(!locationPageCoords){
            locationPageCoords  = location
        }
        navigator.geolocation.getCurrentPosition(function (position) {
                const currentCoords = {lat: position.coords.latitude, lng: position.coords.longitude}
                loadMap(mapElm,location,locationPageCoords,currentCoords,isLocationSpecific)
            },
            function (error) {
                console.log(error.message)
                loadMap(mapElm,location,locationPageCoords,[],isLocationSpecific)
            })

    }
    function loadMap(mapElm,location,pageCoords,currentCoords=[],isLocationSpecific){
        const mapStyle = [
            {
                "elementType": "labels",
                "stylers": [
                    {
                        "color": "#555045"
                    },
                    {
                        "weight": 2.5
                    }
                ]
            },
            {
                "elementType": "labels.icon",
                "stylers": [
                    {
                        "visibility": "off"
                    }
                ]
            },
            {
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "elementType": "labels.text.fill",
                "stylers": [
                    {
                        "saturation": 20
                    },
                    {
                        "lightness": 65
                    }
                ]
            },
            {
                "elementType": "labels.text.stroke",
                "stylers": [
                    {
                        "visibility": "off"
                    }
                ]
            },
            {
                "featureType": "administrative",
                "stylers": [
                    {
                        "color": "#707e2d"
                    },
                    {
                        "saturation": "-72"
                    },
                    {
                        "lightness": "20"
                    },
                    {
                        "gamma": "3.58"
                    },
                    {
                        "weight": "0.75"
                    }
                ]
            },
            {
                "featureType": "administrative",
                "elementType": "geometry",
                "stylers": [
                    {
                        "hue": "#ff0000"
                    }
                ]
            },
            {
                "featureType": "administrative",
                "elementType": "geometry.fill",
                "stylers": [
                    {
                        "color": "blue"
                    },
                    {
                        "lightness": 20
                    }
                ]
            },
            {
                "featureType": "administrative",
                "elementType": "geometry.stroke",
                "stylers": [
                    {
                        "color": "blue"
                    },
                    {
                        "lightness": 17
                    },
                    {
                        "weight": 1.2
                    }
                ]
            },
            {
                "featureType": "administrative",
                "elementType": "labels",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "administrative",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "color": "#625d52"
                    },
                    {
                        "weight": "0.79"
                    }
                ]
            },
            {
                "featureType": "landscape",
                "elementType": "geometry",
                "stylers": [
                    {
                        "lightness": 20
                    }
                ]
            },
            {
                "featureType": "landscape",
                "elementType": "geometry.fill",
                "stylers": [
                    {
                        "color": "#e5d3aa"
                    }
                ]
            },
            {
                "featureType": "landscape",
                "elementType": "geometry.stroke",
                "stylers": [
                    {
                        "color": "#e5d3aa"
                    }
                ]
            },
            {
                "featureType": "landscape",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "landscape.man_made",
                "elementType": "labels",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "landscape.man_made",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "poi",
                "elementType": "geometry",
                "stylers": [
                    {
                        "color": "#f5f5f5"
                    },
                    {
                        "lightness": 21
                    }
                ]
            },
            {
                "featureType": "poi",
                "elementType": "labels",
                "stylers": [
                    {
                        "visibility": "off"
                    }
                ]
            },
            {
                "featureType": "poi",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "poi.attraction",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "poi.park",
                "elementType": "geometry",
                "stylers": [
                    {
                        "color": "#e5d3aa"
                    },
                    {
                        "lightness": 21
                    }
                ]
            },
            {
                "featureType": "poi.park",
                "elementType": "labels",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "poi.park",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "poi.place_of_worship",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "poi.sports_complex",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "poi.sports_complex",
                "elementType": "labels.text.fill",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "road.arterial",
                "elementType": "geometry",
                "stylers": [
                    {
                        "lightness": 18
                    }
                ]
            },
            {
                "featureType": "road.highway",
                "elementType": "geometry.fill",
                "stylers": [
                    {
                        "color": "#ffffff"
                    },
                    {
                        "lightness": 17
                    }
                ]
            },
            {
                "featureType": "road.highway",
                "elementType": "geometry.stroke",
                "stylers": [
                    {
                        "color": "#ffffff"
                    },
                    {
                        "lightness": 29
                    },
                    {
                        "weight": 0.2
                    }
                ]
            },
            {
                "featureType": "road.local",
                "elementType": "geometry",
                "stylers": [
                    {
                        "color": "#ffffff"
                    },
                    {
                        "lightness": 16
                    }
                ]
            },
            {
                "featureType": "road.local",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "color": "#555045"
                    },
                    {
                        "weight": 5.5
                    }
                ]
            },
            {
                "featureType": "transit",
                "elementType": "geometry",
                "stylers": [
                    {
                        "color": "#f2f2f2"
                    },
                    {
                        "lightness": 19
                    },
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "transit",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "transit.station",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "transit.station.airport",
                "elementType": "labels.text",
                "stylers": [
                    {
                        "visibility": "on"
                    }
                ]
            },
            {
                "featureType": "water",
                "elementType": "geometry",
                "stylers": [
                    {
                        "color": "#e9e9e9"
                    },
                    {
                        "lightness": 17
                    }
                ]
            }
        ]
        const map = new google.maps.Map(document.getElementById("map"), {
            zoom: parseInt(pageCoords[0].map_lat_lng.zoom),
            center: {lat: parseFloat(pageCoords[0].map_lat_lng.lat), lng: parseFloat(pageCoords[0].map_lat_lng.lng)},
            disableDefaultUI: true,
            styles:mapStyle
        });

        var icon = {
            path: "M168.3 499.2C116.1 435 0 279.4 0 192C0 85.96 85.96 0 192 0C298 0 384 85.96 384 192C384 279.4 267 435 215.7 499.2C203.4 514.5 180.6 514.5 168.3 499.2H168.3zM192 256C227.3 256 256 227.3 256 192C256 156.7 227.3 128 192 128C156.7 128 128 156.7 128 192C128 227.3 156.7 256 192 256z",
            fillColor: '#94986E',
            fillOpacity: 1,
            anchor: new google.maps.Point(
                200, // width
                600 // height
            ),
            strokeWeight: 0,
            scale: 0.1
        }
        var marker;

        let distance = []
        for (var i = 0; i < location.length; i++) {
            const coordinate = location[i]
            const Latlng = {lat: parseFloat(coordinate.map_lat_lng.lat), lng: parseFloat(coordinate.map_lat_lng.lng)};
            distance.push(getDistanceBetweenCoords(currentCoords, Latlng))
            marker = new google.maps.Marker({
                position: Latlng,
                map,
                icon: icon
            });
        }
        let center = distance.indexOf(Math.min(...distance))
        if(!isLocationSpecific){
           try{
               map.panTo(new google.maps.LatLng(parseFloat(location[center].map_lat_lng.lat), parseFloat(location[center].map_lat_lng.lng)));
           }catch (e){
               console.log('default map enabled')
           }
        }
    }

    function getDistanceBetweenCoords(mk1, mk2) {
        var R = 3958.8; // Radius of the Earth in miles
        var rlat1 = mk1.lat * (Math.PI/180); // Convert degrees to radians
        var rlat2 = mk2.lat * (Math.PI/180); // Convert degrees to radians
        var difflat = rlat2-rlat1; // Radian difference (latitudes)
        var difflon = (mk2.lng-mk1.lng) * (Math.PI/180); // Radian difference (longitudes)

        var d = 2 * R * Math.asin(Math.sqrt(Math.sin(difflat/2)*Math.sin(difflat/2)+Math.cos(rlat1)*Math.cos(rlat2)*Math.sin(difflon/2)*Math.sin(difflon/2)));
        return d;
    }
}
