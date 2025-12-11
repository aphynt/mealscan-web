@extends('layouts.admin')

@section('title', 'Employee Detail')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">Employee Detail</h1>
        <a href="{{ route('admin.employees') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
            ← Back to Employees
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-8">
            <!-- Photo Section -->
            <div class="md:col-span-1">
                <div class="bg-gray-50 rounded-lg p-4">
                    @if($employee->photo_url)
                        <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" 
                             class="w-full h-auto rounded-lg shadow-md">
                    @else
                        <div class="w-full aspect-square bg-gray-200 rounded-lg flex items-center justify-center">
                            <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <p class="text-center text-sm text-gray-500 mt-2">No photo registered</p>
                    @endif
                </div>
            </div>

            <!-- Details Section -->
            <div class="md:col-span-2 space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Personal Information</h2>
                    
                    <div class="space-y-4">
                        <div class="border-b border-gray-200 pb-3">
                            <label class="text-sm font-medium text-gray-500">NIK</label>
                            <p class="mt-1 text-lg text-gray-900 font-semibold">{{ $employee->nik }}</p>
                        </div>

                        <div class="border-b border-gray-200 pb-3">
                            <label class="text-sm font-medium text-gray-500">Name</label>
                            <p class="mt-1 text-lg text-gray-900 font-semibold">{{ $employee->name }}</p>
                        </div>

                        <div class="border-b border-gray-200 pb-3">
                            <label class="text-sm font-medium text-gray-500">Status</label>
                            <div class="mt-1">
                                @if($employee->is_active)
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="border-b border-gray-200 pb-3">
                            <label class="text-sm font-medium text-gray-500">Face Registration</label>
                            <div class="mt-1">
                                @if($employee->hasFaceRegistered())
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Registered
                                    </span>
                                @else
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Not Registered
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="border-b border-gray-200 pb-3">
                            <label class="text-sm font-medium text-gray-500">Member Since</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $employee->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex space-x-3 pt-4">
                    <a href="{{ route('admin.employees.edit', $employee) }}" 
                       class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium text-center transition">
                        Edit Employee
                    </a>
                    
                    @if($employee->hasFaceRegistered())
                        <form action="{{ route('admin.employees.delete-face', $employee) }}" method="POST" class="flex-1" onsubmit="return confirm('Delete face data? This will remove the photo and face registration.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-medium transition">
                                Remove Face
                            </button>
                        </form>
                    @else
                        <button onclick="openFaceRegisterModal('{{ $employee->id }}', '{{ $employee->nik }}', '{{ $employee->name }}')" 
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition">
                            Register Face
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Statistics -->
    <div class="bg-white shadow-lg rounded-xl p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Attendance Statistics</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-blue-600 font-medium">Total Attendance</p>
                <p class="text-3xl font-bold text-blue-900 mt-1">{{ $employee->attendanceLogs()->count() }}</p>
            </div>

            <div class="bg-yellow-50 rounded-lg p-4">
                <p class="text-sm text-yellow-600 font-medium">Breakfast</p>
                <p class="text-3xl font-bold text-yellow-900 mt-1">{{ $employee->attendanceLogs()->where('meal_type', 'breakfast')->count() }}</p>
            </div>

            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-blue-600 font-medium">Lunch</p>
                <p class="text-3xl font-bold text-blue-900 mt-1">{{ $employee->attendanceLogs()->where('meal_type', 'lunch')->count() }}</p>
            </div>

            <div class="bg-purple-50 rounded-lg p-4">
                <p class="text-sm text-purple-600 font-medium">Dinner</p>
                <p class="text-3xl font-bold text-purple-900 mt-1">{{ $employee->attendanceLogs()->where('meal_type', 'dinner')->count() }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Face Registration Modal -->
<div id="faceRegisterModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-lg rounded-xl bg-white">
        <div class="text-center">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Register Face</h3>
            <p class="text-gray-600 mb-2">NIK: <span id="modalEmployeeId" class="font-semibold"></span></p>
            <p class="text-gray-600 mb-6">Name: <span id="modalEmployeeName" class="font-semibold"></span></p>
            
            <form id="faceRegisterForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-left text-sm font-medium text-gray-700 mb-2">Upload Photo</label>
                    <input type="file" name="photo" accept="image/*" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Upload a clear photo of the employee's face (JPG, PNG)</p>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeFaceRegisterModal()"
                            class="flex-1 px-4 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition">
                        Register
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openFaceRegisterModal(employeeId, employeeIdText, employeeName) {
        document.getElementById('modalEmployeeId').textContent = employeeIdText;
        document.getElementById('modalEmployeeName').textContent = employeeName;
        document.getElementById('faceRegisterForm').action = '/admin/employees/' + employeeId + '/register-face';
        document.getElementById('faceRegisterModal').classList.remove('hidden');
    }

    function closeFaceRegisterModal() {
        document.getElementById('faceRegisterModal').classList.add('hidden');
    }
</script>
@endsection
