<?php

use Illuminate\Support\Facades\Schedule;

// Shared hosting has no supervisor, so cron drains the queue once a minute
// instead of a long-running worker. Static cache invalidation and mail both
// run there. The lock outlives a run by a little but not by the default day,
// so a crashed run cannot stall the queue.
Schedule::command('queue:work --stop-when-empty --max-time=50')
	->everyMinute()
	->withoutOverlapping(2);
