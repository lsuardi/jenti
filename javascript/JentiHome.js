
// Copyright 2015 - NINETY-DEGREES

$(document).ready(function() 
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
                
                // render page
                var render = $.cookie("jenti_render");
                switch (render)
                {
                    case "options": render_options(); break;
                    default: render_home(); break
                }
                $.removeCookie("jenti_render");
            },
            error: function(xhr, status, errorThrown) 
            {
                tools_render_error_ajax(xhr, status, errorThrown);
            }
        });
    }
}); 



function render_home()
{
    var catalog = jQuery.data(document.body, "catalog");
    var showCount;
    var score;

    $("#div-content").html(html_home());

    $("#button-play").click(function(event) 
    {
        $.ajax({
            url: "ajax/get_next_word.php",
            type: "GET",
            dataType : "json",
            success: function(json) 
            {
                if (json.hasOwnProperty("ERROR"))
                {
                    tools_render_error(json.ERROR); 
                    return;
                }

                // display word data
                var word = json.WORD;
                var definition = json.DEFINITION;
                var wordTip = word.length + " " + catalog[10];
                $("#p-definition").text(definition);
                $("#input-word").val(wordTip);
                $("#p-hint-popup-content").html(html_hint_popup(json));
                $("#button-hint").removeClass("ui-disabled");
                $("#button-guess").removeClass("ui-disabled");
                $("#button-show").removeClass("ui-disabled");
                $("#p-score").html(html_score());
                
                // save word data
                jQuery.data(document.body, "word_json", json);

                // initialize like
                jQuery.data(document.body, "like", false);
                
                // initialize play
                showCount = 0;
                score = word.length;
            },
            error: function(xhr, status, errorThrown) 
            {
                tools_render_error_ajax(xhr, status, errorThrown);
            }
        });
    });

    $("#input-word").click(function(event)
    {
        var word_json = jQuery.data(document.body, "word_json");
        var word = word_json.WORD;
        var inputWord = "";
        if (showCount > 0)
        {
            inputWord = word.substring(0, showCount);
        }
        $("#input-word").val(inputWord);
    });

    $("#button-guess").click(function(event) 
    {
        var word_json = jQuery.data(document.body, "word_json");
        var word = word_json.WORD;
        var guess = $("#input-word").val();
        $("#div-guess-popup-feedback").empty();
        $("#p-guess-popup-content").html(html_guess_popup(word_json, guess));
        if (guess.toUpperCase() === word.toUpperCase())
        {
            $("#div-guess-popup-feedback").html(html_guess_popup_feedback());
            
            // trigger makes jquery reanalyze the html code 
            // inserted dynamically by html_guess_popup_feedback()
            $("#div-guess-popup-feedback").trigger("create");
            showCount = word.length;
            
            // save score and other info
            $.ajax({
                url: "ajax/post_guess.php",
                data: {
                    WORD_ID: word_json.WORD_ID,
                    DEFINITION_ID: word_json.ID,
                    SCORE: score
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
                    $("#p-score").html(html_score());
                },
                error: function(xhr, status, errorThrown) 
                {
                    tools_render_error_ajax(xhr, status, errorThrown);
                }
            });
        }
        
        $("#button-like").on('click',function(event)
        {
            var catalog = jQuery.data(document.body, "catalog");
            var text = $("#button-like").text();
            
            if(text === catalog[39])
            {
                $("#button-like").text(catalog[40]);
                jQuery.data(document.body, "like", true);
            }
            if(text === catalog[40])
            {
                $("#button-like").text(catalog[39]);
                jQuery.data(document.body, "like", false);
            }
        });

        $("#guess-popup-textarea").click(function(event)
		{
		    $("#guess-popup-textarea").val("");
		});

    });

    $("#button-show").click(function(event) 
    {
        var word_json = jQuery.data(document.body, "word_json");
        var word = word_json.WORD;
        
        showCount++;

        var inputWord = word.substring(0, showCount);
        for (i=showCount; i<word.length; i++)
        {
            inputWord += " -";
        }
        
        score--;
        
        $("#input-word").val(inputWord);

        if (showCount === word.length)
        {
            $("#button-show").addClass("ui-disabled");            
        }
    });
    
    $(document).on("tap", "#guess-popup-feedback-submit,#close", function(event)
    {
        if($("#guess-popup-textarea").length)
        {
            var feedback = $("#guess-popup-textarea").val();
            jQuery.data(document.body, "guess-popup-feedback", feedback);
        }
    });

    $("#button-guess-popup").bind(
    {
    	// play again after guessing a word
        popupafterclose: function(event, ui)
        {
        	var word_json = jQuery.data(document.body, "word_json");
            var catalog = jQuery.data(document.body, "catalog");
        	var word = word_json.WORD;
            var like = jQuery.data(document.body, "like");
            var feedback = jQuery.data(document.body, "guess-popup-feedback");
            var feedback_data = {};
            
            feedback_data["WORD_ID"] = word_json.WORD_ID;
            feedback_data["DEFINITION_ID"] = word_json.ID;
            feedback_data["LIKE"] = like;
            
            if(feedback.length > 0 && feedback !== catalog[42])
            {
                feedback_data["FEEDBACK"] = feedback;
            }
            if(like || feedback.length > 0)
            {
                $.ajax({
                    url: "ajax/post_feedback.php",
                    data: feedback_data,
                    type: "POST",
                    dataType : "json",
                    success: function(json) 
                    {
                        if (json.hasOwnProperty("ERROR"))
                        {
                            tools_render_error(JSON.stringify(json,null,2)); 
                        }
                    },
                    error: function(xhr, status, errorThrown) 
                    {
                        tools_render_error_ajax(xhr, status, errorThrown);
                    }
                });

                jQuery.data(document.body, "guess-popup-feedback", "");
            }

            // play next word
        	var guess = $("#input-word").val();
        	if (guess.toUpperCase() === word.toUpperCase())
        	{
        		$("#button-play").click();
        	}
        }
    });
}



