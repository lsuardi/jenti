<?php

/* TODO LIST

 * rinomina task manager get_task to get_insert, etc...
 * filtri con nome
 * filtri numerici, viste...
 * crea classe FilterControl
 * sorting
 * prova a inserire un voto non numerico
 * task manager non gestisce filtri non numerici
 * valutazione si puo' usare anche per programmare verifiche,
   interrogazioni, etc... aggiungere descrizione, stato...
   valutazione di classe o del singolo?
 * configurazione di task_link[param] deve essere come per task[filter]
 * togliere config table_pk e table_pk_auto
 * $config mescola colonne da query e colonne da tabella
 * usa costanti per elementi config
 * non e' possibile usare piu' di un dbedit per pagina
 * refactor
 */

// HISTORY
// 20150218 - use DBEManagerSql version 1.1
// 20150218 - updated sql_add_filter... to use column names not labels for request variables
// 20150409 - version identifier, row total


define( "DBE_VERSION"               , "2.8");

define( "DBE_ACTION_SELECT"         , 0);
define( "DBE_ACTION_INSERT"         , 1);
define( "DBE_ACTION_VIEW"           , 2);
define( "DBE_ACTION_UPDATE"         , 3);
define( "DBE_ACTION_DELETE"         , 4);
define( "DBE_ACTION_SUBMIT_INSERT"  , 5);
define( "DBE_ACTION_SUBMIT_UPDATE"  , 6);
define( "DBE_ACTION_SUBMIT_DELETE"  , 7);
define( "DBE_ACTION_SUBMIT_CANCEL"  , 8);

define( "DBE_STYLE_TABLE"           , "DBE_TABLE");
define( "DBE_STYLE_TOOLBAR"         , "DBE_TOOLBAR");
define( "DBE_STYLE_TD"              , "DBE_TD");
define( "DBE_STYLE_TD_HEADER"       , "DBE_TD_HEADER");
define( "DBE_STYLE_ERROR"           , "DBE_ERROR_MSG");
define( "DBE_STYLE_USRMSG"          , "DBE_USR_MSG");
define( "DBE_STYLE_BUTTON"          , "DBE_BUTTON");

define( "DBE_CONFIG_HOSTNAME"              , "hostname");
define( "DBE_CONFIG_USER_NAME"             , "user_name");
define( "DBE_CONFIG_USER_PSWD"             , "user_pswd");
define( "DBE_CONFIG_USER_DB"               , "user_db");



require_once "DBEManagerSql.1.1.php";
require_once "DBEManagerPagination.1.1.php";
require_once "DBEManagerTasks.1.0.php";



/*
 *
 *
 */
class DBEdit
{
    // messaggio di errore
    public $errore;

    // versione
    public $version;

    // numero to righe in tabella
    public $row_total;

    // messaggio all'utente
    private $usr_msg;

    // configurazione dell'oggetto
    private $config;

    // query eseguita
    private $sql;

    // risultato della query
    private $query_result;

    // azione richiesta
    private $action;

    // prefisso per vari nomi di uso interno
    private $prefix;

    // dbedit form name
    private $form_name;

    // metadata colonne
    private $col_meta;

    // nomi delle colonne
    private $col_names;

    // gestione della paginazione
    private $paginator;

    // gestione dei tasks
    private $task_manager;

    // gestione SQL query
    private $sql_mgr;

    // mappa azioni dai codici numerici ai codici lettera
    private static $action_codes = array( DBE_ACTION_INSERT => "I"
                                        , DBE_ACTION_VIEW   => "V"
                                        , DBE_ACTION_UPDATE => "U"
                                        , DBE_ACTION_DELETE => "D"
                                        , DBE_ACTION_SELECT => "S"
                                        );

    // mappa azioni dai codici numerici ai nomi
    private static $action_names = array( DBE_ACTION_INSERT => "Aggiungi"
                                        , DBE_ACTION_VIEW   => "Visualizza"
                                        , DBE_ACTION_UPDATE => "Modifica"
                                        , DBE_ACTION_DELETE => "Rimuovi"
                                        , DBE_ACTION_SELECT => "Seleziona"
                                        );



    /**
     * Inizializza DBEdit.
     *
     * @param $external_config la configurazione del db editor
     */
    public function __construct( $external_config )
    {
        $this->version = DBE_VERSION;
        $this->errore = null;
        $this->usr_msg = null;
        $this->sql = null;
        $this->prefix = "DBEF";
        $this->form_name = $this->prefix . $external_config["table_name"];

        // inizializza configurazione
        $this->config = $external_config;
        $this->config["form_name"] = $this->form_name;
        $this->config["prefix"] = $this->prefix;
        $this->config["action_codes"] = self::$action_codes;

        // database
        $this->sql_mgr = new ManagerSql( $this->config);
        if ($this->sql_mgr)
        {
            $this->col_meta = $this->sql_mgr->get_table_metadata($this->config["table_name"]);
            $this->row_total = $this->sql_mgr->get_table_row_count($this->config["table_name"]);
        }

        // nomi di colonne
        $this->set_col_names();

        // paginazione
        $this->paginator = new ManagerPagination( $this->config);

        // tasks
        $this->task_manager = new ManagerTasks( $this->config);

        // azione richiesta
        $this->set_action();
    }



