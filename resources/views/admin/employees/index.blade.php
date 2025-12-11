@extends('layouts.admin')

@section('title', 'Manage Employees')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Employees</h1>
        <a href="{{ route('admin.employees.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition">
            + Add Employee
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIK</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Face Registered</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $employee->nik }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $employee->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($employee->hasFaceRegistered())
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">No</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($employee->is_active)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="{{ route('admin.employees.show', $employee) }}" class="text-blue-600 hover:text-blue-900" title="View Details">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                
                                <a href="{{ route('admin.employees.edit', $employee) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                
                                @if($employee->hasFaceRegistered())
                                    <form action="{{ route('admin.employees.delete-face', $employee) }}" method="POST" class="inline" onsubmit="return confirm('Delete face data?')">
                                        @csrf
                                        @method('DELETE')
                                            <button type="submit" class="text-orange-600 hover:text-orange-900">Remove Face</button>
                                    </form>
                                @else
                                    <button onclick="openFaceRegisterModal('{{ $employee->id }}', '{{ $employee->nik }}', '{{ $employee->name }}')" class="text-green-600 hover:text-green-900">
                                        Register Face
                                    </button>
                                @endif
                                
                                <form action="{{ route('admin.employees.delete', $employee) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                No employees found. <a href="{{ route('admin.employees.create') }}" class="text-indigo-600 hover:text-indigo-800">Add your first employee</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $employees->links() }}
            </div>
        @endif
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
