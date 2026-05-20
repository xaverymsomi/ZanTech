<?php

namespace Services;

use Database\DB;

class Queue
{
    /**
     * Pushes a new job payload into the background worker queue.
     *
     * @param string $jobType The job handler type (e.g. 'sms', 'email')
     * @param array $payload The job data array (automatically JSON encoded)
     * @return int|string The inserted job queue ID
     */
    public static function push(string $jobType, array $payload): int|string
    {
        $db = DB::connection();
        
        return $db->save('mx_job_queue', [
            'job_type'   => $jobType,
            'payload'    => json_encode($payload),
            'status'     => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