    /**
     * Il controllore che gestisce le azioni del table editor.
     *
     * @return string la rappresentazion in HTML della tabella
     */
    public function execute()
    {
        // processa l'azione in corso
        $html = "";
        switch ($this->action)
        {
            case DBE_ACTION_SUBMIT_INSERT:
            {   $html = $this->html_action_submit_insert();
                break;
            }
            case DBE_ACTION_SUBMIT_UPDATE:
            {   $html = $this->html_action_submit_update();
                break;
            }
            case DBE_ACTION_DELETE:
            case DBE_ACTION_SUBMIT_DELETE:
            {   $html = $this->html_action_submit_delete();
                break;
            }
            case DBE_ACTION_INSERT:
            {   $html = $this->html_action_insert();
                break;
            }
            case DBE_ACTION_UPDATE:
            {   $html = $this->html_action_update();
                break;
            }
            case DBE_ACTION_VIEW:
            {   $html = $this->html_action_view_delete();
                break;
            }
            default:
            {   $html = $this->html_action_select();
                break;
            }
        }

        if (@$this->config["debug"])
        { $html .= "<HR>" . print_r($_REQUEST, true);
          $html .= "<HR><PRE>" . print_r($this, true) . "</PRE>";
        }
        
        $html .= '<font style="font-size:smaller;">Powered by DBEdit '.$this->version.'</font>';

        return $html;
    }



    /**
     * Salva errore.
     *
     * @param $err messaggio di errore
     */
    private function set_error( $err )
    {
        $this->errore = $err;

        if($this->sql_mgr->error)
        {
            $this->errore .= ' [' . $this->sql_mgr->error . ']';
        }

        if(isset($this->config["debug"]))
        {
            echo '<div class="'.DBE_STYLE_ERROR.'">' . $this->errore . '</div>';
        }
    }



    /**
     * Decidi l'azione corrente.
     */
    private function set_action( )
    {
        // azione dal form select
        $this->action   = isset($_REQUEST["azione"])
                        ? $_REQUEST["azione"]
                        : DBE_ACTION_SELECT
                        ;

        // azione da task manager
        if ($this->task_manager->get_task_action())
        {
            $this->action = $this->task_manager->get_task_action();
        }

        // azione dal form edit
        if (isset($_REQUEST["azione_submit"]))
        {
            $this->action = $_REQUEST["azione_submit"];
        }
    }



    /**
     * Azione SELECT
     *
     * @return string HTML che visualizza la tabella
     */
    private function html_action_select( )
    {
        $this->query_result = $this->sql_query_select();
        if ($this->query_result)
        {
            return $this->html_righe_multiple( $this->query_result);
        }
        return $this->html_usr_message();
    }



    /**
     * Azione UPDATE.
     *
     * @return string HTML per effettuare update, etc.
     */
    private function html_action_update( )
    {
        if (isset($_REQUEST["pk_values"]))
        {
            $this->query_result = $this->sql_query_select_record();
            if ($this->query_result)
            {
                return $this->sql_mgr->get_num_rows($this->query_result) > 1
                     ? $this->html_righe_multiple_update($this->query_result)
                     : $this->html_riga_singola($this->query_result)
                     ;
            }
            return $this->html_usr_message();
        }

        // se chiavi non sono definite facciamo select
        return $this->html_action_select( );
    }



    /**
     * Azione INSERT.
     *
     * @return string HTML per effettuare insert
     */
    private function html_action_insert( )
    {
        return $this->html_riga_singola_insert();
    }



    /**
     * Azione DELETE, VIEW
     *
     * @return string HTML per effettuare update, etc.
     */
    private function html_action_view_delete( )
    {
        if (isset($_REQUEST["pk_values"]))
        {
            $this->query_result = $this->sql_query_select_record();
            if ($this->query_result)
            {
                return $this->html_riga_singola($this->query_result);
            }
            return $this->html_usr_message();
        }

        // se chiavi non sono definite facciamo select
        return $this->html_action_select( );
    }



    /**
     * Azione submit DELETE.
     *
     * @return string successo = HTML del SELECT
     *                failure = stringa vuota
     */
    private function html_action_submit_delete( )
    {
        $this->query_result = $this->sql_query_delete();
        if ($this->query_result)
        {
            $this->usr_msg = "RIMOZIONE RIUSCITA";
            return $this->html_action_select( );
        }
        return $this->html_usr_message();
    }



    /**
     * Azione submit UPDATE.
     *
     * @return string successo = HTML del SELECT
     *                failure = stringa vuota
     */
    private function html_action_submit_update( )
    {
        $this->query_result = $this->sql_query_update();
        if ($this->query_result)
        {
            $this->usr_msg = "MODIFICA RIUSCITA";
            return $this->html_action_select( );
        }
        return $this->html_usr_message();
    }



    /**
     * Azione submit INSERT.
     *
     * @return string successo = HTML del SELECT
     *                failure = stringa vuota
     */
    private function html_action_submit_insert( )
    {
        $this->query_result = $this->sql_query_insert();
        if ($this->query_result)
        {
            $this->usr_msg = "INSERIMENTO RIUSCITO";
            return $this->html_action_select( );
        }
        return $this->html_usr_message();
    }



