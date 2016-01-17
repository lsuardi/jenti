
// Copyright 2015 - NINETY-DEGREES

$(document).ready(function() 
{
    if(WURFL.is_mobile)
    {
        var catalog = jQuery.data(document.body, "catalog");
        if (!catalog)
        {
            $.ajax({
                url: "ajax/get_catalog.php",
                type: "GET",
                dataType : "json",
                success: function(json) 
                {
                    // save catalog
                    jQuery.data(document.body, "catalog", json);

                    $("#sp-logo").html(html_logo());
                    $("#sp-logo").delay(0).animate({ opacity: 1 }, 3000);

                    $("#sp-button-play").click(function(event)
                    {
                        window.location.href = "index.php";
                    });
                },
                error: function(xhr, status, errorThrown) 
                {
                    tools_render_error_ajax(xhr, status, errorThrown);
                }
            });
        }
    }
    else
    {
        window.location.href = "about.html";
    }
}); 

function html_logo()
{
    var catalog = jQuery.data(document.body, "catalog");

    var html
        = '<span id="sp-logo-span" class="jenti-sp-logo-text">' + catalog[0] + '</span>'
        + '<br><br><br><br><br><br><br>'
        + '<button id="sp-button-play" class="ui-btn ui-btn-inline ui-corner-all jenti-sp-button ">' + catalog[1] + '</button>'
        ;
    
    return html;
}
