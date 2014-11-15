<?php
/**
 * This file contains the skeleton for all of the database objects
 *
 * @package    BZiON\Models
 * @license    https://github.com/allejo/bzion/blob/master/LICENSE.md GNU General Public License Version 3
 */

/**
 * A base database object (e.g. A player or a team)
 * @package    BZiON\Models
 */
abstract class BaseModel implements ModelInterface
{
    /**
     * The Database ID of the object
     * @var int
     */
    protected $id;

    /**
     * The name of the database table used for queries
     * @var string
     */
    protected $table;

    /**
     * False if there isn't any row in the database representing
     * the requested object ID
     * @var boolean
     */
    protected $valid;

    /**
     * The result of the database query to locate the object with a specific ID
     * @var array
     */
    protected $result;

    /**
     * The database variable used for queries
     * @var Database
     */
    protected $db;

    /**
     * Whether the lazy parameters of the model have been loaded
     * @var boolean
     */
    private $loaded = false;

    /**
     * The name of the database table used for queries
     * You can use this constant in static methods as such:
     * static::TABLE
     */
    const TABLE = "";

    /**
     * Construct a new Model
     *
     * This method takes the ID of the object to look for and creates a
     * $this->db object which can be used to communicate with the database,
     * as well as a $this->result array which is the single result of the
     * $this->db->query function. If the $id is specified as 0, then an
     * invalid object will be returned
     *
     * @param int    $id     The ID of the object to look for
     */
    public function __construct($id)
    {
        $this->db = Database::getInstance();

        $this->table = static::TABLE;

        if ($id == 0) {
            $this->valid  = false;
            $this->result = array();

            return;
        }

        $this->id = $id;

        $columns = static::getEagerColumns();
        $results = $this->db->query("SELECT $columns FROM {$this->table} WHERE id = ? LIMIT 1", "i", array($id));

        if (count($results) < 1) {
            $this->valid  = false;
        } else {
            $this->valid  = true;
            $this->assignResult($results[0]);
        }
    }

    abstract protected function assignResult($result);

    /**
     * Update a database field
     *
     * @param string $name  The name of the column
     * @param mixed  $value The value to set the column to
     * @param string $type  The type of the value, can be 's' (string) , 'i' (integer) , 'd' (double) or 'b' (blob)
     *
     * @return void
     */
    public function update($name, $value, $type='i')
    {
        $this->db->query("UPDATE ". static::TABLE . " SET `$name` = ? WHERE id = ?", $type."i", array($value, $this->id));
    }

    /**
     * Delete the object
     *
     * Please note that this does not delete the object entirely from the database,
     * it only hides it from users. You should overload this function if your object
     * does not have a 'status' column which can be set to 'deleted'.
     */
    public function delete()
    {
        $this->status = 'deleted';
        $this->update('status', 'deleted', 's');
    }

    /**
     * Permanently delete the object from the database
     */
    public function wipe()
    {
        $this->db->query("DELETE FROM " . static::TABLE . " WHERE id = ?", "i", array($this->id));
    }

    /**
     * Get an object's database ID
     * @return int The ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * See if an object is valid
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Check if a status of the object is 'deleted'
     * @return bool
     */
    public function isDeleted()
    {
        if (isset($this->status))
            return $this->status == "deleted";
        return false;
    }

    /**
     * Counts the elements of the database that match a specific query
     *
     * @param  string $additional_query The MySQL query string (e.g. `WHERE id = ?`)
     * @param  string $types            The types of values that will be passed to Database::query()
     * @param  array  $params           The parameter values that will be passed to Database::query() corresponding to $types
     * @param  string $table            The database table that will be searched, defaults to the model's table
     * @param  string $column           Only count the entries where `$column` is not `NULL` (or all if `$column` is `*`)
     * @return int
     */
    protected static function fetchCount($additional_query='', $types='', $params=array(), $table='', $column='*')
    {
        $table = (empty($table)) ? static::TABLE : $table;
        $db = Database::getInstance();

        $result = $db->query("SELECT COUNT($column) AS count FROM $table $additional_query",
            $types, $params
        );

        return $result[0]['count'];
    }

    /**
     * Gets the id of a database row which has a specific value on a column
     * @param  string $value  The value which the column should be equal to
     * @param  string $column The name of the database column
     * @param  string $type   The type of the value, can be 's' (string) , 'i' (integer) , 'd' (double) or 'b' (blob)
     * @return int    The ID of the object
     */
    protected static function fetchIdFrom($value, $column, $type="s")
    {
        $results = self::fetchIdsFrom($column, $value, $type, false, "LIMIT 1");

        // Return the id or 0 if nothing was found
        return (isset($results[0])) ? $results[0] : 0;
    }

    /**
     * Gets an array of object IDs from the database
     *
     * @param string          $additional_query Additional query snippet passed to the MySQL query after the SELECT statement (e.g. `WHERE id = ?`)
     * @param string          $types            The types of values that will be passed to Database::query()
     * @param array           $params           The parameter values that will be passed to Database::query() corresponding to $types
     * @param string          $table            The database table that will be searched
     * @param string|string[] $select           The column that will be returned
     *
     * @return mixed[] A list of values, if $select was only one column, or the return array of $db->query if it was more
     */
    protected static function fetchIds($additional_query='', $types='', $params=array(), $table = "", $select='id')
    {
        $table = (empty($table)) ? static::TABLE : $table;
        $db = Database::getInstance();

        // If $select is an array, convert it into a comma-separated list that MySQL will accept
        if (is_array($select))
            $select = implode(",", $select);

        $results = $db->query("SELECT $select FROM $table $additional_query", $types, $params);

        // If $select specifies multiple columns, just return the $results array
        if (isset($results[0]) && count($results[0]) != 1) {
            return $results;
        }

        // Find the correct value if the user specified a table.
        // For example, if $select is "groups.id", we should convert it to
        // "id", because that's how MySQLi stores column names in the $results
        // array.
        $selectArray = explode(".",$select);
        $select = end($selectArray);

        if (!$results)
            return array();

        return array_column($results, $select);
    }