    /**
     * Visualizza il risultato di mysql_query() con righe
     * multiple in una tabella HTML. Serve per selezionare
     * un record di tabella da modificare.
     *
     * @param $query_result il risultato di mysql_query()
     * @return string HTML che visualizza la tabella
     */
    private function html_righe_multiple( $query_result )
    {
        // inietta javascript per selettore azioni
        $html = $this->task_manager->html_selector_javascript();

        // inietta javascript per gestion filtri
        $html .= $this->html_filter_javascript();

        $html .= '<div>'."\n";

        // inizia tabella
        $html .= '<FORM ID="'.$this->form_name.'" ACTION="'
                . $_SERVER["PHP_SELF"]
                . '" METHOD="post" ONSUBMIT="">'
                ."\n";
        $html .= '<TABLE class="'.DBE_STYLE_TABLE.'">'."\n";

        // principali funzioni di dbedit nel header della tabella
        $html .= $this->html_toolbar( $query_result );

        // visualizza i nomi delle colonne
        $html .= '<TR>'."\n";

        // selettore riga in prima colonna
        if (isset($this->config["table_pk"]))
        {
            $html .= '<TD class="'.DBE_STYLE_TD_HEADER.'">';
            $html .= '</TD>';
        }

        $i = 0;
        while ($i < $this->sql_mgr->get_num_fields($query_result))
        {
            $meta = $this->sql_mgr->get_field($query_result, $i);
            if ($meta && (! isset($this->config["column"][$meta->name]["hidden"])))
            {
                $html .= '<TD class="'.DBE_STYLE_TD_HEADER.'">'."\n";
                $html .= $this->html_col_label($meta->name);
                $html .= '</TD>';
            }
            $i++;
        }

        // selettore tasks in ultima colonna
        $html .= $this->task_manager->html_column_title();

        $html .= '</TR>'."\n";

        // visualizza tutte le righe
        while ($row = $this->sql_mgr->get_row_assoc( $query_result))
        {
            $html .= '<TR>'."\n";

            // formatta la chiave per la riga
            if (isset($this->config["table_pk"]))
            {
                $pk_name = $this->config["table_pk"];
                $pk_value = $row["$pk_name"];

                // selettore riga in prima colonna
                $html .= '<TD class="'.DBE_STYLE_TD.'">';
                $html .= '<INPUT TYPE="checkbox" ID="pk_values[]" name="pk_values[]" value="'.$pk_value.'">'."\n";
                $html .= '</TD>';
            }

            foreach ($row as $col_name => $col_value)
            {
                if (! isset($this->config["column"][$col_name]["hidden"]))
                {
                    $html .= '<TD class="'.DBE_STYLE_TD.'">'.$col_value.'</TD>'."\n";
                }
            }

            // selettore tasks in ultima colonna
            $html .= $this->task_manager->html_selector($row);

            $html .= '</TR>'."\n";

        }
        $html .= '</TABLE>'."\n";
        $html .= $this->paginator->html_hidden_offset();
        $html .= '</FORM>'."\n";
        $html .= '</div>';
        return $html;
    }



    /**
     */
    private function html_select_azione( $pk_value )
    {
        // selettore azione
        $html = '<SELECT name="selettore_azione" style="font-size: 13px;" onchange="esegui_azione_select(this)" >';
        $html .= '<OPTION value="" selected="true">Azione</OPTION>';
        $html .= '<OPTION>Aggiungi</OPTION>';
        if ($pk_value)
        {
            $html .= '<OPTION value="'.$pk_value.'">Visualizza</OPTION>';
            $html .= '<OPTION value="'.$pk_value.'">Modifica</OPTION>';
            $html .= '<OPTION value="'.$pk_value.'">Rimuovi</OPTION>';
        }
        $html .= '</SELECT>';
        return $html;
    }



    /**
     * Visualizza il risultato di mysql_query() riga
     * singola in una tabella HTML. Serve per modificare
     * un singolo record.
     *
     * @param $query_result il risultato di mysql_query()
     * @return string HTML che visualizza la tabella
     */
    private function html_riga_singola( $query_result)
    {
        $html = '<div>'."\n";

        // visualizza il messaggio all'utente
        $html .= $this->html_usr_message();

        // inizia tabella
        $html .= '<FORM ID="'.$this->form_name.'" ACTION="' . $_SERVER["PHP_SELF"] . '" METHOD="post">'."\n";
        $html .= '<TABLE class="'.DBE_STYLE_TABLE.'">'."\n";

        $html .= $this->html_toolbar_edit();

        // visualizza una colonna per riga
        $row = $this->sql_mgr->get_row_assoc( $query_result);
        foreach($this->col_names as $col_name)
        {
            // nome della colonna
            $html .= '<TR>'."\n";
            $html .= '<TD class="'.DBE_STYLE_TD_HEADER.'">'."\n";
            $html .= $this->html_col_label($col_name);
            $html .= '</TD>'."\n";

            // valore della colonna
            $html .= '<TD class="'.DBE_STYLE_TD.'">'."\n";
            $html .= $this->html_col_value($col_name, $row[$col_name]);
            $html .= '</TD>'."\n";
            $html .= '</TR>'."\n";
        }

        $html .= '</TABLE>'."\n";
        $html .= '<INPUT NAME="azione" TYPE="hidden" value="">'."\n";
        $html .= $this->html_hidden_pk_values();
        $html .= $this->paginator->html_hidden_offset();
        $html .= '</FORM>'."\n";
        $html .= '</div>';

        return $html;
    }



    /**
     * Modifica di righe multiple.
     *
     * @param $query_result il risultato di mysql_query()
     * @return string HTML che visualizza la tabella
     */
    private function html_righe_multiple_update( $query_result)
    {
        $html = '<div>'."\n";

        // visualizza il messaggio all'utente
        $html .= $this->html_usr_message();

        // inizia tabella
        $html .= '<FORM ID="'.$this->form_name.'" ACTION="' . $_SERVER["PHP_SELF"] . '" METHOD="post">'."\n";
        $html .= '<TABLE class="'.DBE_STYLE_TABLE.'">'."\n";

        $html .= $this->html_toolbar_edit();

        // visualizza i nomi delle colonne
        $html .= '<TR>'."\n";
        foreach($this->col_names as $col_name)
        {
            $html .= '<TD class="'.DBE_STYLE_TD_HEADER.'">'.$this->html_col_label($col_name)."</TD>";
        }
        $html .= '</TR>'."\n";

        // visualizza tutte le righe
        while ($row = $this->sql_mgr->get_row_assoc( $query_result))
        {
            $html .= '<TR>'."\n";

            foreach ($this->col_names as $col_name)
            {
                $html .= '<TD class="'.DBE_STYLE_TD.'">'."\n";
                $html .= $this->html_col_value($col_name, $row[$col_name]);
                $html .= '</TD>'."\n";
            }

            $html .= '</TR>'."\n";
        }

        $html .= '</TABLE>'."\n";
        $html .= '<INPUT NAME="azione" TYPE="hidden" value="">'."\n";
        $html .= $this->html_hidden_pk_values();
        $html .= $this->paginator->html_hidden_offset();
        $html .= '</FORM>'."\n";
        $html .= '</div>';

        return $html;
    }



