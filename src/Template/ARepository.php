<?php

namespace Kentron\Template;

use Kentron\Template\AModel;

use Kentron\Entity\Template\{ADBEntity, ACoreCollectionEntity};

abstract class ARepository
{
    /**
     * The class name of the model
     * Should be overriden
     *
     * @var string
     */
    protected $modelClass;

    /**
     * The model
     *
     * @var AModel
     */
    private $model;

    /**
     * Any updates that need to be run
     *
     * @var array
     */
    private $updates = [];

    /**
     * All of the available clause operators
     *
     * @var string[]
     */
    private $operators = [
        "=", "<", ">", "<=", ">=", "<>", "!=",
        "like", "like binary", "not like", "between", "ilike",
        "&", "|", "^", "<<", ">>",
        "rlike", "regexp", "regexp binary", "not regexp",
        "~", "~*", "!~", "!~*", "similar to",
        "not similar to"
    ];

    /**
     * AbstractRepository constructor
     *
     * @throws \UnexpectedValueException If model is empty or not a child of AModel
     */
    public function __construct ()
    {
        if (is_null($this->modelClass) || !is_subclass_of($this->modelClass, AModel::class)) {
            throw new \UnexpectedValueException("Model must be instance of " . AModel::class);
        }

        $this->resetOrmModel();
    }

    /**
     * Entity methods
     */

    /**
     * Runs the update method using the $updates property
    * @return void
    */
    public function runUpdate (): void
    {
        $this->update($this->updates);
    }

    /**
     * Inserts a new entry into the database using an ADBEntity
    *
    * @param ADBEntity $entity
    */
    public function insertOne (ADBEntity $entity): void
    {
        // Loop through the attributes and set based on the key
        foreach ($entity->iterateAvailableProperties(true) as $property => $value) {
            if ($property === $entity->getDateCreatedColumn()) {
                continue;
            }

            $this->set($property, $value);
        }
        $this->save();

        // Save the new values to the entity
        $entity->build($this->toArray());
    }

    /**
     * Inserts all entities in a collection into the database
    *
    * @param ACoreCollectionEntity $collectionEntity
    */
    public function insertMany (ACoreCollectionEntity $collectionEntity): void
    {
        foreach ($collectionEntity->iterateCoreEntities() as $aDBEntity) {
            $this->insertOne($aDBEntity);
        }
    }

    /**
     * Gets the first result from the table and inserts it into the given ADBEntity
    *
    * @param ADBEntity $dbEntity The entity to be built from the results
    *
    * @return bool The success of the build
    */
    public function buildFirst (ADBEntity $dbEntity): bool
    {
        $result = $this->first();

        if (is_null($result)) {
            return false;
        }

        $dbEntity->build($result);
        return true;
    }

    /**
     * Gets all results from the table and inserts them into a given ACoreCollectionEntity
    *
    * @param ACoreCollectionEntity $collectionEntity The collection entity to be build
    *
    * @return bool The success of the build
    */
    public function buildAll (ACoreCollectionEntity $collectionEntity): bool
    {
        $this->get();
        $results = $this->toArray();

        if (count($results) === 0) {
            return false;
        }

        $collectionEntity->buildCollection($results);
        return true;
    }

    /**
     * Adds an update array to the $updates property
     *
     * @param string $column The column to update
     * @param mixed  $value  The new value of that column
     *
     * @return void
     */
    protected function addUpdate (string $column, $value): void
    {
        $this->updates[$column] = $value;
    }

    /**
     * Table methods
     */

    /**
     * Delete
     *
     * @return mixed|null
     */
    public function delete ()
    {
        return $this->model->delete();
    }

    /**
     * Update
     *
     * @param array $array
     *
     * @return void
     */
    public function update (array $array): void
    {
        $this->model->update($array);
    }

    /**
     * Get
     *
     * @param array $columns
     */
    public function get ($columns = ["*"]): void
    {
        $this->model = $this->model->get($columns);
    }

    /**
     * To Array
     *
     * @return array
     */
    public function toArray (): array
    {
        return $this->model->toArray() ?? [];
    }

    /**
     * Select
     *
     * @param array $columns
     *
     * @return void
     */
    public function select ($columns = ["*"]): void
    {
        $this->model = $this->model->select($columns);
    }

    /**
     * Set
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function set (string $key, $value): void
    {
        $this->model->{$key} = $value;
    }

    /**
     * Save
     *
     * @return bool
     */
    public function save (): bool
    {
        return $this->model->save();
    }

