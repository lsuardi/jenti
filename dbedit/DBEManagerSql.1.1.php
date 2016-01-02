<?php

// Copyright 2015 - NINETY-DEGREES

// TODO

// HISTORY
// 20150215 - created version 1.1
// 20150215 - query_insert(), get_insert_id(), errno
// 20150309 - query_update()

/**
 * Gestione di query SQL
 */
class ManagerSql
{
    // messaggio di errore
    public $error;
    public $errno;

    private $hostname;
    private $user_name;
    private $user_pswd;
    private $user_db;

    // connessione a mysql server
    private $link;

    // query eseguita
    private $sql;

    // risultato dell'ultima query
    private $query_result;
    
    // metadata
    private $metadata = array();

    private $debug;



    /**
     * Crea nuovo Manager.
     *
     * @param $config configurazione di DBEdit
     */
    public function __construct( & $config )
    {
        $this->hostname = $config["hostname"];
        $this->user_name = $config["user_name"];
        $this->user_pswd = $config["user_pswd"];
        $this->user_db = $config["user_db"];
        $this->debug = @$config["debug"];

        $this->init();
    }



    /**
     * Getters e setters
     */
    public function get_num_fields( $query_result)
    {
        return mysql_num_fields( $query_result);
    }
    public function get_field( $query_result, $i)
    {
        return mysql_fetch_field( $query_result, $i);
    }
    public function get_row_assoc( $query_result)
    {
        return mysql_fetch_assoc( $query_result);
    }
    public function get_row_array( $query_result)
    {
        return mysql_fetch_array( $query_result);
    }
    public function get_num_rows( $query_result)
    {
        return mysql_num_rows( $query_result);
    }
    public function get_insert_id( )
    {
        return mysql_insert_id( );
    }



    /**
     * All information about a table columns
     *
     * @return array di nomi di colonne e relative info in formato mysql
     */
    public function get_table_metadata( $table_name)
    {
        $metadata = null;
        $list_fields = mysql_list_fields($this->user_db, $table_name);
        if (! $list_fields)
        {
            $this->set_error( "DB Error, could not get metadata." );
            return $metadata;
        }
        $num_fields = mysql_num_fields($list_fields);
        for ($i=0; (($i<$num_fields) && ($info_field = mysql_fetch_field($list_fields, $i))); $i++)
        {
            // aggiungi field flags e salva table metadata
            $info_field->field_flags = mysql_field_flags($list_fields, $i);
            $metadata[$info_field->name] = $info_field;
        }

        return $metadata;
    }

    
    
    /**
     * Get a table rows count.
     *
     * @param string $table_name name of the table
     */
    public function get_table_row_count($table_name)
    {
        $sql = "SELECT COUNT(*) ROW_COUNT FROM {$table_name}";
        $query_result = $this->query($sql);
        if ($query_result)
        {
            $result_assoc = $this->get_row_assoc($query_result);  
            return $result_assoc["ROW_COUNT"];
        }
        return 0;
    }



    /**
     * Esegui SQL select
     *
     * @return int risultato di mysql_query()
     */
    public function query( $sql)
    {
        $this->error = null;
        $this->sql = $sql;
        $this->query_result = mysql_query($this->sql, $this->link);
        if (! $this->query_result)
        {
            $this->set_error( "DB Error, could not query the database." );
        }

        if ($this->debug)
        {
            echo "<HR>".$this->sql;
        }

        return $this->query_result;
    }



    /**
     * Esegui SQL select
     *
     * @return array con l'intero risultato
     */
    public function query_all( $sql)
    {
        $this->error = null;
        $this->sql = $sql;
        $query_result = mysql_query($this->sql, $this->link);
        if (! $query_result)
        {
            $this->set_error( "DB Error, could not query the database." );
            return $query_result;
        }

        // costruisci risultato
        $result_set = array();
        while ($row = mysql_fetch_array( $query_result))
        {
            $result_set[] = $row;
        }

        return $result_set;
    }



