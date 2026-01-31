<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MealTimeSetting;
use App\Models\AttendanceLog;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    protected $faceService;

    public function __construct(FaceRecognitionService $faceService)
    {
        $this->faceService = $faceService;
    }
    public function dashboard()
    {
        $totalEmployees = Employee::where('is_active', true)->count();
        $registeredFaces = Employee::whereHas('faceEmbedding')->count();
        $todayAttendance = AttendanceLog::whereDate('attendance_date', today())->count();

        return view('admin.dashboard', compact('totalEmployees', 'registeredFaces', 'todayAttendance'));
    }

    public function employees(Request $request)
    {
        $search = $request->search;

        $employees = Employee::with('faceEmbedding')
            // ->whereNull('photo_path')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nik', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.employees.index', compact('employees'));
    }

    public function createEmployee()
    {
        return view('admin.employees.create');
    }

    public function storeEmployee(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required|string|max:50|unique:employees,nik',
            'name' => 'required|string|max:100',
        ]);

        Employee::create($validated);

        return redirect()->route('admin.employees')->with('success', 'Employee created successfully!');
    }

    public function editEmployee(Employee $employee)
    {
        return view('admin.employees.edit', compact('employee'));
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'nik' => 'required|string|max:50|unique:employees,nik,' . $employee->id,
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        $employee->update($validated);

        return redirect()->route('admin.employees')->with('success', 'Employee updated successfully!');
    }

    public function deleteEmployee(Employee $employee)
    {
        // Delete face data from Python API if exists
        if ($employee->hasFaceRegistered()) {
            $this->faceService->deleteFace($employee->nik);
        }

        $employee->delete();
        return redirect()->route('admin.employees')->with('success', 'Employee deleted successfully!');
    }

    public function registerFace(Request $request, Employee $employee)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $photo = $request->file('photo');

        if (!$photo || !$photo->isValid()) {
            Log::info('Upload photo gagal', [
                'error_code' => $photo?->getError(),
                'error_msg'  => $photo?->getErrorMessage(),
                'size'       => $photo?->getSize(),
                'mime'       => $photo?->getMimeType(),
            ]);

        }
        $tempPath = $photo->getRealPath();

        // Call Python API to register face
        try {
            // ===============================
            // Call Python Face API
            // ===============================
            $result = $this->faceService->registerFace($employee->nik, $tempPath);

            if (!is_array($result)) {
                throw new \Exception('Response faceService bukan array');
            }

            if (!($result['success'] ?? false)) {
                throw new \Exception($result['error'] ?? 'Register face gagal di Python API');
            }

            // ===============================
            // Simpan foto ke storage
            // ===============================
            $photoPath = $photo->store('faces', 'public');

            if (!$photoPath) {
                throw new \Exception('Gagal menyimpan foto ke storage');
            }

            // ===============================
            // Update employee
            // ===============================
            $employee->update([
                'photo_path' => $photoPath
            ]);

            // ===============================
            // Simpan embedding
            // ===============================
            $employee->faceEmbedding()->updateOrCreate(
                ['nik' => $employee->nik],
                [
                    'embedding_path'  => $result['embedding_path'] ?? '',
                    'confidence_score'=> $result['confidence'] ?? null,
                    'bbox'            => $result['bbox'] ?? null,
                ]
            );

            return redirect()
                ->route('admin.employees')
                ->with('success', 'Face registered successfully!');

        } catch (\Throwable $e) {

            Log::error('Register Face Failed', [
                'employee_nik' => $employee->nik,
                'message'      => $e->getMessage(),
                'file'         => $e->getFile(),
                'line'         => $e->getLine(),
            ]);

            return redirect()
                ->route('admin.employees')->with('success', 'Registrasi wajah gagal: ' . $e->getMessage());
        }
    }

    public function showEmployee(Employee $employee)
    {
        return view('admin.employees.show', compact('employee'));
    }

    public function deleteFace(Employee $employee)
    {


        // if (!$employee->hasFaceRegistered()) {
        //     return back()->withErrors(['error' => 'No face data found for this employee']);
        // }

        // // Delete from Python API
        // $result = $this->faceService->deleteFace($employee->nik);

        // if ($result['success']) {
        //     // Delete photo from storage
        //     if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
        //         Storage::disk('public')->delete($employee->photo_path);
        //     }

        //     // Update employee to remove photo path
        //     $employee->update(['photo_path' => null]);

        //     // Delete from database
        //     $employee->faceEmbedding()->delete();
        //     return redirect()->route('admin.employees')->with('success', 'Face data deleted successfully!');
        // }

        // return back()->withErrors(['error' => $result['error'] ?? 'Failed to delete face data']);


        try {
            if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
                Storage::disk('public')->delete($employee->photo_path);
            }

            // Update employee to remove photo path
            $employee->update(['photo_path' => null]);

            // Delete from database
            $employee->faceEmbedding()->delete();

            return redirect()->route('admin.employees')->with('success', 'Face data deleted successfully!');
        } catch (\Throwable $th) {
            return back()->withErrors(['error' => $result['error'] ?? 'Failed to delete face data']);
        }
    }

    public function mealTimeSettings()
    {
        $settings = MealTimeSetting::all();
        return view('admin.meal-times', compact('settings'));
    }

    public function updateMealTime(Request $request, $mealType)
    {
        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'boolean',
        ]);

        $setting = MealTimeSetting::where('meal_type', $mealType)->firstOrFail();
        $setting->update($validated);

        return redirect()->route('admin.meal-times')->with('success', 'Meal time updated successfully!');
    }

    public function attendanceReport(Request $request)
    {
        $query = AttendanceLog::with('employee');

        // Filter by date range
        if ($request->filled('filter_type')) {
            $filterType = $request->filter_type;

            if ($filterType === 'today') {
                $query->whereDate('attendance_date', today());
            } elseif ($filterType === 'week') {
                $query->whereBetween('attendance_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
            } elseif ($filterType === 'month') {
                $query->whereYear('attendance_date', now()->year)
                      ->whereMonth('attendance_date', now()->month);
            } elseif ($filterType === 'custom' && $request->filled(['start_date', 'end_date'])) {
                $query->whereBetween('attendance_date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
        } else {
            // Default: today
            $query->whereDate('attendance_date', today());
        }

        // Filter by meal type
        if ($request->filled('meal_type')) {
            $query->where('meal_type', $request->meal_type);
        }

        // Filter by employee
        if ($request->filled('employee_nik')) {
            $query->where('nik', $request->employee_nik);
        }

        $attendances = $query->latest('attendance_time')->paginate(50);
        $employees = Employee::where('is_active', true)->get();

        // Statistics
        $stats = [
            'total' => $attendances->total(),
            'breakfast' => AttendanceLog::where('meal_type', 'breakfast')
                ->when($request->filled('filter_type'), function($q) use ($request) {
                    $this->applyDateFilter($q, $request);
                })
                ->count(),
            'lunch' => AttendanceLog::where('meal_type', 'lunch')
                ->when($request->filled('filter_type'), function($q) use ($request) {
                    $this->applyDateFilter($q, $request);
                })
                ->count(),
            'dinner' => AttendanceLog::where('meal_type', 'dinner')
                ->when($request->filled('filter_type'), function($q) use ($request) {
                    $this->applyDateFilter($q, $request);
                })
                ->count(),
        ];

        return view('admin.attendance-report', compact('attendances', 'employees', 'stats'));
    }

    public function exportAttendance(Request $request)
    {
        $query = AttendanceLog::with('employee');


        // Apply same filters as report
        if ($request->filled('filter_type')) {
            $filterType = $request->filter_type;

            if ($filterType === 'today') {
                $query->whereDate('attendance_date', today());
            } elseif ($filterType === 'week') {
                $query->whereBetween('attendance_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
            } elseif ($filterType === 'month') {
                $query->whereYear('attendance_date', now()->year)
                      ->whereMonth('attendance_date', now()->month);
            } elseif ($filterType === 'custom' && $request->filled(['start_date', 'end_date'])) {
                $query->whereBetween('attendance_date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
        } else {
            // Default: today
            $query->whereDate('attendance_date', today());
        }

        if ($request->filled('meal_type')) {
            $query->where('meal_type', $request->meal_type);
        }

        if ($request->filled('employee_nik')) {
            $query->where('nik', $request->employee_nik);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
                             ->orderBy('attendance_time', 'desc')
                             ->get();

        // Generate Excel file
        $filename = 'Laporan_Absensi_' . now()->format('Y-m-d_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($attendances),
            $filename
        );
    }

    private function applyDateFilter($query, $request)
    {
        $filterType = $request->filter_type;

        if ($filterType === 'today') {
            $query->whereDate('attendance_date', today());
        } elseif ($filterType === 'week') {
            $query->whereBetween('attendance_date', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        } elseif ($filterType === 'month') {
            $query->whereYear('attendance_date', now()->year)
                  ->whereMonth('attendance_date', now()->month);
        } elseif ($filterType === 'custom' && $request->filled(['start_date', 'end_date'])) {
            $query->whereBetween('attendance_date', [
                $request->start_date,
                $request->end_date
            ]);
        }
    }
}