    /**
     * Where
     *
     * @param string $column
     * @param mixed $value
     * @param string $operator
     *
     * @return void
     */
    public function where (string $column, $value, string $operator = "="): void
    {
        $this->validateOperator($operator);

        $this->model = $this->model->where($column, $operator, $value);
    }

    public function whereRaw($queryString, array $bindings = [])
    {
        $this->model = $this->model->whereRaw($queryString, $bindings);
    }

    public function orWhereRaw($queryString, array $bindings = [])
    {
        $this->model = $this->model->orWhereRaw($queryString, $bindings);
    }

    /**
     * Where Between
     *
     * @param string $column
     * @param array $values
     * @param string $modifier
     * @param bool $not
     *
     * @return void
     */
    public function whereBetween (string $column, array $values, string $modifier = "and", bool $not = false): void
    {
        $this->model = $this->model->whereBetween($column, $values, $modifier, $not);
    }

    /**
     * Get Value
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getValue (string $key)
    {
        return $this->model->{$key};
    }

    /**
     * Count
     *
     * @return int
     */
    public function count (): int
    {
        return $this->model->count();
    }

    /**
     * First
     *
     * @return array|null
     */
    public function first () : ?array
    {
        $this->model = $this->model->first();

        if (!$this->model) {
            return null;
        }

        return $this->model->toArray();
    }

    /**
     * Reset Orm Model
     *
     * @return void
     */
    public function resetOrmModel (): void
    {
        $this->model = new $this->modelClass;
    }

    /**
     * OrWhere
     * @param string $column
     * @param mixed $operator
     * @param string $value
     *
     * @return void
     */
    public function orWhere (string $column, $value, string $operator = "="): void
    {
        $this->validateOperator($operator);

        $this->model->orWhere($column, $operator, $value);
    }

    /**
     * Joins table
     *
     * @param string      $table    Table to join
     * @param string      $left     Column on table
     * @param string|null $right    Foreign key column
     * @param string      $operator
     * @param string      $type     Join type
     * @param bool        $where    Join where clause
     *
     * @return void
     */
    public function join (string $table, string $left, ?string $right = null, string $operator = "=", string $type = "inner", bool $where = false): void
    {
        $this->validateOperator($operator);

        $this->model = $this->model->join($table, $left, $operator, $right, $type, $where);
    }

    /**
     * Order a column, default descending
     *
     * @param string $column
     * @param boolean $desc
     *
     * @return void
     */
    public function orderBy (string $column, bool $desc = true): void
    {
        $this->model = $this->model->orderBy($column, ["asc", "desc"][$desc]);
    }

    /**
     * Is Empty
     *
     * @return bool
     */
    public function isEmpty (): bool
    {
        return $this->model->isEmpty();
    }

    /**
     * Get the prepared SQL
     *
     * @return string
     */
    public function getStatement (): string
    {
        return $this->model->getStatement();
    }

    /**
     * Where In
     *
     * @param string $column
     * @param array $values
     *
     * @return void
     */
    public function whereIn (string $column, array $values): void
    {
        $this->model = $this->model->whereIn($column, $values);
    }

    /**
     * Limit
     *
     * @param int $limit
     *
     * @return void
     */
    public function limit (int $limit): void
    {
        $this->model = $this->model->limit($limit);
    }

    /**
     * Gets with trashed
     *
     * @return void
     */
    public function withTrashed (): void
    {
        $this->model = $this->model->withTrashed();
    }

    /**
     * Where column is null
     *
     * @param string $column
     *
     * @return void
     */
    public function whereNull (string $column): void
    {
        $this->model = $this->model->whereNull($column);
    }

    /**
     * Left join table
     *
     * @param string $table
     * @param string $left
     * @param string|null $right
     * @param string $operator
     *
     * @return void
     */
    public function leftJoin (string $table, string $left, ?string $right = null, string $operator = "="): void
    {
        $this->validateOperator($operator);

        $this->model = $this->model->leftJoin($table, $left, $operator, $right);
    }

    /**
     * Where column is not null
     *
     * @param string $column
     *
     * @return void
     */
    public function whereNotNull (string $column): void
    {
        $this->model = $this->model->whereNotNull($column);
    }

    /**
     * Private methods
     */

    /**
     * Validates a given operator
     *
     * @param string $operator
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function validateOperator (string $operator): void
    {
        if (!in_array($operator, $this->operators)) {
            throw new \InvalidArgumentException("'$operator' is not a valid operator");
        }
    }
}