    /**
     * Esegui SQL INSERT
     *
     * @return object query_result
     */
    public function query_insert( $table_name, $row_data)
    {
        $this->error = null;
        $this->errno = 0;
        
        // get metadata
        if (!isset($this->metadata[$table_name]))
        {
            $this->metadata[$table_name] = $this->get_table_metadata($table_name);
        }
        $table_meta = $this->metadata[$table_name];
        if (! $table_meta)
        {
            return false;
        }
        
        // build sql
        $this->sql = "INSERT INTO {$table_name} (";
        foreach ($table_meta as $col_name => $col_info)
        {
            if (isset($row_data[$col_name]))
            {
                $this->sql .= "$col_name,";                
            }
        }
        $this->sql = rtrim($this->sql, ",") . ") VALUES (";
        foreach ($table_meta as $col_name => $col_info)
        {
            if (isset($row_data[$col_name]))
            {
                $row_data[$col_name] = trim($row_data[$col_name]);
                if ($col_info->primary_key)
                {
                    if (substr_count($col_info->field_flags, "auto_increment"))
                    {
                        $this->sql .= "null,";
                    }
                    else
                    {
                        if($col_info->numeric)
                        {
                            $this->sql .= "{$row_data[$col_name]},";
                        }
                        else
                        {
                            $this->sql .= "'{$row_data[$col_name]}',";
                        }
                    }
                }
                else
                {
                    if ($col_info->type == "date")
                    {
                        $this->sql .= "'" . date( "Ymd", strtotime($row_data[$col_name])) . "',";
                    }
                    else if($col_info->numeric)
                    {
                        $this->sql .= "{$row_data[$col_name]},";
                    }
                    else
                    {
                        $data = mysql_escape_string($row_data[$col_name]);
                        $this->sql .= "'$data',";
                    }
                }
            }
        }
        $this->sql = rtrim($this->sql, ",") . ");";        
        
        // execute sql
        $query_result = mysql_query($this->sql, $this->link);
        if (! $query_result)
        {
            $this->set_error( "DB Error, could not insert row." );
        }

        return $query_result;
    }



    /**
     * Esegui SQL UPDATE
     *
     * @return object query_result
     */
    public function query_update($table_name, $row_data)
    {
        $this->error = null;
        $this->errno = 0;
        
        // get metadata
        if (!isset($this->metadata[$table_name]))
        {
            $this->metadata[$table_name] = $this->get_table_metadata($table_name);
        }
        $table_meta = $this->metadata[$table_name];
        
        // build sql
        $this->sql = "UPDATE {$table_name} SET ";
        foreach ($table_meta as $col_name => $col_info)
        {
            if (isset($row_data[$col_name]))
            {
                $row_data[$col_name] = trim($row_data[$col_name]);
                $this->sql .= "$col_name = ";                
                if ($col_info->type == "date")
                {
                    $this->sql .= "'" . date( "Ymd", strtotime($row_data[$col_name])) . "',";
                }
                else if($col_info->numeric)
                {
                    $this->sql .= "{$row_data[$col_name]},";
                }
                else
                {
                    $data = mysql_escape_string($row_data[$col_name]);
                    $this->sql .= "'$data',";
                }
            }
        }
        $this->sql = rtrim($this->sql, ",") 
                   . " WHERE ID = {$row_data["ID"]} ;";
        
        // execute sql
        $query_result = mysql_query($this->sql, $this->link);
        if (! $query_result)
        {
            $this->set_error( "DB Error, could not update row." );
        }

        return $query_result;
    }
    
    
    
    /**
     * Aggiungi LIMIT to SQL
     */
    public function sql_select_limit( $sql, $offset, $row_count)
    {
        if($row_count)
        {
            $this->sql = $sql . " LIMIT ".$offset.",".$row_count;
        }
        return $this->sql;
    }