    /**
     * Genera campi nascosti per trasmettere pk values
     */
    private function html_hidden_pk_values()
    {
        $html = "";
        if (isset($_REQUEST["pk_values"]))
        {
            foreach($_REQUEST["pk_values"] as $value)
            {
                $html .= '<INPUT NAME="pk_values[]" TYPE="hidden" value="'.$value.'">'."\n";
            }
        }
        return $html;
    }



    /**
     * Form di inserimento di nuovi records.
     *
     * @return string HTML che visualizza il form
     */
    private function html_riga_singola_insert()
    {
        $html = '<div>'."\n";

        // inizia tabella
        $html .= '<FORM ID="'.$this->form_name.'" ACTION="' . $_SERVER["PHP_SELF"] . '" METHOD="post">'."\n";
        $html .= '<TABLE class="'.DBE_STYLE_TABLE.'">'."\n";

        $html .= $this->html_toolbar_edit();

        // visualizza una colonna per riga
        $i = 0;
        while ($i < count($this->col_names))
        {
            // nome della colonna
            $col_name = $this->col_names[$i];

            if ($this->col_meta[$col_name]->primary_key
            &&  substr_count($this->col_meta[$col_name]->field_flags, "auto_increment"))
            {
                // no op
            }
            else
            {
                // columns that participate in multiple record insert are
                // not displayed as user does not have to provide a value
                if ($col_name != $this->task_manager->get_insert_many_col_name())
                {
                    $html .= '<TR>'."\n";
                    $html .= '<TD class="'.DBE_STYLE_TD_HEADER.'">'."\n";
                    $html .= $this->html_col_label($col_name);
                    $html .= '</TD>'."\n";
                    $html .= '<TD class="'.DBE_STYLE_TD.'">'."\n";
                    $html .= $this->html_col_value($col_name, '');
                    $html .= '</TD>'."\n";
                    $html .= '</TR>'."\n";
                }
            }

            $i++;
        }

        $html .= '</TABLE>'."\n";
        $html .= '<INPUT NAME="azione" TYPE="hidden" value="">'."\n";
        $html .= $this->html_hidden_pk_values();
        $html .= $this->paginator->html_hidden_offset();
        $html .= $this->task_manager->html_hidden_task();
        $html .= '</FORM>'."\n";
        $html .= '</div>';

        return $html;
    }



    /**
     * Ritorna il nome di colonna da visualizzare.
     *
     * @param $col_name
     * @return string label di colonna
     */
    private function html_col_label( $col_name)
    {
        return isset($this->config["column"][$col_name]["name"])
             ? $this->config["column"][$col_name]["name"]
             : $col_name
             ;
    }



    /**
     * Visualizza un dato. Se l'azione e' modifica o inserisci
     * il dato puo' essere modificato nel form.
     *
     * @param $col_name nome di colonna
     * @param $col_value valore di colonna
     * @return string HTML che rappresenta il dato
     */
    private function html_col_value( $col_name, $col_value)
    {
        if (isset($this->config["column"][$col_name]["sql_fk"]))
        {
            // bisogna generare il selettore per la foreign key
            return( $this->html_col_value_fk($col_name, $col_value) );
        }
        else if(isset($this->config["column"][$col_name]["sql_enum"]))
        {
            // bisogna generare il selettore per la enumerazione
            return( $this->html_col_value_enum($col_name, $col_value) );
        }

        // assembla HTML in base all'azione in corso
        $html = "";
        switch ($this->action)
        {
            case DBE_ACTION_INSERT:
            case DBE_ACTION_UPDATE:
            {
                if ($this->col_meta[$col_name]->primary_key
                &&  substr_count($this->col_meta[$col_name]->field_flags, "auto_increment"))
                {
                    // primary key
                    $html = $col_value . '<INPUT NAME="'.$col_name.'[]" TYPE="hidden" value="'.$col_value.'">'."\n";
                }
                else if(isset($this->config["column"][$col_name]["value"]) && (! $col_value))
                {
                    // default value
                    $value = $this->config["column"][$col_name]["value"];
                    if (isset($this->config["column"][$col_name]["html"]))
                    {
                        $html = $this->config["column"][$col_name]["html"];
                        $html = str_ireplace('%name%', $col_name."[]", $html);
                        $html = str_ireplace('%value%', $value, $html);
                    }
                    else
                    {
                        $html = '<INPUT NAME="'.$col_name.'[]" TYPE="text" value="'.$value.'">'."\n";
                    }
                }
                else if (($this->col_meta[$col_name]->type == "date")
                     &&  isset($this->config["column"][$col_name]["format"]))
                {
                    // date value
                    $value = date( $this->config["column"][$col_name]["format"], strtotime($col_value));
                    $html = '<INPUT NAME="'.$col_name.'[]" TYPE="text" value="'.$value.'">'."\n";
                }
                else
                {
                    // anything else
                    if (isset($this->config["column"][$col_name]["html"]))
                    {
                        $html = $this->config["column"][$col_name]["html"];
                        $html = str_ireplace('%name%', $col_name."[]", $html);
                        $html = str_ireplace('%value%', $col_value, $html);
                    }
                    else
                    {
                        $html = '<INPUT NAME="'.$col_name.'[]" TYPE="text" value="'.$col_value.'">'."\n";
                    }
                }
                break;
            }
            default:
            {
                if (($this->col_meta[$col_name]->type == "date")
                     &&  isset($this->config["column"][$col_name]["format"]))
                {
                    $html = date( $this->config["column"][$col_name]["format"], strtotime($col_value));
                }
                else
                {
                    $html = $col_value;
                }
                break;
            }
        }
        return $html;
    }



