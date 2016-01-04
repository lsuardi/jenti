
// Copyright 2015 - NINETY-DEGREES



function render_options()
{
    var catalog = jQuery.data(document.body, "catalog");
    
    var html = '<button id="button-profile" class="ui-btn ui-corner-all">' + catalog[31] + '</button>'
        + '<button id="button-login" class="ui-btn ui-corner-all">' + catalog[2] + '</button>'
        + '<button id="button-language" class="ui-btn ui-corner-all">' + catalog[3] + '</button>'
        + '<button id="button-skin" class="ui-btn ui-corner-all">' + catalog[25] + '</button>'
        + '<button id="button-play" class="ui-btn ui-corner-all">' + catalog[1] + '</button>'
        ;
    
    $("#div-content").html(html);

    $("#button-login").click(function(event) 
    {
        render_login();
    });

    $("#button-profile").click(function(event) 
    {
        render_profile();
    });

    $("#button-play").click(function(event) 
    {
        render_home();
    });

    $("#button-language").click(function(event) 
    {
        render_options_language();
    });
    
    $("#button-skin").click(function(event) 
    {
        // save score and other info
        $.ajax({
            url: "ajax/change_skin.php",
            type: "POST",
            dataType : "json",
            success: function(json) 
            {
                if (json.hasOwnProperty("ERROR"))
                {
                    tools_render_error(JSON.stringify(json,null,2)); 
                    return;
                }

                // reload index and render options page
                $.cookie("jenti_render", "options");
                window.location.href = "index.php";
            },
            error: function(xhr, status, errorThrown) 
            {
                tools_render_error_ajax(xhr, status, errorThrown); 
            }
        });
    });

}



function render_options_language()
{
    var catalog = jQuery.data(document.body, "catalog");
    var language_code = $.cookie("jenti_language_code");
    var labels = catalog[26].split(',');
    var values = catalog[27].split(',');

    var html = '<fieldset id="fieldset-language" data-role="controlgroup">';
    for (var i=0; i<labels.length; i++)
    {
        var radioOn = (values[i] === language_code ? ' ui-radio-on ' : ' ui-radio-off ');
        
        html    +='<div class="ui-radio">';
        html    +='<label class="ui-btn ui-btn-inherit ui-btn-icon-left ui-corner-all ui-first-child ' + radioOn + '" '
                + ' value="' + values[i] + '">' 
                + labels[i] + '</label>'
                ;
        html    +='</div>';
    }
    html += '</fieldset>';

    $("#div-content").html(html);
    
    $("#fieldset-language").click(function(event) 
    {
        // the clicked label contains value attribute with the language_code
        var language_code = event.target.attributes["value"].value;
        $.cookie("jenti_language_code", language_code);
        location.reload();
    });

}



function render_login()
{
    var catalog = jQuery.data(document.body, "catalog");

    var html    = '<BR>'
                + '<label for="input-email">' + catalog[28] + '</label>'
                + '<input data-clear-btn="false" name="input-email" id="input-email" '
                + '  value="" class="ui-body-a ui-corner-all jenti-login-input">'
        
                + '<BR><BR>'
        
                + '<label for="input-pwd">' + catalog[29] + '</label>'
                + '<input data-clear-btn="false" name="input-pwd" id="input-pwd" '
                + '  value="" type="password" class="ui-body-a ui-corner-all jenti-login-input">'
        
                + '<BR><BR><BR>'

                + '<div id="div-login-button" data-role="content" class="jenti-text-center">'
                + '  <button id="button-dologin" class="ui-btn ui-btn-inline ui-corner-all">' 
                +      catalog[2] + '</button>'
                + '</div>'
                ;

    $("#div-content").html(html);

    $("#button-dologin").click(function(event) 
    {
        var catalog = jQuery.data(document.body, "catalog");

        var email = tools_validate_string("#input-email", catalog[28]);
        var pwd = tools_validate_string("#input-pwd", catalog[29]);
        if (email && pwd)
        {
            ajax_login(email, pwd, function(json) 
            {
                // noop
            });
        }
    });
}



function render_profile()
{
    var catalog = jQuery.data(document.body, "catalog");

    var html    = '<BR>'
                + '<label>' + catalog[28] + catalog[34] + '</label>'
                + '<input data-clear-btn="false" name="input-email" id="input-email" '
                + '  value="" class="ui-body-a ui-corner-all jenti-login-input">'
        
                + '<BR><BR>'
        
                + '<label>' + catalog[29] + catalog[34] + '</label>'
                + '<input data-clear-btn="false" name="input-pwd" id="input-pwd" '
                + '  value="" type="password" class="ui-body-a ui-corner-all jenti-login-input">'
        
                + '<BR><BR>'
        
                + '<label>' + catalog[33] + catalog[34] + '</label>'
                + '<input data-clear-btn="false" name="input-birth-date" id="input-birth-date" '
                + '  placeholder="' + catalog[35] + '" class="ui-body-a ui-corner-all jenti-login-input">'
        
                + '<BR><BR>'
        
                + '<label>' + catalog[32] + '</label>'
                + '<input data-clear-btn="false" name="input-name" id="input-name" '
                + '  value="" class="ui-body-a ui-corner-all jenti-login-input">'
        
                + '<BR><BR><BR>'
        
                + '<div id="div-login-button" data-role="content" class="jenti-text-center">'
                + '  <button id="button-profile" class="ui-btn ui-btn-inline ui-corner-all">' 
                +      catalog[31] + '</button>'
                + '  <div id="custom-border-radius" style="display:inline;">'
                + '    <a href="#button-profile-info-popup" id="button-profile-popup" data-rel="popup" '
                + '      class="ui-icon-info ui-btn ui-btn-inline ui-corner-all ui-btn-icon-notext jenti-button"></a>'
                + '  </div>'
                + '</div>'
                ;

    $("#div-content").html(html);

    $("#button-profile").click(function(event) 
    {
        // TODO validate email
        var email = tools_validate_string("#input-email", catalog[28]);
        var pwd = tools_validate_string("#input-pwd", catalog[29]);
        var birthdate = tools_validate_string("#input-birth-date", catalog[33]);
        var name = $("#input-name").val().trim();
                
        // save profile
        if (email && pwd && birthdate)
        {
            $.ajax({
                url: "ajax/post_profile.php",
                data: {
                    EMAIL: email,
                    CITY: pwd,
                    NAME: name,
                    BIRTHDATE: birthdate
                },
                type: "POST",
                dataType : "json",
                success: function(json) 
                {
                    if (json.hasOwnProperty("ERROR"))
                    {
                        tools_render_error(JSON.stringify(json,null,2)); 
                        return;
                    }

                    // automatically login after registration
                    ajax_login(email, pwd, function(json) 
                    {
                        // user registered message
                        alert(catalog[38]);
                    });       
                },
                error: function(xhr, status, errorThrown) 
                {
                    tools_render_error_ajax(xhr, status, errorThrown); 
                }
            });            
        }
    });
}

function ajax_login(email, pwd, success_callback)
{
    $.ajax({
        url: "ajax/post_login.php",
        data: {
            BERGAMO: email,
            ENDINE: pwd
        },
        type: "POST",
        dataType : "json",
        success: function(json)
        {
            if (json.hasOwnProperty("ERROR"))
            {
                tools_render_error_json(json); 
                return;
            }
            
            success_callback(json);
            
            $.cookie("jenti_render", "home");
            window.location.href = "index.php";
        },
        error: function(xhr, status, errorThrown) 
        {
            tools_render_error_ajax(xhr, status, errorThrown); 
        }
    });       
}