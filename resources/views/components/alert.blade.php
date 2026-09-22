@if (session('success'))<div role="status" class="mb-5 rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">{{ session('success') }}</div>@endif
@if (session('error'))<div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>@endif
