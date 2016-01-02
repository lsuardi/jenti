
// Copyright 2015 - NINETY-DEGREES

function tools_get_url_vars()
{
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}



function tools_render_error_ajax(xhr, status, errorThrown)
{
    var msg = "Error: " + errorThrown
            + "Status: " + status
            ;
    alert(msg);
}



function tools_render_error(msg)
{
    alert(msg);
}



function tools_render_error_json(json)
{
    alert(json.ERROR);
}



function tools_validate_string(control_id, label)
{
    var value = $(control_id).val().trim();
    if (value.length === 0)
    {
        $(control_id).addClass("jenti-red-border");
        return null;
    }
    else
    {
        $(control_id).removeClass("jenti-red-border");        
    }
    return value;
}



function tools_validate_date(control_id, label)
{  
    // regular expression to match required date format
    re = /^\d{1,2}\/\d{1,2}\/\d{4}$/;

    var value = $(control_id).val().trim();
    if (value.length === 0 || !value.match(re))
    {
        $(control_id).addClass("jenti-red-border");
        return null;
    }
    else
    {
        $(control_id).removeClass("jenti-red-border");        
    }

    return value;
}
