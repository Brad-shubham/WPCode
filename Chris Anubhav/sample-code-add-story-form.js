function adding_forms(e, button, previewStatus=false) {
    e.preventDefault();
    if (jQuery("#termsCheckbox").is(":checked")) {
      var terms = "true";
    } else {
      var terms = "false";
    }
    var status = button.id;
    var title = jQuery("#title").val();
    var author = jQuery("#author_id").val();
    var content = tinymce.get("myTextarea").getContent({ format: "text" });
    var theme = jQuery(".themeCheck:checkbox:checked")
      .map(function () {
        return jQuery(this).val();
      })
      .get();
    var prompt_id = jQuery("#prompt_hidden").val();
    var story_id = jQuery("#story_hidden").val();
    var category_slug = jQuery("#prompt_cat_hidden").val();
  
    if (status == "draft") {
      var condition = validate_fields_to_save(title);
    }
  
    if (status == "publish") {
      var condition = validate_fields_to_publish(title, theme, content);
    }
    if (condition == "true") {
      var trimmed_content = content.trim();
      if (terms == "true" && status == "draft" && theme != "" && trimmed_content != "") {
        jQuery("#preview").attr("disabled", false);
        jQuery("#publish").attr("disabled", false);
      }
    }
  
    if(condition == "true") {
      if (status == "publish") {
        document.getElementById("buttonText").innerHTML = "Unpublish";
        jQuery("#publish").attr("id", "pending");
      }
    }
  
    if(status == "pending") {
      var condition = "true";
    }
  
    if (condition == "true") {
      jQuery.ajax({
        url: form_obj.ajax_url,
        type: "post",
        data: {
          action: "add_form",
          title: title,
          author: author,
          content: trimmed_content,
          theme: theme,
          terms: terms,
          status: status,
          prompt_id: prompt_id,
          story_id: story_id,
          category_slug: category_slug,
        },
        success: function (data) {
          if (!story_id) {
            $("#story_hidden").val(data);
          }
          jQuery("#titleError").html("");
          jQuery("#themeError").html("");
          jQuery("#contentError").html("");
          if (status == "draft") {
            document.getElementById("buttonText").innerHTML = "Publish";
            jQuery("#pending").attr("id", "publish");
            if (terms == "true") {
              jQuery("#saved_result").html(form_obj.save_with_terms);
            } else if (terms == "false") {
              jQuery("#saved_result").html(form_obj.save_without_terms);
            }
          } else if (status == "publish") {
            jQuery("#saved_result").html(form_obj.publish);
          } else if (status == "pending") {
            document.getElementById("buttonText").innerHTML = "Publish";
            jQuery("#pending").attr("id", "publish");
            jQuery("#saved_result").html(form_obj.unpublish);
          }
        },
      });
    } else {
      jQuery("#saved_result").html("");
      if (status == "draft") {
        jQuery("#titleError").html(
          " “Title is required. Please provide a Title before saving your story.” "
        );
      }
      if (status == "publish") {
        if (title == "") {
          jQuery("#titleError").html(
            " “Title is required. Please provide a Title before saving your story.” "
          );
        }
        if (theme == "") {
          jQuery("#themeError").html(
            " “Please select at least one theme before publishing your story so that our users can more easily find your story.” "
          );
        }
        if (content == "") {
          jQuery("#contentError").html(
            " “Story Content is required. Please write something to share your thoughts!” "
          );
        }
      }
    }
    if(previewStatus){
      var url = jQuery("#preview_hidden_link").val();
      url += "?submit_to_print=1&prompt_id_to_print="+prompt_id+"&story_id_to_print="+story_id;
      window.location = url;
    }
  }
  
  function confirm_box(e) {
    var val = confirm("Are you sure?");
    if (val == true) {
      e.preventDefault();
      window.location.reload();
    }
  }
  
  function validate_fields_to_save(title) {
    var val_title = title;
    if (val_title == "") {
      return "false";
    } else {
      return "true";
    }
  }
  
  function validate_fields_to_publish(title, theme, content) {
    var val_title = title;
    var val_theme = theme;
    var val_content = content;
    if (val_title == "" || val_theme == "" || val_content == "") {
      return "false";
    } else {
      return "true";
    }
  }
  