    /**
     * Visualizza valore foreign key come selettore di opzioni.
     * Se l'azione e' modifica o inserisci
     * il dato puo' essere modificato nel form.
     *
     * @param $col_name nome di colonna
     * @param $col_value valore di colonna
     * @return string HTML che rappresenta i valori della fk
     */
    private function html_col_value_fk( $col_name, $col_value)
    {
        // processa la foreign key
        $html = "";
        if (isset($this->config["column"][$col_name]["sql_fk"]))
        {
            // usa la query specificata in configurazione per
            // ottenere la lista dei valori da visualizzare e dei
            // corrispondenti valori chiave
            //
            // la query deve ritornare ID in prima colonna
            // e il valore da mostrare all'utente in seconda colonna, per esempio
            // SELECT ID, CONCAT( NOME , ' ' , COGNOME) FROM PERSONA
            $this->sql = $this->config["column"][$col_name]["sql_fk"];

            // aggiungi filtri
            $this->sql = $this->sql_add_filter($this->sql, $col_name);

            $result = $this->sql_mgr->query($this->sql);
            if (!$result)
            {
                $this->set_error( "DB Error." );
                return $html;
            }

            // genera il selettore per la foreign key
            $html = '<SELECT name="'.$col_name.'[]" >'."\n";
            $html .= '<OPTION value=""></OPTION>'."\n";
            $num_rows = $this->sql_mgr->get_num_rows($result);
            while ($row = $this->sql_mgr->get_row_array($result))
            {
                // seleziona valore gia' esistente
                $selected = (($col_value == $row[0]) || ($num_rows == 1)) ? ' selected="true" ' : "";
                $html .= '<OPTION value="'.$row[0].'"'.$selected.'>'.$row[1].'</OPTION>'."\n";
            }
            $html .= '</SELECT>';
        }

        return $html;
    }



    /**
     * Visualizza valore foreign key come selettore di opzioni.
     * Se l'azione e' modifica o inserisci
     * il dato puo' essere modificato nel form.
     *
     * @param $col_name nome di colonna
     * @param $col_value valore di colonna
     * @return string HTML che rappresenta i valori della fk
     */
    private function html_col_value_enum( $col_name, $col_value)
    {
        // processa la foreign key
        $html = "";
        if (isset($this->config["column"][$col_name]["sql_enum"]))
        {
            // usa la query specificata in configurazione per
            // ottenere la lista dei valori da visualizzare, per esempio
            // SELECT VALORE FROM ENUM WHERE NOME = 'MATERIA' ORDER BY 1
            $this->sql = $this->config["column"][$col_name]["sql_enum"];

            // aggiungi filtri
            $this->sql = $this->sql_add_filter_enum($this->sql, $col_name);

            $result = $this->sql_mgr->query($this->sql);
            if (!$result)
            {
                $this->set_error( "DB Error." );
                return $html;
            }

            // genera il selettore per la enum
            $html = '<SELECT name="'.$col_name.'[]" >'."\n";
            $html .= '<OPTION value=""></OPTION>'."\n";
            $num_rows = $this->sql_mgr->get_num_rows($result);
            while ($row = $this->sql_mgr->get_row_array( $result))
            {
                // seleziona valore gia' esistente
                $selected = (($col_value == $row[0]) || ($num_rows == 1)) ? ' selected="true" ' : "";
                $html .= '<OPTION value="'.$row[0].'"'.$selected.'>'.$row[0].'</OPTION>'."\n";
            }

            $html .= '</SELECT>';
        }

        return $html;
    }



    /**
     * Genera l'edit header con i bottoni INSERT, MODIFY, DELETE, CANCEL
     *
     * @return string HTML che rappresenta l'header
     */
    private function html_toolbar_edit( )
    {
        // visualizza il messaggio all'utente
        $html = $this->html_usr_message();

        // in modalita' edit la tabella ha 2 colonne

        $html .= '<TR>'."\n";
        $html .= '<TD colspan="1000" class="'.DBE_STYLE_TOOLBAR.'">'."\n";
        switch ($this->action)
        {
            case DBE_ACTION_INSERT:
            {
                $html .= '<BUTTON TYPE="submit" name="azione_submit" value="'.DBE_ACTION_SUBMIT_INSERT.'" class="'.DBE_STYLE_BUTTON.'">Aggiungi</button>';
                break;
            }
            case DBE_ACTION_UPDATE:
            {
                $html .= '<BUTTON TYPE="submit" name="azione_submit" value="'.DBE_ACTION_SUBMIT_UPDATE.'" class="'.DBE_STYLE_BUTTON.'">Modifica</button>';
                break;
            }
            case DBE_ACTION_DELETE:
            {
                $html .= '<BUTTON TYPE="submit" name="azione_submit" value="'.DBE_ACTION_SUBMIT_DELETE.'" class="'.DBE_STYLE_BUTTON.'">Rimuovi</button>';
                break;
            }
            default:
            {
                // NO OP
            }
        }
        $html .= '<BUTTON TYPE="submit" name="azione_submit" value="'.DBE_ACTION_SUBMIT_CANCEL.'" class="'.DBE_STYLE_BUTTON.'">Annulla</button>';

        // filtri
        if (isset($this->config["column"]))
        {
            foreach( $this->config["column"] as $col_name => $col_info)
            {
                if (isset($col_info["filter"]))
                {
                    // propaghiamo valori in hidden fields
                    $html .= ' <INPUT NAME="'. $this->request_filter_name($col_info)
                            . '" TYPE="hidden" VALUE="'. $this->request_filter_value($col_info)
                            . '">'
                            . "\n"
                            ;
                }
            }
        }

        $html .= '</TD>'."\n";
        $html .= '</TR>'."\n";

        return $html;
    }



