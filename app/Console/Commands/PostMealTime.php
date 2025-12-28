<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AttendanceLog;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Exception\RequestException;

class PostMealTime extends Command
{
    protected $signature = 'app:post-meal-time';
    protected $description = 'Mengirim data meal time';

    public function handle()
    {
        $this->info('Mengambil 1000 data attendance_logs terakhir...');

        $logs = AttendanceLog::orderByDesc('id')
            ->limit(1000)
            ->get();

        if ($logs->isEmpty()) {
            $this->warn('Tidak ada data yang dikirim.');
            return Command::SUCCESS;
        }

        $payload = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'nik' => $log->nik,
                'meal_type' => $log->meal_type,
                'status' => $log->status,
                'quantity' => (int) $log->quantity,
                'remarks' => $log->remarks,
                'created_by' => $log->created_by ?? 'system',
                'rating' => (int) $log->rating,
                'attendance_date' => \Carbon\Carbon::parse($log->attendance_date)->format('Y-m-d'),
                'attendance_time' => \Carbon\Carbon::parse($log->attendance_time)->format('Y-m-d H:i:s'),
                // 'similarity_score' => round((float) $log->similarity_score, 2),
                // 'confidence_score' => round((float) $log->confidence_score, 2),
                'similarity_score' => (float) $log->similarity_score,
                'confidence_score' => (float) $log->confidence_score,
                'created_at' => $log->created_at
                    ? $log->created_at->format('Y-m-d H:i:s')
                    : now()->format('Y-m-d H:i:s'),
            ];
        })->values();

        $body = json_encode(['data' => $payload], JSON_UNESCAPED_SLASHES);

        $client = new Client([
            'timeout' => 15,
        ]);

        $request = new GuzzleRequest(
            'POST',
            'http://36.67.119.212:9015/api/attendance/receive',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            $body
        );

        try {
            $response = $client->send($request, ['http_errors' => false]);

            $this->info('Status Code: ' . $response->getStatusCode());
            $this->info('Response: ' . $response->getBody());

        } catch (RequestException $e) {
            $this->error('Request failed');

            if ($e->hasResponse()) {
                $this->error($e->getResponse()->getBody());
            } else {
                $this->error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
