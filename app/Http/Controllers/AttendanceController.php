<?php

namespace App\Http\Controllers;

use App\Models\MealTimeSetting;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $faceService;

    public function __construct(FaceRecognitionService $faceService)
    {
        $this->faceService = $faceService;
    }

    public function index()
    {
        $currentMealType = MealTimeSetting::getCurrentMealType();
        $attendances = AttendanceLog::with('employee')
            ->whereDate('attendance_date', today())
            ->latest('attendance_time')
            ->get();

        return view('attendance.index', compact('currentMealType', 'attendances'));
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'image'         => 'required|string',
            'quantity'      => 'integer|min:1|max:10',
            'remarks'       => 'nullable|string',
            'rating'       => 'nullable|integer',
            'recognize_only'=> 'boolean',
        ]);


        // Flag: mode auto-recognition saja atau benar-benar absen
        $recognizeOnly = $request->input('recognize_only', false);

        // Cek meal time hanya kalau bukan recognize_only
        $currentMealType = MealTimeSetting::getCurrentMealType();
        if (!$currentMealType && !$recognizeOnly) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dalam waktu makan.',
            ]);
        }

        // Decode base64 image
        $imageData = $request->image;
        Log::info('Raw image: ' . substr($imageData,0,100));
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $imageData = base64_decode($imageData);
        }

        // Simpan file sementara
        // $tempFile = tempnam(sys_get_temp_dir(), 'face_') . '.jpg';
        // file_put_contents($tempFile, $imageData);
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'face_' . uniqid() . '.jpg';
        file_put_contents($tempFile, $imageData);

        try {
            // Panggil Python API untuk face recognition
            $result = $this->faceService->recognizeFace($tempFile);

            // Hapus temp file
            @unlink($tempFile);

            // Log::info('Face API result', $result);

            if (!($result['success'] ?? false)) {
                return response()->json([
                    'success'               => false,
                    'message'               => $result['message'] ?? 'Wajah tidak dikenali',
                    'is_real_face'          => $result['is_real_face'] ?? null,
                    'anti_spoofing_score'   => $result['anti_spoofing_score'] ?? null,
                    'confidence'            => $result['confidence'] ?? null,
                    'similarity'            => $result['similarity'] ?? 0.0,
                ]);
            }

            // Ambil NIK dari hasil Python
            $nik = $result['nik'] ?? $result['employee_id'] ?? null;

            if (!$nik) {
                return response()->json([
                    'success' => false,
                    'message' => 'NIK tidak ditemukan dari sistem face recognition',
                ], 422);
            }

            // Ambil nama karyawan dari database Laravel
            $employee = Employee::where('nik', $nik)->first();
            $employeeName = $employee ? $employee->name : null;

            // MODE RECOGNIZE ONLY: hanya kembalikan data tanpa simpan absensi
            if ($recognizeOnly) {
                return response()->json([
                    'success'               => true,
                    'nik'                   => $nik,
                    'employee_id'           => $nik,
                    'employee_name'         => $employeeName,
                    'similarity'            => $result['similarity'] ?? null,
                    'confidence'            => $result['confidence'] ?? null,
                    'is_real_face'          => $result['is_real_face'] ?? null,
                    'anti_spoofing_score'   => $result['anti_spoofing_score'] ?? null,
                    'message'               => 'Wajah berhasil dikenali',
                ]);
            }

            // MODE CHECK-IN: simpan ke database

            // Cek sudah absen atau belum hari ini untuk meal type ini
            $existingAttendance = AttendanceLog::where('nik', $nik)
                ->where('meal_type', $currentMealType)
                ->whereDate('attendance_date', today())
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'success'       => false,
                    'message'       => "Anda sudah absen {$currentMealType} hari ini.",
                    'nik'           => $nik,
                    'employee_name' => $employeeName,
                ]);
            }

            // Simpan absensi baru
            $attendance = AttendanceLog::create([
                'nik'              => $nik,
                'meal_type'        => $currentMealType,
                'status'           => 'present',
                'created_by'       => 'system',
                'quantity'         => $request->quantity ?? 1,
                'rating'           => $request->rating ?? null,
                'sys_post'         => 0,
                'remarks'          => $request->remarks ?? null,
                'attendance_date'  => today(),
                'attendance_time'  => now(),
                'similarity_score' => $result['similarity'] ?? null,
                'confidence_score' => $result['confidence'] ?? null,
                'is_real_face'     => $result['is_real_face'] ?? null,
            ]);

            // Save photo evidence
            if ($imageData) {
                $photoFileName = $nik . '_' . $currentMealType . '_' . now()->format('YmdHis') . '.jpg';
                $photoPath = 'attendance_photos/' . today()->format('Y-m-d') . '/' . $photoFileName;
                
                // Create directory if not exists
                $fullPath = storage_path('app/public/' . $photoPath);
                if (!file_exists(dirname($fullPath))) {
                    mkdir(dirname($fullPath), 0755, true);
                }
                
                // Save photo
                file_put_contents($fullPath, $imageData);
                
                // Update attendance record with photo path
                $attendance->photo_path = $photoPath;
                $attendance->save();
            }

            return response()->json([
                'success'               => true,
                'message'               => "Absensi {$currentMealType} berhasil. Terimakasih!",
                'nik'                   => $nik,
                'employee_name'         => $employeeName,
                'meal_type'             => $currentMealType,
                'quantity'              => $attendance->quantity,
                'attendance_id'         => $attendance->id,
                'is_real_face'          => $result['is_real_face'] ?? null,
                'anti_spoofing_score'   => $result['anti_spoofing_score'] ?? null,
            ]);

        } catch (\Exception $e) {
            @unlink($tempFile);

            Log::error('Attendance check-in error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
