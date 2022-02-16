<?php

namespace Veranstaltungsplanung;

/**
 * Class SQLQuery
 * This class is to MANAGE a query that is maybe more complex than a standard sql-query.
 * You can easily add filters, joins and additional select-parts.
 *
 * $query = SQLQuery::table("auth_user_md5")
 *              ->join("seminar_user", "seminar_user.user_id = auth_user_md5.user_id")
 *              ->join("seminar_inst", "seminar_inst.seminar_id = seminar_user.Seminar_id")
 *              ->where("seminar_inst.institut_id = :institut_id",array('institut_id' => $inst->getId()));
 * if (Request::get("status") {
 *      $query->where("auth_user_md5.status = :status", array('status' => Request::get("status"));
 * }
 * if ($query->count() <= 500) {
 *      $user_data = $query->fetchAll();
 * } else {
 *      PageLayout::postInfo(_("Geben Sie mehr Filter ein."));
 * }
 *
 *
 */
class SQLQuery {

    public $settings = [];
    public $name = null;
    public $combine_where_statements_with = "AND";

    static public function table($table, $query_name = null)
    {
        $query = new SQLQuery($table, $query_name);
        return $query;
    }

    /**
     * Constructor of the query. A main table is needed.
     * @param string $table : a database table
     * @param string name :
     */
    public function __construct($table, $query_name = null)
    {
        $this->settings['table'] = $table;
        $this->name = $query_name;
    }

    public function select($select, $statement = null) {
        if (!is_array($select)) {
            $select = $statement ? [$select => $statement] : [$select => ""];
        }
        foreach ($select as $alias => $statement) {
            $this->settings['select'][$alias] = $select;
        }
    }

    /**
     * Joins a table to the query. You can omit $table and just call join($tablename,
     * $on, $join) if you don't need an alias.
     * @param $alias : table-name or an alias.
     * @param null $table : optional table name. Use this if you want to use an alias.
     * @param string $on : any condition like "t1.user_id = t2.id"
     * @param string $join : type of joining "INNER JOIN" or "LEFT JOIN"
     * @return $this
     */
    public function join($alias, $table = null, $on = "", $join = "INNER JOIN", $before = null)
    {
        if (preg_match("/[\s=]/", $table)) {
            //user left away the $table var and shifted the other variables:
            $join = $on;
            $on = $table;
            $table = null;
        }
        $j = [];
        if ($table) {
            $j['table'] = $table;
        }
        if ($on) {
            $j['on'] = $on;
        }
        if ($join) {
            $j['join'] = $join;
        }
        if ($before === null) {
            $this->settings['joins'][$alias] = $j;
        } else {
            $index = array_search($before, array_keys($this->settings['joins']));
            $this->settings['joins'] = array_slice($this->settings['joins'], 0, $index, true) +
                [$alias => $j] +
                array_slice($this->settings['joins'], $index, NULL, true);
        }
        return $this;
    }

    /**
     * Adds a condition to the query. Any conditions will get concatenated by AND.
     * @param $name : the name of the condition. Use any name. It will be treated as an identifier.
     * @param null $condition
     * @param array $parameter
     * @return $this
     */
    public function where($name, $condition = null, $parameter = [])
    {
        if ($condition === null) {
            unset($this->settings['where'][$name]);
        } elseif($parameter === null) {
            $this->settings['where'][$name] = $name;
            $this->settings['parameter'] = array_merge((array) $this->settings['parameter'], $condition);
        } else {
            $this->settings['where'][$name] = $condition;
            $this->settings['parameter'] = array_merge((array) $this->settings['parameter'], $parameter);
        }
        return $this;
    }

    /**
     * Removes a formerly defined where condition identified by its name.
     * @param $name : the name of the where-condition that was defined in where-method..
     * @return $this
     */
    public function removeWhereCondition($name)
    {
        unset($this->settings['where'][$name]);
        return $this;
    }

    /**
     * Sets the grouping of the resultset.
     * @param string $clause : the clause to sort after like "auth_user_md5.user_id"
     * @return $this
     */
    public function groupBy($clause)
    {
        $this->settings['groupby'] = $clause;
        return $this;
    }

    /**
     * Sets the order of the resultset.
     * @param string $clause : the clause to sort after like "Vorname ASC, position DESC"
     * @return $this
     */
    public function orderBy($clause)
    {
        $this->settings['order'] = $clause;
        return $this;
    }