    /**
     * Genera il toolbar con i bottoni di paginazione, filtri, etc...
     *
     * @return string HTML che rappresenta l'header
     */
    private function html_toolbar( $query_result )
    {
        // visualizza il messaggio all'utente
        $html = $this->html_usr_message();

        if (! isset($this->config["action"])
        &&  ! $this->util_array_key_exists("filter", $this->config)
        &&  ! isset($this->config["row_count"]))
        {
            return $html;
        }

        $html .= "<TR>\n";
        $html .= '<TD colspan="1000" class="'.DBE_STYLE_TOOLBAR.'">'."\n";

        $html .= '<TABLE width="100%" cellpadding="0" cellspacing="0" border="0">'."\n";
        $html .= '<TR>'."\n";

        // operazioni IVUD
        $html .= '<TD class="'.DBE_STYLE_TOOLBAR.'">'."\n";
        $html .= $this->html_toolbar_button(DBE_ACTION_INSERT);
        $html .= $this->html_toolbar_button(DBE_ACTION_VIEW);
        $html .= $this->html_toolbar_button(DBE_ACTION_UPDATE);
        $html .= $this->html_toolbar_button(DBE_ACTION_DELETE);
        $html .= '&nbsp;&nbsp;&nbsp;</TD>'."\n";

        // filtri
        if ($this->util_array_key_exists("filter", $this->config))
        {
            $html .= '<TD class="'.DBE_STYLE_TOOLBAR.'" width="100%">'."\n";
            foreach( $this->config["column"] as $col_name => $col_info)
            {
                if (isset($col_info["filter"]))
                {
                    // quando l'utente clicca un filtro resettiamo azione e chiavi
                    $html .= ucfirst(strtolower($col_info["name"]))
                            . ' <INPUT NAME="'. $this->request_filter_name($col_info)
                            . '" TYPE="text" VALUE="'. $this->request_filter_value($col_info)
                            . '" STYLE="font-size:10px;" SIZE=10 '
                            . ' ONCLICK="filter_onclick();" '
                            . ' ONCHANGE="filter_onchange();" '
                            . ' >'
                            . "\n"
                            ;
                }
            }
            $html .= '</TD>'."\n";
        }

        // aggiungi bottoni di paginazione
        $html .= '<TD class="'.DBE_STYLE_TOOLBAR.'" align="right">&nbsp;&nbsp;&nbsp;'."\n";
        $html .= '[ ' . $this->row_total . ' righe ]&nbsp;&nbsp;';
        $html .= $this->paginator->html_buttons( $this->sql_mgr->get_num_rows( $query_result));
        $html .= '</TD>'."\n";

        $html .= "</TR>\n";
        $html .= "</TABLE>\n";

        $html .= '</TD>'."\n";
        $html .= "</TR>\n";

        return $html;
    }



    /**
     * Genera javascript per gestione filtri da iniettare prima del form DBEdit
     *
     * @return string javascript HTML tag
     */
    private function html_filter_javascript()
    {
        if ($this->util_array_key_exists("filter", $this->config))
        {
            return '
                    <script>
                        function filter_onclick( )
                        {
                            var azione = document.getElementById("azione");
                            var pk_values = document.getElementById("pk_values");
                            var offset = document.getElementById("offset");

                            if (azione)
                                azione.value = 0;

                            if (pk_values)
                                pk_values.value = null;

                            if (offset)
                                offset.value = 0;
                        }
                        function filter_onchange( )
                        {
                            var dbef = document.getElementById("'.$this->form_name.'");
                            var azione = document.getElementById("azione");
                            var pk_values = document.getElementById("pk_values");
                            var offset = document.getElementById("offset");

                            if (azione)
                                azione.value = 0;

                            if (pk_values)
                                pk_values.value = null;

                            if (offset)
                                offset.value = 0;

                            // submit form
                            dbef.submit();
                        }
                    </script>
            ';
        }
        return "";
    }



    /**
     * Genera i bottoni per le varie azioni.
     *
     * @return string HTML che rappresenta il bottone
     */
    private function html_toolbar_button( $action )
    {
        // prima di visualizzare il bottone controlla configurazione
        if (isset($this->config["action"])
        &&  (strpos($this->config["action"], self::$action_codes[$action]) !== false))
        {
            return  '<BUTTON TYPE="submit" ID="azione" NAME="azione" '
                    .'value="'.$action.'" class="'.DBE_STYLE_BUTTON.'" >'
                    .self::$action_names[$action]
                    .'</button>';
        }
    }



    /**
     * Genera il messaggio all'utente
     *
     * @return string HTML
     */
    private function html_usr_message( )
    {
        $html = "";
        if(isset($this->errore))
        {
            $html .= '<div class="'.DBE_STYLE_ERROR.'">'."\n";
            $html .= $this->errore;
            $html .= "</div>\n";
        }
        if(isset($this->usr_msg))
        {
            $html .= '<TR><TD colspan="1000" class="'.DBE_STYLE_USRMSG.'">'."\n";
            $html .= $this->usr_msg;
            $html .= "</TD></TR>\n";
        }
        if($this->task_manager->get_task_title()
        &&($this->task_manager->get_task_action() == $this->action))
        {
            $html .= '<TR><TD colspan="1000" class="'.DBE_STYLE_USRMSG.'">'."\n";
            $html .= $this->task_manager->get_task_title();
            $html .= "</TD></TR>\n";
        }
        return $html;
    }



