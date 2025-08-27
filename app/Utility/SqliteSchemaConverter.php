<?php

namespace App\Utility;

class SqliteSchemaConverter
{
    /**
     * Convert MySQL schema to SQLite compatible schema
     */
    public static function convertMysqlToSqlite($mysqlSql)
    {
        // Remove MySQL-specific statements
        $sql = preg_replace('/SET SQL_MODE.*?;/i', '', $mysqlSql);
        $sql = preg_replace('/SET time_zone.*?;/i', '', $sql);
        $sql = preg_replace('/ALTER DATABASE.*?;/i', '', $sql);
        $sql = preg_replace('/\/\*!.*?\*\/;?/s', '', $sql);
        
        // Remove MySQL comments
        $sql = preg_replace('/^--.*$/m', '', $sql);
        
        // Convert AUTO_INCREMENT to AUTOINCREMENT
        $sql = preg_replace('/AUTO_INCREMENT/i', 'AUTOINCREMENT', $sql);
        
        // Convert ENGINE and CHARSET specifications
        $sql = preg_replace('/ENGINE=\w+\s*/i', '', $sql);
        $sql = preg_replace('/DEFAULT CHARSET=\w+\s*/i', '', $sql);
        $sql = preg_replace('/DEFAULT COLLATE \w+\s*/i', '', $sql);
        $sql = preg_replace('/COLLATE \w+\s*/i', '', $sql);
        $sql = preg_replace('/CHARACTER SET \w+\s*/i', '', $sql);
        
        // Convert MySQL data types to SQLite equivalents
        $sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/tinyint\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/smallint\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/mediumint\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/bigint\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/varchar\(\d+\)/i', 'TEXT', $sql);
        $sql = preg_replace('/char\(\d+\)/i', 'TEXT', $sql);
        $sql = preg_replace('/text/i', 'TEXT', $sql);
        $sql = preg_replace('/longtext/i', 'TEXT', $sql);
        $sql = preg_replace('/mediumtext/i', 'TEXT', $sql);
        $sql = preg_replace('/decimal\(\d+,\d+\)/i', 'REAL', $sql);
        $sql = preg_replace('/float\(\d+,\d+\)/i', 'REAL', $sql);
        $sql = preg_replace('/double\(\d+,\d+\)/i', 'REAL', $sql);
        $sql = preg_replace('/datetime/i', 'TEXT', $sql);
        $sql = preg_replace('/timestamp/i', 'TEXT', $sql);
        $sql = preg_replace('/date/i', 'TEXT', $sql);
        $sql = preg_replace('/time/i', 'TEXT', $sql);
        
        // Handle UNIQUE KEY constraints
        $sql = preg_replace('/UNIQUE KEY `([^`]+)` \(`([^`]+)`\)/i', 'UNIQUE($2)', $sql);
        
        // Remove extra commas before closing parentheses
        $sql = preg_replace('/,\s*\)/', ')', $sql);
        
        // Clean up multiple whitespace
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = preg_replace('/\s*;\s*/', ";\n", $sql);
        
        return trim($sql);
    }
    
    /**
     * Convert TRUNCATE statements to DELETE for SQLite
     */
    public static function convertTruncateToDelete($sql)
    {
        // Replace TRUNCATE TABLE statements with DELETE FROM
        $sql = preg_replace('/TRUNCATE TABLE\s+([^;]+);?\s*ALTER TABLE\s+\1\s+AUTO_INCREMENT\s*=\s*1;?/i', 
                           'DELETE FROM $1;', $sql);
        $sql = preg_replace('/TRUNCATE TABLE\s+([^;]+);?/i', 'DELETE FROM $1;', $sql);
        
        return $sql;
    }
    
    /**
     * Get SQLite compatible schema from MySQL install.sql
     */
    public static function getSqliteSchema()
    {
        $mysqlSchema = file_get_contents(base_path('database/schemas/install.sql'));
        if ($mysqlSchema === false) {
            throw new \Exception('Could not read MySQL schema file');
        }
        
        $sqliteSchema = self::convertMysqlToSqlite($mysqlSchema);
        
        // Add some SQLite-specific optimizations
        $sqliteSchema = "PRAGMA foreign_keys = ON;\n" . $sqliteSchema;
        
        return $sqliteSchema;
    }
}