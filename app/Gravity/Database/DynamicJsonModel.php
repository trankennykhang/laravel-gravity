<?php

namespace App\Gravity\Database;

class DynamicJsonModel extends JsonModel
{
    /**
     * Map of dynamically generated classes to their configured JSON table names.
     */
    protected static $dynamicTables = [];

    /**
     * Map of dynamically generated classes to their fillable fields.
     */
    protected static $dynamicFillables = [];

    /**
     * Register dynamic configuration for a dynamic model class.
     */
    public static function register(string $className, string $table, array $fillable)
    {
        $className = ltrim($className, '\\');
        self::$dynamicTables[$className] = $table;
        self::$dynamicFillables[$className] = $fillable;
    }

    /**
     * Get the dynamic table name associated with the subclass.
     */
    public static function getTable()
    {
        $className = ltrim(static::class, '\\');
        return self::$dynamicTables[$className] ?? parent::getTable();
    }

    /**
     * Get the dynamic fillable fields associated with the subclass.
     */
    public function getFillable(): array
    {
        $className = ltrim(static::class, '\\');
        return self::$dynamicFillables[$className] ?? parent::getFillable();
    }
}
