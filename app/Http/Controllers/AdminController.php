<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MealTimeSetting;
use App\Models\AttendanceLog;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;

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

    public function employees()
    {
        $employees = Employee::with('faceEmbedding')->latest()->paginate(15);
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
        $tempPath = $photo->getRealPath();

        // Call Python API to register face
        $result = $this->faceService->registerFace($employee->nik, $tempPath);

        if ($result['success']) {
            // Save photo to Laravel storage
            $photoPath = $photo->store('faces', 'public');
            
            // Update employee with photo path
            $employee->update(['photo_path' => $photoPath]);

            // Save face embedding data to database
            $employee->faceEmbedding()->updateOrCreate(
                ['nik' => $employee->nik],
                [
                    'embedding_path' => $result['embedding_path'] ?? '',
                    'confidence_score' => $result['confidence'] ?? null,
                    'bbox' => $result['bbox'] ?? null,
                ]
            );

            return redirect()->route('admin.employees')->with('success', 'Face registered successfully!');
        }

        return back()->withErrors(['photo' => $result['error'] ?? 'Failed to register face']);
    }

    public function showEmployee(Employee $employee)
    {
        return view('admin.employees.show', compact('employee'));
    }

    public function deleteFace(Employee $employee)
    {
        if (!$employee->hasFaceRegistered()) {
            return back()->withErrors(['error' => 'No face data found for this employee']);
        }

        // Delete from Python API
        $result = $this->faceService->deleteFace($employee->nik);

        if ($result['success']) {
            // Delete photo from storage
            if ($employee->photo_path && \Storage::disk('public')->exists($employee->photo_path)) {
                \Storage::disk('public')->delete($employee->photo_path);
            }
            
            // Update employee to remove photo path
            $employee->update(['photo_path' => null]);
            
            // Delete from database
            $employee->faceEmbedding()->delete();
            return redirect()->route('admin.employees')->with('success', 'Face data deleted successfully!');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Failed to delete face data']);
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
