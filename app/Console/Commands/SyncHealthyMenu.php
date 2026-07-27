<?php

namespace App\Console\Commands;

use App\Models\HealthyMenu;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncHealthyMenu extends Command
{
    protected $signature = 'app:sync-healthy-menu';

    protected $description = 'Sync Healthy Menu dari Server B';

    public function handle()
    {
        $client = new Client([
            'timeout' => 30
        ]);

        try {

            $response = $client->get(
                'http://124.158.168.194:93/api/healthy-menu'
            );

            $result = json_decode($response->getBody(), true);

            if (!($result['success'] ?? false)) {
                $this->error('API gagal.');
                return Command::FAILURE;
            }

            DB::transaction(function () use ($result) {

                // Hapus semua data lama
                DB::table('healthy_menu')->delete();

                $insertData = [];

                foreach ($result['data'] as $row) {

                    $insertData[] = [
                        'nik'         => $row['nik'],
                        'name'        => $row['name'],
                        'additional'  => $row['additional'],
                        'created_at'  => $row['created_at'],
                        'updated_at'  => $row['updated_at'],
                        'updated_by'  => $row['updated_by'],
                        'deleted_by'  => $row['deleted_by'],
                    ];
                }

                if (!empty($insertData)) {
                    HealthyMenu::insert($insertData);
                }

            });

            $this->info('Sync Healthy Menu berhasil.');

        } catch (\Exception $e) {

            Log::error($e->getMessage());

            $this->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
