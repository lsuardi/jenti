
$(document).ready(function() 
{
    $("#button-feedback-submit").click(function(event) 
    {
        var catalog = jQuery.data(document.body, "catalog");
        var word = jQuery.data(document.body, "word_json");
        var feedback = $("#textarea-feedback").val();
        if (word)
        {
            $.ajax({
                url: "ajax/post_feedback.php",
                data: {
                    WORD_ID: word.WORD_ID,
                    DEFINITION_ID: word.ID,
                    FEEDBACK: feedback
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
                },
                error: function(xhr, status, errorThrown) 
                {
                    tools_render_error_ajax(xhr, status, errorThrown);
                    return;
                }
            });
        }
        $("#textarea-feedback").val("");
    });



    $("#button-options").click(function(event) 
    {
        render_options();
    });


});   