    /**
     * Aggiungi filtri to SQL
     */
    public function sql_select_filter( $sql, $filter_expression, $filter_value)
    {
        // split sql at the level of main clauses
        $sql_parts = $this->sql_split_at_where_end($sql);

        $sql_parts[0] .= substr_count($sql_parts[0], "WHERE") ? " AND " : " WHERE ";
        $sql_parts[0] .= $filter_expression . " LIKE '%".$filter_value."%'";

        // rebuild full SQL
        $sql = $sql_parts[0] . " " . $sql_parts[1];

        return $sql;
    }



    /**
     * Aggiungi where expression to SQL
     */
    public function sql_select_where( $sql, $where_expression)
    {
        // split sql at the level of main clauses
        $sql_parts = $this->sql_split_at_where_end($sql);

        $sql_parts[0] .= substr_count($sql_parts[0], "WHERE") ? " AND " : " WHERE ";
        $sql_parts[0] .= $where_expression;

        // rebuild full SQL
        $sql = $sql_parts[0] . " " . $sql_parts[1];

        return $sql;
    }



    /**
     * Estrae SELECT clause from SQL
     */
    public function sql_split_select( $sql)
    {
        // extract the SELECT clause
        $sql_upper      = strtoupper($sql);
        $pos_select     = strpos( $sql_upper, "SELECT");
        $pos_from       = strpos( $sql_upper, "FROM");
        $sql_select     = substr( $sql, $pos_select+6, $pos_from-$pos_select-6);

        return $sql_select;
    }



    /**
     * Genera WHERE col IN ( ... )
     */
    public function sql_where_col_in( $col_name, $col_numeric, $value_array)
    {
        $html = $col_name . " IN (";
        foreach ($value_array as $value)
        {
            $html .= $col_numeric ? "$value," : "'$value',";
        }
        return rtrim($html, ",") . ")";
    }



    /**
     * Salva errore.
     *
     * @param $err messaggio di errore
     */
    private function set_error( $err )
    {
        $this->error = "DBEManagerSql 1.1: $err";

        if(mysql_errno())
        {
            $this->errno = mysql_errno();
            $this->error .= " [{$this->errno}: " . mysql_error() . ']';
        }

        if($this->sql)
        {
            $this->error .= ' [' . $this->sql . ']';
        }

        if($this->debug)
        {
            echo $this->error;
        }
    }



    /**
     * Inizializza connessione a database e metadata
     */
    private function init()
    {
        // connessione al mysql server
        if (!$this->link = mysql_connect( $this->hostname
                                        , $this->user_name
                                        , $this->user_pswd))
        {
            $this->set_error( 'Could not connect to mysql' );
            return;
        }

        // seleziona il database
        if (!mysql_select_db($this->user_db, $this->link))
        {
            $this->set_error( 'Could not select database' );
            return;
        }
    }



    /**
     * Split SQL at WHERE
     */
    private function sql_split_at_where_end( $sql)
    {
        // split sql at the level of main clauses
        $sql_upper      = strtoupper($sql);
        $pos_groupby    = strpos( $sql_upper, "GROUP BY");
        $pos_having     = strpos( $sql_upper, "HAVING");
        $pos_orderby    = strpos( $sql_upper, "ORDER BY");

        $split_pos      = $pos_orderby ? $pos_orderby : 0;
        $split_pos      = $pos_groupby
                        ? ($split_pos > 0 ? min($pos_groupby, $split_pos) : $pos_groupby)
                        : $split_pos
                        ;
        $split_pos      = $pos_having
                        ? ($split_pos > 0 ? min($pos_having, $split_pos)  : $pos_having)
                        : $split_pos
                        ;

        $sql_part1      = $split_pos ? substr( $sql, 0, $split_pos-1) : $sql;
        $sql_part2      = $split_pos ? substr( $sql, $split_pos, strlen($sql)) : "";

        return array($sql_part1, $sql_part2);
    }
}

?>
