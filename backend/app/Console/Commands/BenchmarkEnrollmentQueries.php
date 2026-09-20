<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\EnrollmentController;
use Illuminate\Http\Request;
use Throwable;

class BenchmarkEnrollmentQueries extends Command
{
    protected $signature = 'benchmark:enrollment-queries';

    protected $description = 'Benchmark enrollment API query and pagination components';

    public function handle(): int
    {
        DB::disableQueryLog();

        $controller = app(EnrollmentController::class);

        $scenarios = [
            'page=1' => [
                'page' => 1,
                'page_size' => 25,
            ],

            'search=NIM' => [
                'search' => '2026000019',
                'page' => 1,
                'page_size' => 25,
            ],

            'search=course' => [
                'search' => 'IF0001',
                'page' => 1,
                'page_size' => 25,
            ],

            'search+filter' => [
                'search' => 'Ahmad',
                'status' => 'APPROVED',
                'semester' => 'GANJIL',
                'page' => 1,
                'page_size' => 25,
            ],
        ];

        foreach ($scenarios as $name => $params) {
            $this->newLine();
            $this->info("=== {$name} ===");

            DB::flushQueryLog();
            DB::enableQueryLog();

            $start = hrtime(true);

            try {
                $request = Request::create(
                    '/api/enrollments',
                    'GET',
                    $params
                );

                $response = $controller->index($request);

                $elapsed = (hrtime(true) - $start) / 1_000_000;

                $queries = DB::getQueryLog();

                $this->line(
                    'Total Laravel time: ' .
                    number_format($elapsed, 3) .
                    ' ms'
                );

                $this->line(
                    'Query count: ' .
                    count($queries)
                );

                foreach ($queries as $i => $query) {
                    $this->line('');
                    $this->line('Query #' . ($i + 1));
                    $this->line(
                        'Time: ' .
                        number_format($query['time'], 3) .
                        ' ms'
                    );
                    $this->line(
                        $query['query']
                    );

                    if (!empty($query['bindings'])) {
                        $this->line(
                            'Bindings: ' .
                            json_encode($query['bindings'])
                        );
                    }
                }

                $this->line('');
                $this->line(
                    'HTTP: ' .
                    $response->getStatusCode()
                );

            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }

            DB::disableQueryLog();
        }

        $this->newLine();
        $this->info('Benchmark completed.');

        return self::SUCCESS;
    }
}