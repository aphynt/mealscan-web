@extends('layouts.admin')

@section('title', 'Edit Employee')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <a href="{{ route('admin.employees') }}" class="text-indigo-600 hover:text-indigo-800 text-sm">&larr; Back to Employees</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Edit Employee</h1>
    </div>

    <div class="bg-white shadow-lg rounded-xl p-8">
        <form method="POST" action="{{ route('admin.employees.update', $employee) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="nik" class="block text-sm font-medium text-gray-700 mb-2">NIK *</label>
                <input type="text" name="nik" id="nik" required
                       value="{{ old('nik', $employee->nik) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('nik') border-red-500 @enderror">
                @error('nik')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                <input type="text" name="name" id="name" required
                       value="{{ old('name', $employee->name) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', $employee->is_active) ? 'checked' : '' }}
                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-700">
                    Active
                </label>
            </div>

            <div class="flex justify-end space-x-3 pt-6">
                <a href="{{ route('admin.employees') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition">
                    Update Employee
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
