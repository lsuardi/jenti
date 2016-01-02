<?php

/**
 * Gestione dei tasks
 */
class ManagerTasks
{
    private $form_name;
    private $prefix;
    private $action_codes;
    private $task_links = null;
    private $tasks = null;
    private $task_name = null;
    private $task_action = null;


    /**
     * Crea nuovo Manager.
     *
     * @param $config configurazione DBEdit
     */
    public function __construct( $config)
    {
        $this->form_name = $config["form_name"];
        $this->prefix = $config["prefix"];
        $this->action_codes = $config["action_codes"];

        if (isset($config["task_link"]))
        {
            $this->task_links = $config["task_link"];
        }

        if (isset($config["task"]))
        {
            $this->tasks = $config["task"];

            if (isset($_REQUEST["task"]) && isset($this->tasks[$_REQUEST["task"]]))
            {
                $this->task_name = $_REQUEST["task"];

                $task = $this->tasks[$this->task_name];
                $actions = array_keys( $this->action_codes, $task["action"]);
                $this->task_action = $actions[0];

                // il task puo' richiedere l'uso di filtri in inserimento
                if (($this->task_action == DBE_ACTION_INSERT)
                &&   isset($this->tasks[$this->task_name]["filter"]))
                {
                    foreach ($this->tasks[$this->task_name]["filter"] as $col_name => $filter_value)
                    {
                        $filter_name = $this->prefix . $col_name;
                        $_REQUEST[$filter_name] = $filter_value;
                    }
                }
            }
        }
    }



    public function get_task_name()
    {
        return $this->task_name;
    }



    public function get_task_action()
    {
        return $this->task_action;
    }



    public function get_task_where()
    {
        return isset($this->tasks[$this->task_name]["where"])
             ? $this->tasks[$this->task_name]["where"]
             : ""
             ;
     }



    public function get_task_title()
    {
        return isset($this->tasks[$this->task_name]["title"])
             ? $this->tasks[$this->task_name]["title"]
             : ""
             ;
    }



    public function get_task_filter_value( $col_name)
    {
        if (($this->task_action == DBE_ACTION_INSERT)
        &&   isset($this->tasks[$this->task_name]["filter"][$col_name]))
        {
            return $this->tasks[$this->task_name]["filter"][$col_name];
        }
        return "";
    }



    public function get_insert_many_col_name( )
    {
        if (($this->task_action == DBE_ACTION_INSERT)
        &&   isset($this->tasks[$this->task_name]["many"]))
        {
            return key($this->tasks[$this->task_name]["many"]);
        }
        return "";
    }



    public function get_insert_many_sql( )
    {
        if (($this->task_action == DBE_ACTION_INSERT)
        &&   isset($this->tasks[$this->task_name]["many"]))
        {
            $col_name = key($this->tasks[$this->task_name]["many"]);
            return($this->tasks[$this->task_name]["many"][$col_name]);
        }
        return "";
    }



    public function html_column_title()
    {
        if ($this->task_links)
        {
            return '<TD class="'.DBE_STYLE_TD_HEADER.'">AZIONI</TD>';
        }
        return "";
    }



    public function html_selector( $row)
    {
        if ($this->task_links)
        {
            // selettore tasks
            $html =  '<TD class="'.DBE_STYLE_TD.'">';
            $html .= '<SELECT name="selettore_azioni" style="font-size: 13px;" onchange="esegui_selettore_azioni(this)" >';
            $html .= '<OPTION value="" selected="true">Azione</OPTION>';
            foreach($this->task_links as $task_idx => $task_link)
            {
                // all task info included in the action URL
                $url = $task_link["url"] . "?task=" . $task_link["name"];
                if (isset($task_link["param"]))
                {
                    foreach($task_link["param"] as $param)
                    {
                        $col_name = $param["col"];
                        $url .= "&".$param["name"]."=".$row[$col_name];
                    }
                }
                $html .= '<OPTION value="'.$url.'">'.$task_link["name"].'</OPTION>';
            }
            $html .= '</SELECT>';
            $html .= '</TD>';

            return $html;

        }
        return "";
    }



    public function html_selector_javascript()
    {
        if ($this->task_links)
        {
            return '
                <script>
                    function esegui_selettore_azioni( selettore )
                    {
                        //alert( "azione selezionata:"
                        //     + " " + selettore.selectedIndex
                        //     + " " + selettore.options[selettore.selectedIndex].text
                        //     + " " + selettore.options[selettore.selectedIndex].value
                        //);

                        var tef = document.getElementById("'.$this->form_name.'");
                        tef.action = selettore.options[selettore.selectedIndex].value;
                        var offset = document.getElementById("offset");
                        if (offset)
                                offset.value = 0;

                        // submit form
                        tef.submit();
                    }
                </script>
            ';
        }
        return "";
    }



    /**
     * @return string html per propagazione dei dati del task
     */
    public function html_hidden_task()
    {
        $html = '<INPUT NAME="task" ID="task" TYPE="hidden" value="'.$this->task_name.'">'."\n";
        if (is_array($_GET))
        {
            // forward any URL parameters
            foreach($_GET as $name => $value)
            {
                $html .= '<INPUT NAME="'.$name.'" ID="'.$name.'" TYPE="hidden" value="'.$value.'">'."\n";
            }
        }
        return $html;
    }

} // end of ManagerTasks

?>
