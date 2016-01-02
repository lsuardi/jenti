<?php

// TODO
// - split ENUM into names and values

require_once '../JentiConfig.php';
require_once '../dbedit/DBEdit.2.8.php';



function jenti_admin_page_header()
{
    $style = "font-size:14px; font-weight:bold; padding:10px;";

    echo '<html>';
    echo '<head>';
    echo '    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '    <link rel="stylesheet" type="text/css" href="../dbedit/DBEdit.css">';
    echo '</head>';
    echo '<body>';
    echo '<h1>JENTI ADMINISTRATION</h1>';
    echo '<a href="JentiAdminUser.php"><button type="submit" style="'.$style.'">USER</button></a> &nbsp;';
    echo '<a href="JentiAdminWordList.php"><button type="submit" style="'.$style.'">WORD LIST</button></a> &nbsp;';
    echo '<a href="JentiAdminWord.php"><button type="submit" style="'.$style.'">WORD</button></a> &nbsp;';
    echo '<a href="JentiAdminEnum.php"><button type="submit" style="'.$style.'">ENUM</button></a> &nbsp;';
}



function jenti_admin_page_footer()
{
    echo '</body>';
    echo '</html>';
}



date_default_timezone_set('Europe/Rome');

?>



