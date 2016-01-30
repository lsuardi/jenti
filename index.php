<?php


// Copyright 2015 - NINETY-DEGREES

require_once "JentiConfig.php";
require_once "JentiSession.php";

$session = new JentiSession($config);
if($session->error)
{
    echo $session->error;
    exit;
}
$catalog = $session->catalog;

$session->save_user_start();

?>

<!DOCTYPE html>
<html>
    <head>
        <title> J E N T I </title>
        
        <meta charset="UTF-8"> 
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="./favicon.png" />
       
        <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.css">
        <link rel="stylesheet" href="css/themes/<?php echo $session->skin; ?>.css" />
        <link rel="stylesheet" href="css/themes/jquery.mobile.icons.min.css" />
        <link rel="stylesheet" href="jenti.css">
        <!--
        <script src="jquery.mobile.1.4.5/demos/js/jquery.min.js"></script>
        <script src="jquery.mobile.1.4.5/demos/js/jquery.mobile-1.4.5.min.js"></script>
        -->
        <script type="text/javascript" src="//wurfl.io/wurfl.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.js"></script>
        <script src="jquery-cookie-master/src/jquery.cookie.js"></script>
        
        <script src="javascript/JentiToolbar.js"></script>
        <script src="javascript/JentiHome.js"></script>
        <script src="javascript/JentiOptions.js"></script>
        <script src="javascript/JentiTools.js"></script>
    </head>
    <body>
        <div data-role="page">
            
            <div id="div-header" data-role="header" class="jenti-text-header" data-position="fixed" >
                <span class="jenti-text-header"><?php echo $catalog[0]; ?></span>

                <div id="custom-border-radius">
                    <a id="button-feedback" href="#popupFeedback" data-rel="popup" data-position-to="window" data-transition="pop" 
                       class="ui-btn ui-corner-all ui-btn-inline ui-btn-icon-notext ui-icon-comment footer-button-left">
                    </a>
                </div>
                
                <div data-role="popup" id="popupFeedback" data-overlay-theme="a" 
                     data-theme="a" style="max-width:280px;">
                    <div data-role="header" data-theme="a">
                        <h2><?php echo $catalog[19]; ?></h2>
                    </div>
                    <div role="main" class="ui-content jenti-text-center">
                        <textarea id="textarea-feedback" name="textarea-1"></textarea>
                        <a id="button-feedback-submit" href="#" 
                           class="ui-btn ui-corner-all ui-shadow ui-btn-inline ui-btn-b" data-rel="back">
                            <?php echo $catalog[21]; ?>
                        </a>
                    </div>
                </div>

                <div id="custom-border-radius">
                    <a id="button-options" href="#" 
                       class="ui-btn ui-corner-all ui-btn-inline ui-btn-icon-notext ui-icon-gear footer-button-right">
                    </a>
                </div>
            </div>
            
            <div id="div-content" data-role="content" class="jenti-text-center">
                <!-- this is where content is rendered -->
            </div>

            <div data-role="popup" id="button-hint-popup" class="ui-content jenti-popup">
                <a href="#" data-rel="back" 
                   class="ui-btn ui-corner-all ui-shadow ui-btn-a ui-icon-delete ui-btn-icon-notext ui-btn-right">
                       <?php echo $catalog[11]; ?></a>
                <p id="p-hint-popup-content"></p>
            </div>

            <div data-role="popup" id="button-guess-popup" class="ui-content jenti-popup jenti-text-center">
                <p id="p-guess-popup-content"></p>
                <div id="div-guess-popup-feedback" class="jenti-text-center"></div>
            </div>
            
            <div data-role="popup" id="button-profile-info-popup" class="ui-content jenti-popup">
              <a href="#" data-rel="back" 
                class="ui-btn ui-corner-all ui-shadow ui-btn-a ui-icon-delete ui-btn-icon-notext ui-btn-right"></a>
                <p><?php echo $catalog[30]; ?></p>
            </div>

        </div><!-- /page -->

    </body>
</html>

