<?php

namespace Database;

class DatabaseErrorMap
{
    public static function resolve(int $code): array
    {
        return match ($code) {

            // 🔐 Authentication
            18456 => ['Database login failed.', 401],
            1045  => ['Database credentials are invalid.', 401],

            // 🧱 Constraints
            2627, 2601, 1062 => ['Duplicate record already exists.', 409],
            547, 1451, 1452 => ['This record is used by another record.', 409],

            // 📐 Data problems
            8152, 1406 => ['Input data too long.', 422],
            245, 1366 => ['Invalid data type.', 422],
            8115, 1690 => ['Numeric value too large.', 422],

            // 🔍 Missing objects
            208, 1146 => ['Table or view not found.', 500],
            207, 1054 => ['Unknown column.', 500],
            2812 => ['Stored procedure not found.', 500],

            // 🔄 Transactions
            1222, 1205 => ['Database deadlock. Try again.', 409],

            // 🔌 Connectivity
            4060, 53, 2002 => ['Database server unavailable.', 503],

            // ⏱ Timeouts
            258, 1205 => ['Database timeout.', 504],

            default => ['Database operation failed.', 500]
        };
    }
}
