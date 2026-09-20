<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\EnrollmentController;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BenchmarkEnrollments extends Command
{
    protected $signature = 'benchmark:enrollments';

    protected $description = 'Benchmark Enrollment API against the large dataset';

    public function handle(EnrollmentController $controller): int
    {
        $scenarios = [
            'page=1' => [
                'page' => 1,
                'page_size' => 25,
            ],

            'page=100' => [
                'page' => 100,
                'page_size' => 25,
            ],

            'page=1000' => [
                'page' => 1000,
                'page_size' => 25,
            ],

            'page=10000' => [
                'page' => 10000,
                'page_size' => 25,
            ],

            'search=2026000019' => [
                'search' => '2026000019',
                'page' => 1,
                'page_size' => 25,
            ],

            'search=IF0001' => [
                'search' => 'IF0001',
                'page' => 1,
                'page_size' => 25,
            ],

            'search=Ahmad + APPROVED + GANJIL' => [
                'search' => 'Ahmad',
                'status' => 'DRAFT',
                'semester' => 'GANJIL',
                'page' => 1,
                'page_size' => 25,
            ],
        ];

        $this->info('Enrollment API Benchmark');
        $this->info('========================');
        $this->newLine();

        $this->table(
            ['Scenario', 'HTTP', 'Rows', 'Total', 'Time (ms)'],
            collect($scenarios)->map(function (array $params, string $name) use ($controller) {
                try {
                    $request = Request::create(
                        '/api/enrollments',
                        'GET',
                        $params
                    );

                    $start = hrtime(true);

                    $response = $controller->index($request);

                    $elapsedMs = (hrtime(true) - $start) / 1_000_000;

                    $status = $response->getStatusCode();

                    $payload = json_decode(
                        $response->getContent(),
                        true
                    );

                    return [
                        $name,
                        $status,
                        count($payload['data'] ?? []),
                        $payload['meta']['total'] ?? '-',
                        number_format($elapsedMs, 3),
                    ];
                } catch (Throwable $e) {
                    return [
                        $name,
                        'ERROR',
                        '-',
                        '-',
                        $e->getMessage(),
                    ];
                }
            })->values()->all()
        );

        $this->newLine();
        $this->info('Benchmark completed.');

        return self::SUCCESS;
    }
}