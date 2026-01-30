<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AttendanceLog;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class PostMealTime extends Command
{
    protected $signature = 'app:post-meal-time';
    protected $description = 'Mengirim data meal time';

    public function handle()
    {
        $this->info('Mengambil 1000 data attendance_logs terakhir...');

        // Ambil hanya yang belum terkirim
        $logs = AttendanceLog::where('sys_post', 0)
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        if ($logs->isEmpty()) {
            $this->warn('Tidak ada data yang dikirim.');
            return Command::SUCCESS;
        }

        $payload = $logs->map(function ($log) {
            return [
                // 'id' => $log->id,
                'nik' => $log->nik,
                'meal_type' => $log->meal_type,
                'status' => $log->status,
                'quantity' => (int) $log->quantity,
                'remarks' => $log->remarks,
                'created_by' => $log->created_by ?? 'system',
                'rating' => (int) $log->rating,
                'attendance_date' => \Carbon\Carbon::parse($log->attendance_date)->format('Y-m-d'),
                'attendance_time' => \Carbon\Carbon::parse($log->attendance_time)->format('Y-m-d H:i:s'),
                'similarity_score' => (float) $log->similarity_score,
                'confidence_score' => (float) $log->confidence_score,
                'created_at' => $log->created_at
                    ? $log->created_at->format('Y-m-d H:i:s')
                    : now()->format('Y-m-d H:i:s'),
            ];
        })->values();

        $body = json_encode(['data' => $payload], JSON_UNESCAPED_SLASHES);

        $client = new Client([
            'timeout' => 30,
        ]);

        $request = new \GuzzleHttp\Psr7\Request(
            'POST',
            'http://124.158.168.194:93/api/attendance/receive',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            $body
        );

        try {
            $response = $client->send($request, ['http_errors' => false]);

            $statusCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();

            Log::info('PostMealTime Response', [
                'status'   => $statusCode,
                'response' => $responseBody,
            ]);

            $this->info('Status Code: ' . $statusCode);
            $this->info('Response: ' . $responseBody);

            // =========================
            // CEK APAKAH SUKSES
            // =========================
            $isSuccess = false;

            if ($statusCode >= 200 && $statusCode < 300) {
                $decoded = json_decode($responseBody, true);

                // jika API punya flag success
                if (is_array($decoded) && isset($decoded['success'])) {
                    $isSuccess = $decoded['success'] === true;
                } else {
                    // fallback: anggap sukses jika HTTP 2xx
                    $isSuccess = true;
                }
            }

            // =========================
            // UPDATE sys_post = 1
            // =========================
            if ($isSuccess) {

                $ids = $logs->pluck('id')->toArray();

                AttendanceLog::whereIn('id', $ids)
                    ->update(['sys_post' => 1]);

                $this->info('Berhasil update sys_post = 1 untuk ' . count($ids) . ' data.');

            } else {
                $this->warn('Pengiriman gagal, sys_post tidak diubah.');
            }

        } catch (\GuzzleHttp\Exception\RequestException $e) {

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