    /**
     * Gets an array of object IDs from the database that have a column equal to something else
     *
     * @param string          $column           The name of the column that should be tested
     * @param array|mixed     $possible_values  List of acceptable values
     * @param bool            $negate           Whether to search if the value of $column does NOT belong to the $possible_values array
     * @param string          $type             The type of the values in $possible_values (can be `s`, `i`, `d` or `b`)
     * @param string|string[] $select           The name of the column(s) that the returned array should contain
     * @param string          $additional_query Additional parameters to be passed to the MySQL query (e.g. `WHERE id = 5`)
     * @param string          $table            The database table which will be used for queries
     *
     * @return mixed[] A list of values, if $select was only one column, or the return array of $db->query if it was more
     */
    protected static function fetchIdsFrom($column, $possible_values, $type, $negate=false, $additional_query="", $table = "", $select='id')
    {
        $question_marks = array();
        $types = "";
        $negation = ($negate) ? "NOT" : "";

        if (!is_array($possible_values)) {
            $possible_values = array($possible_values);
        }

        foreach ($possible_values as $p) {
            $question_marks[] = '?';
            $types .= $type;
        }

        if (empty($possible_values)) {
            if (!$negate) {
                // There isn't any value that $column can have so
                // that it matches the criteria - return nothing.
                return array();
            } else {
                $conditionString = $additional_query;
            }
        } else {
            $conditionString = "WHERE $column $negation IN (" . implode(",", $question_marks) . ") $additional_query";
        }

        return self::fetchIds($conditionString, $types, $possible_values, $table, $select);
    }

    /**
     * Get the MySQL columns that will be loaded as soon as the model is created
     *
     * @return string The columns in a format readable by MySQL
     */
    protected static function getEagerColumns()
    {
        return '*';
    }

    /**
     * Get the MySQL columns that will be loaded only when a corresponding
     * parameter of the model is requested
     *
     * This is done in order to reduce the time needed to load parameters that
     * will not be requested (e.g player activation codes or permissions)
     *
     * @return string The columns in a format readable by MySQL
     */
    protected static function getLazyColumns()
    {
        throw new Exception("You need to specify a Model::getLazyColumns() method");
    }

    /**
     * Load all the parameters of the model that were not loaded during the first
     * fetch from the database
     *
     * @param array $result MySQL's result set
     * @return void
     */
    protected function assignLazyResult($result)
    {
        throw new Exception("You need to specify a Model::lazyLoad() method");
    }

    /**
     * Load all the properties of the model that haven't been loaded yet
     *
     * @return void
     */
    protected function lazyLoad()
    {
        if (!$this->loaded && $this->valid) {
            $this->loaded = true;

            $columns = $this->getLazyColumns();
            $results = $this->db->query("SELECT $columns FROM {$this->table} WHERE id = ? LIMIT 1", "i", array($this->id));

            if (count($results) < 1) {
                throw new Exception("The model has mysteriously disappeared");
            }

            $this->assignLazyResult($results);
        }
    }

    /**
     * Gets an entity from the supplied slug, which can either be an alias or an ID
     * @param  string|int $slug The object's slug
     * @return static
     */
    public static function fetchFromSlug($slug)
    {
        return new static((int) $slug);
    }

    /**
     * Creates a new entry in the database
     *
     * <code>
     * Model::create(array( 'author'=>15, 'content'=>"Lorem ipsum..."  ), 'is');
     * </code>
     *
     * @param  array        $params An associative array, with the keys (columns) pointing to the
     *                              values you want to put on each
     * @param  string       $types  The type of the values in $params (can be `s`, `i`, `d` or `b`)
     * @param  array|string $now    Column(s) to update with the current timestamp
     * @param  string       $table  The table to perform the query on, defaults to the Model's
     *                              table
     * @return static       The new entry
     */
    protected static function create($params, $types, $now=null, $table='')
    {
        $table = (empty($table)) ? static::TABLE : $table;
        $db = Database::getInstance();

        $columns = implode('`,`', array_keys($params));
        $columns = "`$columns`";

        $question_marks = str_repeat('?,', count($params));
        $question_marks = rtrim($question_marks, ','); // Remove last comma

        if ($now) {
            if (!is_array($now)) // Convert $now to an array if it's a string
                $now = array($now);

            foreach ($now as $column) {
                $columns .= ",$column";
                $question_marks .= ",NOW()";
            }
        }

        $query = "INSERT into $table ($columns) VALUES ($question_marks)";
        $db->query($query, $types, array_values($params));

        return new static($db->getInsertId());
    }

    /**
     * Fetch a model's data from the database again
     * @return static The new model
     */
    public function refresh()
    {
        parent::__construct($this->id);

        return $this;
    }

    /**
     * Generate an invalid object
     *
     * <code>
     *     <?php
     *     $object = Team::invalid();
     *
     *     get_class($object); // Team
     *     $object->isValid(); // false
     * </code>
     * @return static
     */
    public static function invalid()
    {
        return new static(0);
    }
}