    public function addSurroundingQuery($superquery)
    {
        $this->settings['superquery'] = $superquery;
        return $this;
    }

    /**
     * Fetches the number of entries of the resultset.
     * @return integer.
     */
    public function count()
    {
        \NotificationCenter::postNotification("SQLQueryWillExecute", $this);
        $sql = "SELECT COUNT(DISTINCT ".implode(", ", $this->getPrimaryKey()).") ".$this->getQuery();

        $statement = \DBManager::get()->prepare($sql);
        $statement->execute((array) $this->settings['parameter']);
        return (int) $statement->fetch(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * Fetches the whole resultset as an array of associative arrays. If you define
     * a sorm_class the result will be an array of the sorm-objects.
     * @return array of arrays or array of objects.
     */
    public function fetchAll($sorm_class = null)
    {
        \NotificationCenter::postNotification("SQLQueryWillExecute", $this);
        $sql = "SELECT `".$this->settings['table']."`.* ";

        foreach ((array) $this->settings['select'] as $alias => $statement) {
            $sql .= $statement ? "`".$statement."` AS `".$alias."` " : "`".$alias."`";
        }

        $sql .= $this->getQuery();

        if ($this->settings['superquery']) {
            $sql = str_replace("{{query}}", "(" . $sql . ")", $this->settings['superquery']);
        }

        $statement = \DBManager::get()->prepare($sql);
        $statement->execute((array) $this->settings['parameter']);
        $alldata = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (!$sorm_class) {
            return $alldata;
        } else {
            $objects = [];
            foreach ($alldata as $data) {
                $object = new $sorm_class();
                $object->setData($data);
                $object->setNew(false);
                $objects[] = $object;
            }
            return $objects;
        }
    }

    /**
     * Shows the query that would be executed in the method fetchAll
     * @return string : sql query
     */
    public function showQuery($with_parameters = false)
    {
        $sql = "SELECT `".$this->settings['table']."`.* ";

        foreach ((array) $this->settings['select'] as $alias => $statement) {
            $sql .= $statement ? "`".$statement."` AS `".$alias."` " : "`".$alias."`";
        }

        $sql .= $this->getQuery();

        if ($with_parameters) {
            foreach ((array) $this->settings['parameter'] as $index => $value) {
                if (is_numeric($index)) {
                    $sql = str_replace("?", \DBManager::get()->quote($value), $sql, 1);
                } else {
                    $sql = str_replace(":".$index, \DBManager::get()->quote($value), $sql);
                }
            }
        }
        return $sql;
    }

    /*******************************************************************************
     *                            protected and private                            *
     *******************************************************************************/

    /**
     * Constructs a query string for a prepared statement without the SELECT part.
     * @return string
     */
    protected function getQuery()
    {
        $sql = "FROM `".$this->settings['table']."` ";
        if ($this->settings['joins']) {
            foreach ($this->settings['joins'] as $alias => $joindata) {
                $table = isset($joindata['table']) ? "`".$joindata['table']."` AS `".$alias."` " : "`".$alias."`";
                $on = isset($joindata['on']) ? " ON (".$joindata['on'].")" : "";
                $sql .= " ".(isset($joindata['join']) ? $joindata['join'] : "INNER JOIN")." ".$table."".$on." ";
            }
        }
        if ($this->settings['where']) {
            $sql .= "WHERE (".implode(") ".$this->combine_where_statements_with." (", $this->settings['where']).") ";
        }
        if ($this->settings['groupby']) {
            $sql .= "GROUP BY ".$this->settings['groupby']." ";
        }
        if ($this->settings['order']) {
            $sql .= "ORDER BY ".$this->settings['order']." ";
        }
        return $sql;
    }

    /**
     * Fetches the primary key of the table and returns it as an array.
     * @return array : primary key as array("column1", "column2")
     */
    protected function getPrimaryKey()
    {
        $statement = \DBManager::get()->prepare("SHOW COLUMNS FROM `".$this->settings['table']."`");
        $statement->execute();
        $pk = [];
        while($rs = $statement->fetch(\PDO::FETCH_ASSOC)) {
            if ($rs['Key'] == 'PRI'){
                $pk[] = "`".$this->settings['table']."`.`".$rs['Field']."`";
            }
        }
        return $pk;
    }


}
