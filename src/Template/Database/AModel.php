<?php
declare(strict_types=1);

namespace Kentron\Template\Database;

use Illuminate\Database\Eloquent\Model;

use Kentron\Template\Database\ADbEntity;

use \Error;
use \ReflectionClass;
use \ReflectionClassConstant;

abstract class AModel extends Model
{
    /**
     * Prefix given to all constants for columns
     * @var string
     */
    private const COLUMN_CONSTANT_PREFIX = "C_";

    /**
     * Name of the table, must be overridden
     * @var string
     */
    public const TABLE = "";

    /**
     * Use timestamps
     * @var bool
     */
    public const TIMESTAMPS = true;

    /**
     * Use a custom date format for the timestamps
     * @var string|null
     */
    public const DATE_FORMAT = null;

    /**
     * Name of the table primary column
     * @var string
     */
    public const C_ID = "id";

    /**
     * Name of the table's created datetime column
     * @var string
     */
    public const C_CREATED_AT = "created_at";

    /**
     * Name of the table's updated datetime column
     * @var string|null
     */
    public const C_UPDATED_AT = null;

    /**
     * Name of the table's deleted datetime column
     * @var string|null
     */
    public const C_DELETED_AT = "deleted_at";

    /** Should be overridden with the child DB entity class */
    protected static ADbEntity|string $entityClass = "";

    /** Should be overridden with the child DB entity collection class */
    protected static ADbCollectionEntity|string $dbCollectionEntityClass = "";

    public function __construct()
    {
        $this->table = static::TABLE;
        $this->primaryKey = static::C_ID;
        $this->timestamps = static::TIMESTAMPS;
        $this->dateFormat = static::DATE_FORMAT;
    }

    /**
     * Instantiate a new instance of the DB Entity
     *
     * @return ADbEntity
     */
    public static function newEntity(): ADbEntity
    {
        $class = static::$entityClass;

        if (empty($class)) {
            throw new Error("Entity class not specified in static Model");
        }

        return new $class();
    }

    /**
     * Instantiate a new instance of the DB Collection Entity
     *
     * @return ADbCollectionEntity
     */
    public static function newCollectionEntity(): ADbCollectionEntity
    {
        $class = static::$dbCollectionEntityClass;

        if (empty($class)) {
            throw new Error("Entity collection class not specified in static Model");
        }

        return new $class();
    }

    /**
     * Gets the defined columns on the model
     *
     * @return array
     */
    final public static function getColumns(): array
    {
        // Get all constants from the static class
        $constants = (new ReflectionClass(static::class))->getConstants(ReflectionClassConstant::IS_PUBLIC);
        // Filter them by those only that start with "C_" because that's the prefix we use for column name constants
        $constants = array_filter($constants, fn($constant) => str_starts_with($constant, self::COLUMN_CONSTANT_PREFIX), ARRAY_FILTER_USE_KEY);

        return array_values($constants);
    }

    // Overrides

    final public function getCreatedAtColumn(): ?string
    {
        return static::C_CREATED_AT;
    }

    final public function getUpdatedAtColumn(): ?string
    {
        return static::C_UPDATED_AT;
    }

    final public function getDeletedAtColumn(): ?string
    {
        return static::C_DELETED_AT;
    }
}
