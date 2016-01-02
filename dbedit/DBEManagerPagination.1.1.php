<?php

/**
 * Gestione della paginazione
 */
class ManagerPagination
{    
    // inizio del limit da usare nel SQL SELECT
    public $offset;

    // numero di righe nella pagina
    public $row_count;
    
    
    
    /**
     * Crea nuovo Manager.
     *
     * @param $config configurazione di DBEdit
     * @param $sql_mgr gestore di SQL
     */
    public function __construct( $config) 
    {     
        $this->row_count    = isset($config['row_count'])
                            ? $config['row_count']
                            : 0
                            ;
        
        // calcola offset
        if(isset($_REQUEST['pagina']))
        {
            if($_REQUEST['pagina'] == 'successivo')
            {
                $this->offset = $_REQUEST['offset'] + $this->row_count;
            }
            else
            {
                $this->offset = $_REQUEST['offset'] - $this->row_count;
            }
        }
        else
        {
            $this->offset = isset($_REQUEST['offset'])
                          ? $_REQUEST['offset']
                          : 0;
        }
    }
    
    
    
    /**
     * @return string html del form field offset da appendere al DBEdit form
     */
    public function html_hidden_offset()
    {
        return '<INPUT NAME="offset" ID="offset" TYPE="hidden" value="'.$this->offset.'">'."\n";
    }
    
    
    
    /**
     * @return string hrml dei bottoni di paginazione
     */
    public function html_buttons( $num_rows)
    {
        $html = "";
        if($this->row_count)
        {
            $disabled = ($this->offset == 0) 
                      ? " disabled " 
                      : "";
            $label = '<<&nbsp;'.$this->offset.'&nbsp;';
            $html .= '<BUTTON TYPE="submit" NAME="pagina" value="precedente" class="'.DBE_STYLE_BUTTON.'" '.$disabled.'>'.$label.'</button>';
            
            $disabled = ($num_rows < $this->row_count) 
                      ? " disabled " 
                      : "";
            $label = '&nbsp;'.($this->offset + $num_rows).'&nbsp;>>';
            $html .= '<BUTTON TYPE="submit" NAME="pagina" value="successivo" class="'.DBE_STYLE_BUTTON.'" '.$disabled.'>'.$label.'</button>';
        }
        return $html;
    }
    
}

?>