    /**
     * Assembla ed esegui SQL select
     *
     * @return int risultato di mysql_query()
     */
    private function sql_query_select()
    {
        $this->sql = "SELECT * FROM " . $this->config["table_name"];
        if (isset($this->config["table_sql_select"]))
        {
            $this->sql = $this->config["table_sql_select"];
        }

        // aggiungi filtri
        $this->sql = $this->sql_add_filter($this->sql);

        // aggiungi paginazione
        $this->sql = $this->sql_mgr->sql_select_limit(
            $this->sql,
            $this->paginator->offset,
            $this->paginator->row_count);

        $this->query_result = $this->sql_mgr->query($this->sql);
        if (! $this->query_result)
        {
            $this->set_error( "DB Error." );
        }

        return $this->query_result;
    }



    /**
     * Assembla ed esegui SQL select che ritorna singolo record
     *
     * @return int risultato di mysql_query()
     */
    private function sql_query_select_record()
    {
        // visualizza una o piu' righe
        // TODO l'ordine delle PK ritornate dalla query e` casuale
        $this->sql = "select * from " . $this->config["table_name"]
             . " where " . $this->sql_pk_filter($_REQUEST["pk_values"]);

        $this->query_result = $this->sql_mgr->query($this->sql);
        if (! $this->query_result)
        {
            $this->set_error( "DB Error." );
        }

        return $this->query_result;
    }



    /**
     * Inserisce nuovi records.
     *
     * @return resource risultato dell'ultima mysql_query()
     */
    private function sql_query_insert( )
    {
        $query_result = null;
        $col_name = $this->task_manager->get_insert_many_col_name();
        if ($col_name)
        {
            // the insert task provides the SQL to generate the records
            $this->sql = $this->task_manager->get_insert_many_sql();
            $rows = $this->sql_mgr->query_all($this->sql);
            if (count($rows))
            {
                print_r($rows);
                foreach($rows as $i => $row)
                {
                    // aggiungi il valore mancante a $_REQUEST e inserisci record
                    $_REQUEST[$col_name] = array($row[$col_name]);
                    $query_result = $this->sql_query_insert_record();
                    if (! $this->query_result)
                    {
                        break;
                    }
                }
            }
            else
            {
                $this->set_error( "La query non ha risultato [$this->sql]." );
            }
        }
        else
        {
            $query_result = $this->sql_query_insert_record();
        }

        return $query_result;
    }



    /**
     * Assembla ed esegui SQL insert di un nuovo record
     *
     * @return int risultato di mysql_query()
     */
    private function sql_query_insert_record()
    {
        $this->sql = "insert into " . $this->config["table_name"] . " (";
        foreach($this->col_names as $col_name)
        {
            $this->sql .= strtoupper($col_name) . ",";
        }
        $this->sql = rtrim($this->sql, ",") . ") values(";

        $numcol = count($this->col_names);
        for($i=0; $i<$numcol; $i++)
        {
            $name = $this->col_names[$i];
            if ($this->col_meta[$name]->primary_key)
            {
                if (substr_count($this->col_meta[$name]->field_flags, "auto_increment"))
                {
                    $this->sql .= "null,";
                }
                else
                {
                    if($this->col_meta[$name]->numeric)
                    {
                        $this->sql .= (is_numeric($_REQUEST[$name][0]) ? $_REQUEST[$name][0] : 'null') . ",";
                    }
                    else
                    {
                        $this->sql .= "'" . $_REQUEST[$name][0] . "',";
                    }
                }
            }
            else
            {
                if ($this->col_meta[$name]->type == "date")
                {
                    $this->sql .= "'" . date( "Ymd", strtotime($_REQUEST[$name][0])) . "',";
                }
                else if($this->col_meta[$name]->numeric)
                {
                    $this->sql .= (is_numeric($_REQUEST["$name"][0]) ? $_REQUEST["$name"][0] : 'null') . ",";
                }
                else
                {
                    $this->sql .= "'" . $_REQUEST["$name"][0] . "',";
                }
            }
        }
        $this->sql = rtrim($this->sql, ",") . ");";
        $this->query_result = $this->sql_mgr->query($this->sql);
        if(! $this->query_result )
        {
            $this->set_error( 'Impossibile inserire un nuovo record.' );
        }

        return $this->query_result ;
    }



    /**
     * Modifica i records selezionati.
     *
     * @return resource risultato dell'ultima mysql_query()
     */
    private function sql_query_update( )
    {
        if (isset($_REQUEST["pk_values"]))
        {
            for($i=0; $i<count( $_REQUEST["pk_values"]); $i++)
            {
                $this->query_result = $this->sql_query_update_record($i);
                if (! $this->query_result)
                {
                    return $this->query_result;
                }
            }
            return $this->query_result;
        }
    }



