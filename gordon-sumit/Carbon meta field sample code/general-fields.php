<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make('theme_options', __('Global Sections',VAVIA_DOMAIN))
    ->add_tab(__('Benefits',VAVIA_DOMAIN), array(
        Field::make('checkbox', 'make_it_easy_sec_is_hidden', __('Hide',VAVIA_DOMAIN)),
        Field::make('text', 'make_it_easy_sec_title', __('Title',VAVIA_DOMAIN)),
        Field::make('complex', 'make_it_easy_cards', __('Benefit',VAVIA_DOMAIN))
            ->set_layout('tabbed-horizontal')
            ->add_fields(array(
                Field::make('text', 'title', __('Title',VAVIA_DOMAIN)),
                Field::make('image', 'image', __('Icon',VAVIA_DOMAIN)),
                Field::make('rich_text', 'description', __('Description',VAVIA_DOMAIN)),
            )),
    ))
    ->add_tab(__('How It Works',VAVIA_DOMAIN), array(
        Field::make('checkbox', 'how_it_work_sec_is_hidden', __('Hide',VAVIA_DOMAIN)),
        Field::make('complex', 'how_it_work', __('How It Works',VAVIA_DOMAIN))
            ->set_layout('tabbed-horizontal')
            ->add_fields(array(
                Field::make('text', 'how_it_work_tab_title', __('Tab Title',VAVIA_DOMAIN)),
                Field::make('rich_text', 'how_it_work_text', __('Description',VAVIA_DOMAIN)),
                Field::make('image', 'how_it_work_background_image', __('Background Image',VAVIA_DOMAIN)),
            )),
    ))
    ->add_tab(__('Testimonials',VAVIA_DOMAIN), array(
        Field::make('checkbox', 'testimonial_sec_is_hidden', __('Hide',VAVIA_DOMAIN)),
        Field::make('text', 'testimonial_sec_title', __('Title',VAVIA_DOMAIN)),
    ))
    ->add_tab(__('Resources',VAVIA_DOMAIN), array(
        Field::make('checkbox', 'resource_sec_is_hidden', __('Hide',VAVIA_DOMAIN)),
        Field::make('urlpicker', 'resource_sec_btn', __('Button',VAVIA_DOMAIN)),
    ))
    ->add_tab(__('Franchise',VAVIA_DOMAIN), array(
        Field::make('checkbox', 'franchise_sec_is_hidden', __('Hide',VAVIA_DOMAIN)),
        Field::make('text', 'franchise_sec_title', __('Title',VAVIA_DOMAIN)),
        Field::make('rich_text', 'franchise_sec_desc', __('Description',VAVIA_DOMAIN)),
        Field::make('urlpicker', 'franchise_sec_btn', __('Button',VAVIA_DOMAIN)),
        Field::make('image','franchise_sec_background_img_left',__('Background Image Left',VAVIA_DOMAIN))->set_width(50),
        Field::make('image', 'franchise_sec_background_img_right', __('Background Image Right',VAVIA_DOMAIN))->set_width(50),
    ))
    ->add_tab(__('Map',VAVIA_DOMAIN), array(
        Field::make('checkbox', 'map_sec_is_hidden', __('Hide',VAVIA_DOMAIN)),
        Field::make('text', 'map_sec_title', __('Title',VAVIA_DOMAIN)),
        Field::make('rich_text', 'map_sec_desc', __('Description',VAVIA_DOMAIN)),
    ))
    ->add_tab(__('Social',VAVIA_DOMAIN), array(
        Field::make('checkbox', 'social_sec_is_hidden', __('Hide',VAVIA_DOMAIN)),
        Field::make('text', 'social_sec_title', __('Title',VAVIA_DOMAIN))->set_width(25),
        Field::make('urlpicker', 'social_sec_btn', __('Button',VAVIA_DOMAIN))->set_width(25),
        Field::make('media_gallery', 'social_image', __('Social Images',VAVIA_DOMAIN)),
    ))
    ->add_tab(__('Contact',VAVIA_DOMAIN), array(
        Field::make('checkbox', 'contact_sec_is_hidden', __('Hide',VAVIA_DOMAIN)),
        Field::make('text', 'contact_sec_title', __('Title',VAVIA_DOMAIN))->set_width(50),
        Field::make('text', 'contact_sec_form', __('Form ID',VAVIA_DOMAIN))->set_width(50),
    ))
    ->add_tab(__('FAQ',VAVIA_DOMAIN), array(
        Field::make('checkbox', 'faq_sec_is_hidden', __('Hide',VAVIA_DOMAIN)),
        Field::make('text', 'faq_sec_title', __('Title',VAVIA_DOMAIN))->set_width(25),
        Field::make('complex', 'faq_sec_question_answer',__('FAQ',VAVIA_DOMAIN))
            ->set_layout('tabbed-horizontal')
            ->add_fields(array(
                Field::make('text', 'question', __('Question',VAVIA_DOMAIN)),
                Field::make('rich_text', 'answer', __('Answer',VAVIA_DOMAIN)),
            )),
    ));