function html_home()
{
    var catalog = jQuery.data(document.body, "catalog");
    
    var html 
        = '<div id="div-definition" class="ui-body ui-body-a ui-corner-all jenti-text-center">'
        + '<h3 id="h3-definition">' + catalog[7] + '</h3>'
        + '<p id="p-definition">' + catalog[6] + '</p>'
        + '</div>'

        + '<div id="div-word" data-role="content" class="jenti-text-center">'
        + '  <div id="custom-border-radius" style="display:inline;">'
        + '    <a href="#button-hint-popup" id="button-hint" data-rel="popup" data-position-to="window" '
        + '      class="ui-icon-info ui-btn ui-btn-inline ui-corner-all ui-btn-icon-notext ui-disabled jenti-button"></a>'
        + '  </div>'
        + '  <input name="input-word" id="input-word" value="" type="text" '
        + '    class="ui-body-a ui-corner-all jenti-word-input">'
        + '  <div id="custom-border-radius" style="display:inline;">'
        + '    <a href="#" id="button-show" '
        + '      class="ui-icon-search ui-btn ui-btn-inline ui-corner-all ui-btn-icon-notext ui-disabled jenti-button"></a>'
        + '  </div>'
        + '</div>'

        + '<br>'

        + '<div id="div-buttons" data-role="content" class="jenti-text-center">'
        + '  <a href="#button-guess-popup" id="button-guess" data-rel="popup" data-position-to="window" class="ui-btn ui-btn-inline ui-corner-all ui-disabled">' + catalog[9] + '</a>'
        + '  <button id="button-play" class="ui-btn ui-btn-inline ui-corner-all">' + catalog[1] + '</button>'
        + '</div>'

        + '<br>'

        + '<div id="div-score" data-role="content" class="jenti-text-center">'
        + '  <p id="p-score" class="jenti-text-center">' + html_score() + '</p>'
        + '</div>'

        + '<p id="p-debug" class="jenti-text-center"></p>'
        ;
                
        return html;
}
        
        
        
function html_hint_popup(word_json)
{
    var catalog = jQuery.data(document.body, "catalog");

    // source and word type
    var html = catalog[22] 
             + ' <a href="' + word_json.SOURCE_URL + '" target="_blank">' 
             + word_json.SOURCE_NAME + "</a><br>"
             + catalog[12] + " <b>" + word_json.TYPE + "</b>"
             ;
    
    // show tags if any
    var tags = word_json.TAGS.trim();
    if (tags.length > 0)
    {
        html += "<BR>" + catalog[24] + " <b>" + tags + "</b>";
    }

    // process more definitions if any
    var moreDefinitions = word_json.MORE_DEFINITIONS;
    if (moreDefinitions.length > 0)
    {
        html += "<BR><BR>" + catalog[13] + "<BR>";
        html += "<OL>";
        for (i = 0; i < moreDefinitions.length; i++) 
        { 
            html += "<LI>" + moreDefinitions[i].TAGS 
                  + " " + moreDefinitions[i].DEFINITION 
                  + "</LI>";
        }
        html += "</OL>";
        
        $("#button-hint").removeClass("ui-icon-info");
        $("#button-hint").addClass("ui-icon-bullets");
    }
    else
    {
        $("#button-hint").removeClass("ui-icon-bullets");
        $("#button-hint").addClass("ui-icon-info");    
    }
    
    return html;
}        
        
        
        
function html_guess_popup(word_json, guess)
{
    var catalog = jQuery.data(document.body, "catalog");
    var word = word_json.WORD;
    var html;
    
    if (guess.toUpperCase() === word.toUpperCase())
    {
        html = catalog[15] + " <b>" + word + "</b>";
        html += "<BR><BR>";
        //html += catalog[16];
    }
    else
    {
        html = catalog[14];
    }
    
    return html;
}     



function html_guess_popup_feedback()
{
    var catalog = jQuery.data(document.body, "catalog");
    
    var html
        = '<textarea id="guess-popup-textarea" name="textarea-2">' + catalog[42] + '</textarea>'
        + '<a id="button-like" href="#" '
        + 'class="ui-btn ui-corner-all ui-shadow ui-btn-inline ui-btn-b">'
        + catalog[39] + '</a>'
        + '<a id="guess-popup-feedback-submit" href="#" '
        + 'class="ui-btn ui-corner-all ui-shadow ui-btn-inline ui-btn-b" data-rel="back">'
        + catalog[1] +'</a>';
    
    return html;
}
        
  
  
function html_score()
{
    var catalog = jQuery.data(document.body, "catalog");
    
    tmp = $.cookie('jenti_email');
    tmp = $.cookie('jenti_score');
    
    var score = 0;
    if ($.cookie('jenti_score') !== undefined)
    {
        score = $.cookie('jenti_score');
    }
    
    var msg = catalog[23];
    if ($.cookie('jenti_email') !== undefined)
    {
        var name = $.cookie('jenti_email');
        if ($.cookie('jenti_name') !== undefined)
        {
            name = $.cookie('jenti_name');
        }
        msg = catalog[36] + " " + name;
    }
    
    msg = msg + " " + score;
    
    if ($.cookie('jenti_email') === undefined)
    {
        // user not logged in, add login hint
        msg = msg + '<br><br>' + catalog[37];
    }
    
    return msg;
}