    /**
     * Assembla ed esegui SQL update di un record.
     *
     * @param int $nth this is the nth element of the arrays from $_REQUEST
     *                 that hold the column values
     * @return int risultato di mysql_query()
     */
    private function sql_query_update_record( $nth )
    {
        $numcol = count($this->col_names);
        $this->sql ="update " . $this->config["table_name"] . " set ";

        $pk_var = "pk_values";
        for($i=0;$i<$numcol;$i++)
        {
            $name = $this->col_names[$i];
            if(($this->col_meta[$name]->primary_key)
            && (substr_count($this->col_meta[$name]->field_flags, "auto_increment")))
            {
                $pk_var = $name;
            }
            else
            {
                if (! $_REQUEST[$name][$nth])
                {
                    $this->sql .= ' ' . $name . " = null,";
                }
                else if ($this->col_meta[$name]->type == "date")
                {
                    $this->sql .= ' ' . $name . " = '" . date( "Ymd", strtotime($_REQUEST[$name][$nth])) . "',";
                }
                else if($this->col_meta[$name]->numeric)
                {
                   $this->sql .= ' ' . $name . " = " . $_REQUEST[$name][$nth] . ",";
                }
                else
                {
                   $this->sql .= ' ' . $name . " = '" . $_REQUEST[$name][$nth] . "',";
                }
            }
        }
        $this->sql = rtrim($this->sql, ",") . " where " . $this->sql_pk_filter(array($_REQUEST[$pk_var][$nth]));
        $this->query_result  = $this->sql_mgr->query($this->sql);
        if(! $this->query_result )
        {
            $this->set_error( 'Impossibile modificare il record ' . $_REQUEST[$pk_var][$nth] );
        }

        return $this->query_result ;
    }



    /**
     * Assembla ed esegui SQL delete
     *
     * @return int risultato di mysql_query()
     */
    private function sql_query_delete()
    {
        // cancella record
        $this->sql = "delete from " . $this->config["table_name"]
             . " where " . $this->sql_pk_filter($_REQUEST["pk_values"]);

        $this->query_result  = $this->sql_mgr->query($this->sql);
        if (! $this->query_result )
        {
            $this->set_error( "DB Error." );
        }

        return $this->query_result ;
    }



    /**
     *
     *
     */
    private function sql_pk_filter( $pk_values )
    {
        foreach($this->col_meta as $col_name => $info_field)
        {
            if($info_field->primary_key)
            {
                return $this->sql_mgr->sql_where_col_in($col_name, $info_field->numeric, $pk_values);
            }
        }

        return "";
    }



    /**
     * Add filters to an SQL query
     * @return string the modified SQL
     */
    private function sql_add_filter( $sql, $requested_col_name = null)
    {
        // aggiungi filtri
        if ($this->util_array_key_exists("filter", $this->config)
        ||  $this->task_manager->get_task_name())
        {
            // append WHERE filters
            foreach( $this->config["column"] as $col_name => $col_info)
            {
                if (isset($col_info["filter"])
                &&  (is_null($requested_col_name) || ($requested_col_name == $col_name)))
                {
                    $request_name = $this->request_filter_name($col_info);
                    $request_value = $this->request_filter_value($col_info);
                    if (strlen($request_value))
                    {
                        $sql = $this->sql_mgr->sql_select_filter($sql, $col_info["filter"], $request_value);
                    }
                }
            }

            // append task filters
            if ($this->task_manager->get_task_name()
            &&  $this->task_manager->get_task_action() == DBE_ACTION_SELECT)
            {
                $sql = $this->sql_mgr->sql_select_where($sql, $this->task_manager->get_task_where());
            }
        }

        return $sql;
    }



    /**
     * Add filter to an enum SQL query
     * @return string the modified SQL
     */
    private function sql_add_filter_enum( $sql, $col_name)
    {
        // aggiungi filtro
        if (isset($this->config["column"][$col_name]["filter"]))
        {
            // extract the SELECT clause
            $sql_select = $this->sql_mgr->sql_split_select($sql);

            // append filter
            $request_name = $this->request_filter_name($this->config["column"][$col_name]);
            $request_value = $this->request_filter_value($this->config["column"][$col_name]);
            if (strlen($request_value))
            {
                $sql = $this->sql_mgr->sql_select_filter($sql, $sql_select, $request_value);
            }
        }

        return $sql;
    }



    /**
     * List of columns to be manipulated.
     *
     * @return array of column names
     */
    private function set_col_names()
    {
        $i = 0;
        $this->col_names = array();

        // synch con la lista delle colonna da configurazione
        if (isset($this->config["column"]) && is_array($this->config["column"]))
        {
            // aggiungiamo colonne da configurazione
            foreach($this->config["column"] as $col_name => $col_info)
            {
                if (array_key_exists($col_name, $this->col_meta))
                {
                    $this->col_names[$i] = $col_name;
                    $i++;
                }
            }
        }

        // completiamo con colonne da metadata
        foreach($this->col_meta as $col_name => $col_info)
        {
            if (!in_array( $col_name, $this->col_names))
            {
                $this->col_names[$i] = $col_name;
                $i++;
            }
        }
    }



    /**
     * Find if key exists in multi dimensional array
     * @return bool true if found false if not found
     */
    private function util_array_key_exists( $key, $array)
    {
           if( !is_array( $array))
           {
               return false;
           }

           if(array_key_exists( $key, $array))
           {
               return true;
           }

           foreach( $array as $row )
           {
               // recursively search internal arrays
               if($this->util_array_key_exists( $key, $row))
               {
                   return true;
               }
           }

           return false;
    }



    /**
     * Calculate a request filter name from column config info.
     * @return the filter name used in $_REQUEST
     */
    private function request_filter_name( $config_col_info)
    {
        $request_name = NULL;
        if (isset($config_col_info["filter"]))
        {
            $request_name = $this->prefix . $config_col_info["filter"];
        }
        return $request_name;
    }



    /**
     * Find a request filter value from column config info.
     * @return the filter value stored in $_REQUEST
     */
    private function request_filter_value( $config_col_info)
    {
        $request_value = "";
        $request_name = $this->request_filter_name( $config_col_info);
        if ($request_name)
        {
            $request_value = isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : "";
        }
        return $request_value;
    }

    

} // end of DBEdit


?>
