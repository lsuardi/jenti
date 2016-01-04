<?php

?>

<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.js"></script>
        <script src="jquery-cookie-master/src/jquery.cookie.js"></script>
        
        <script>
function ajax_get_catalog(success_callback)
{
        $.ajax({
            url: "../ajax/get_catalog.php",
            type: "GET",
            dataType : "json",
            success: success_callback,
            error: function(xhr, status, errorThrown) 
            {
                alert('error - ' + xhr + ' - ' + status + ' - ' + errorThrown);
            }
        });
}

function show_json(json)
{
    alert(json);
}

            ajax_get_catalog(show_json);
            
        </script>
    </head>
    <body>
    </body>
</html>

