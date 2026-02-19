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

        $logs = AttendanceLog::where('sys_post', 0)
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        if ($logs->isEmpty()) {
            $this->warn('Tidak ada data yang dikirim.');
            Log::info('PostMealTime: Tidak ada data dengan sys_post = 0');
            return Command::SUCCESS;
        }

        $this->info('Total ditemukan: ' . $logs->count());

        $payload = [];
        $invalidIds = [];

        foreach ($logs as $log) {

            // ===== VALIDASI DATA =====
            if (empty($log->nik)) {
                $invalidIds[] = $log->id;
                Log::warning("ID {$log->id} gagal: NIK kosong");
                continue;
            }

            if (empty($log->attendance_time)) {
                $invalidIds[] = $log->id;
                Log::warning("ID {$log->id} gagal: attendance_time kosong");
                continue;
            }

            $payload[] = [
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
                'similarity_score' => (float) $log->similarity_score,
                'confidence_score' => (float) $log->confidence_score,
                'created_at' => $log->created_at
                    ? $log->created_at->format('Y-m-d H:i:s')
                    : now()->format('Y-m-d H:i:s'),

                // SAFE CAST
                'food_category' => 1,
                'position' => 'Mess SIMS',
                'is_real_face' => is_null($log->is_real_face)
                    ? null
                    : (int) $log->is_real_face,
                'photo_path' => !empty($log->photo_path)
                    ? trim((string) $log->photo_path)
                    : null,
            ];
        }

        if (empty($payload)) {
            $this->warn('Semua data invalid, tidak ada yang dikirim.');
            Log::warning('PostMealTime: Semua data invalid', [
                'invalid_ids' => $invalidIds
            ]);
            return Command::SUCCESS;
        }

        $body = json_encode(['data' => $payload], JSON_UNESCAPED_SLASHES);

        Log::info('PostMealTime: Payload dikirim', [
            'ids' => collect($payload)->pluck('id')->toArray()
        ]);

        $client = new Client(['timeout' => 30]);

        try {

            $response = $client->post(
                'http://124.158.168.194:93/api/attendance/receive',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $body,
                    'http_errors' => false
                ]
            );

            $statusCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();

            Log::info('PostMealTime Response', [
                'status' => $statusCode,
                'response' => $responseBody,
            ]);

            $this->info("Status Code: {$statusCode}");

            $decoded = json_decode($responseBody, true);

            // ===== STRICT SUCCESS CHECK =====
            $isSuccess = false;

            if ($statusCode >= 200 && $statusCode < 300 && is_array($decoded)) {

                $inserted = $decoded['inserted'] ?? 0;
                $skipped  = $decoded['skipped'] ?? 0;

                Log::info('API Result', [
                    'inserted' => $inserted,
                    'skipped'  => $skipped,
                ]);

                if ($inserted > 0) {
                    $isSuccess = true;
                } else {
                    Log::warning('Semua data di-skip oleh API', [
                        'payload_ids' => collect($payload)->pluck('id')->toArray(),
                        'response' => $decoded
                    ]);
                }
            } else {
                Log::error('HTTP bukan 2xx', [
                    'status' => $statusCode,
                    'response' => $responseBody
                ]);
            }

            if ($isSuccess) {

                $ids = collect($payload)->pluck('id')->toArray();

                AttendanceLog::whereIn('id', $ids)
                    ->update(['sys_post' => 1]);

                $this->info('Berhasil update sys_post untuk ' . count($ids) . ' data.');

            } else {

                $this->error('Pengiriman gagal. sys_post TIDAK diubah.');

                Log::error('PostMealTime: GAGAL update sys_post', [
                    'payload_ids' => collect($payload)->pluck('id')->toArray()
                ]);
            }

        } catch (\Exception $e) {

            $this->error('Exception saat request: ' . $e->getMessage());

            Log::error('PostMealTime Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return Command::SUCCESS;
    }

}
