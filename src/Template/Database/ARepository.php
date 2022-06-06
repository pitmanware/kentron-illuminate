<?php

namespace Kentron\Template\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

use Kentron\Facade\DT;

abstract class ARepository
{
    /** The FQDN of the model. Should be overriden */
    protected AModel|string $modelClass;

    /** The model */
    protected AModel|Collection|Builder|null $model = null;

    /** The FQDN of the hydratable entity */
    protected AdbEntity|string $dbEntity;

    /** The FQDN of the hydratable entity collection */
    protected ADbCollectionEntity|string $dbEntityCollection;

    /** Any updates that need to be run */
    private array $updates = [];

    /**
     * All of the available clause operators
     *
     * @var string[]
     */
    private array $operators = [
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
    public function __construct()
    {
        if (is_null($this->modelClass) || !is_subclass_of($this->modelClass, AModel::class)) {
            throw new \UnexpectedValueException("Model must be instance of " . AModel::class);
        }

        $this->resetOrmModel();
    }

    /**
     * Entity Methods
     */

    public function newEntity(): ADbEntity
    {
        $entityClass = $this->dbEntity;
        return new $entityClass();
    }

    public function newCollectionEntity(): ADbCollectionEntity
    {
        $entityCollectionClass = $this->dbEntityCollection;
        return new $entityCollectionClass();
    }

    /**
     * Inserts a new entry into the database using an ADbEntity
     *
     * @param ADbEntity $dbEntity
     */
    public function insertOne(ADbEntity $dbEntity): void
    {
        // Loop through the attributes and set based on the key
        foreach ($dbEntity->iterateAvailableProperties(true) as $property => $value) {

            // Don't set null values or the "date created" column
            if (is_null($value) || $property === $dbEntity::COLUMN_CREATED_AT) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            else if ($value instanceof DT) {
                $value = $value->format();
            }
            else if (is_bool($value)) {
                $value = (int) $value;
            }

            $this->set($property, $value);
        }
        $this->save();

        // Save the new values to the entity
        $dbEntity->hydrate($this->toArray());
        $this->entityDef = $dbEntity;
    }

    /**
     * Inserts all DB Entities; for now just loops and runs insertOne
     *
     * @param ADbCollectionEntity $collectionEntity
     *
     * @return void
     */
    public function insertMany(ADbCollectionEntity $collectionEntity): void
    {
        foreach ($collectionEntity->iterateEntities() as $dbEntity) {
            $this->insertOne($dbEntity);
            $this->resetOrmModel();
        }
    }

    /**
     * Gets the first result from the table and inserts it into the given ADbEntity
     *
     * @param ADbEntity|null $dbEntity Hydrate an existing entity, or create a new one if null
     *
     * @return ADbEntity|null $dbEntity The entity to be hydrated from the results or null if no results
     */
    public function hydrateFirst(?ADbEntity $dbEntity = null): ?ADbEntity
    {
        $result = $this->first();

        if (is_null($result)) {
            return null;
        }

        $dbEntity ??= $this->newEntity();
        $dbEntity->hydrate($result);

        return $dbEntity;
    }

    /**
     * Gets all results from the table and inserts them into a given ADbCollectionEntity
     *
     * @param ADbCollectionEntity|null $collectionEntity An existing entity collection to be hydrates, or new if null
     *
     * @return ADbCollectionEntity|null $collectionEntity The collection entity to be hydrate, or null if no results
     */
    public function hydrateAll(?ADbCollectionEntity $collectionEntity = null): ?ADbCollectionEntity
    {
        $this->get();
        $results = $this->toArray();

        if (count($results) === 0) {
            return null;
        }

        $collectionEntity ??= $this->newCollectionEntity();
        $collectionEntity->hydrateCollection($results);

        return $collectionEntity;
    }

    /**
     * SQL methods
     */

    /**
     * Wheres
     */

    /**
     * Where
     *
     * @param string $column
     * @param mixed $value
     * @param string $operator
     *
     * @return void
     */
    public function where(string $column, $value, string $operator = "="): void
    {
        $this->validateOperator($operator);

        $this->model = $this->model->where($column, $operator, $value);
    }

    public function whereRaw(string $queryString, array $bindings = [])
    {
        $this->model = $this->model->whereRaw($queryString, $bindings);
    }

    /**
     * OrWhere
     * @param string $column
     * @param mixed $operator
     * @param string $value
     *
     * @return void
     */
    public function orWhere(string $column, $value, string $operator = "="): void
    {
        $this->validateOperator($operator);

        $this->model->orWhere($column, $operator, $value);
    }

    public function orWhereRaw(string $queryString, array $bindings = [])
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
    public function whereBetween(string $column, array $values, string $modifier = "and", bool $not = false): void
    {
        $this->model = $this->model->whereBetween($column, $values, $modifier, $not);
    }

    /**
     * Where In
     *
     * @param string $column
     * @param array $values
     *
     * @return void
     */
    public function whereIn(string $column, array $values): void
    {
        $this->model = $this->model->whereIn($column, $values);
    }

    /**
     * Where column is null
     *
     * @param string $column
     *
     * @return void
     */
    public function whereNull(string $column): void
    {
        $this->model = $this->model->whereNull($column);
    }

    /**
     * Where column is not null
     *
     * @param string $column
     *
     * @return void
     */
    public function whereNotNull(string $column): void
    {
        $this->model = $this->model->whereNotNull($column);
    }

    /**
     * Where the ID column is
     *
     * @param int $id
     *
     * @return void
     */
    public function whereId(int $id): void
    {
        $this->where($this->dbEntity::COLUMN_ID, $id);
    }

    /**
     * Where the ID column is in
     *
     * @param int[] $ids
     *
     * @return void
     */
    public function whereIdsIn(array $ids): void
    {
        $this->whereIn($this->dbEntity::COLUMN_ID, $ids);
    }

    /**
     * Where the date created column is greater than
     *
     * @param string $from
     *
     * @return void
     */
    public function fromDateCreated(string $from): void
    {
        $this->where($this->dbEntity::COLUMN_CREATED_AT, $from, ">=");
    }

    /**
     * Where the date created column is less than
     *
     * @param string $to
     *
     * @return void
     */
    public function toDateCreated(string $to): void
    {
        $this->where($this->dbEntity::COLUMN_CREATED_AT, $to, "<");
    }

    /**
     * Where the date deleted column is greater than
     *
     * @param string $from
     *
     * @return void
     */
    public function fromDateDeleted(string $from): void
    {
        $this->where($this->dbEntity::COLUMN_DELETED_AT, $from, ">=");
    }

    /**
     * Where the date deleted column is less than
     *
     * @param string $to
     *
     * @return void
     */
    public function toDateDeleted(string $to): void
    {
        $this->where($this->dbEntity::COLUMN_DELETED_AT, $to, "<");
    }

    /**
     * Updates
     */

    /**
     * Update
     *
     * @param array $array
     *
     * @return void
     */
    public function update(array $array): void
    {
        $this->model->update($array);
    }

    /**
     * Adds an update array to the $updates property
     *
     * @param string $column The column to update
     * @param mixed  $value  The new value of that column
     *
     * @return void
     */
    protected function addUpdate(string $column, $value): void
    {
        $this->updates[$column] = $value;
    }

    /**
     * Runs the update method using the $updates property
     *
     * @return void
     */
    public function runUpdate(): void
    {
        $this->update($this->updates);
    }

    public function updateCreatedAt(string $createdAt): void
    {
        $this->where($this->entityDef::COLUMN_CREATED_AT, $createdAt, ">=");
    }

    public function updateDeletedAt(string $deletedAt): void
    {
        $this->where($this->entityDef::COLUMN_DELETED_AT, $deletedAt, ">=");
    }

    /**
     * Misc wrappers
     */

    /**
     * Delete
     *
     * @return mixed|null
     */
    public function delete()
    {
        return $this->model->delete();
    }

    /**
     * Get
     *
     * @param array $columns
     */
    public function get($columns = ["*"]): void
    {
        $this->model = $this->model->get($columns);
    }

    /**
     * To Array
     *
     * @return array
     */
    public function toArray(): array
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
    public function select($columns = ["*"]): void
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
    public function set(string $key, $value): void
    {
        $this->model->{$key} = $value;
    }

    /**
     * Save
     *
     * @return bool
     */
    public function save(): bool
    {
        return $this->model->save();
    }

    /**
     * Get Value
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getValue(string $key)
    {
        return $this->model->{$key};
    }

    /**
     * Count
     *
     * @return int
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * First
     *
     * @return array|null
     */
    public function first() : ?array
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
    public function resetOrmModel(): void
    {
        $this->model = new $this->modelClass;
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
    public function join(string $table, string $left, ?string $right = null, string $operator = "=", string $type = "inner", bool $where = false): void
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
    public function orderBy(string $column, bool $desc = true): void
    {
        $this->model = $this->model->orderBy($column, ["asc", "desc"][$desc]);
    }

    /**
     * Is Empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->model->isEmpty();
    }

    /**
     * Get the prepared SQL
     *
     * @return string
     */
    public function getStatement(): string
    {
        return $this->model->getStatement();
    }

    /**
     * Limit
     *
     * @param int $limit
     *
     * @return void
     */
    public function limit(int $limit): void
    {
        $this->model = $this->model->limit($limit);
    }

    /**
     * Gets with trashed
     *
     * @return void
     */
    public function withTrashed(): void
    {
        $this->model = $this->model->withTrashed();
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
    public function leftJoin(string $table, string $left, ?string $right = null, string $operator = "="): void
    {
        $this->validateOperator($operator);

        $this->model = $this->model->leftJoin($table, $left, $operator, $right);
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
    private function validateOperator(string $operator): void
    {
        if (!in_array($operator, $this->operators)) {
            throw new \InvalidArgumentException("'$operator' is not a valid operator");
        }
    }
}
