@props(['status'])
<span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize', 'bg-teal-50 text-teal-800' => in_array($status, ['active', 'completed']), 'bg-amber-50 text-amber-800' => $status === 'scheduled', 'bg-slate-100 text-slate-600' => in_array($status, ['inactive', 'cancelled'])])>{{ $status }}</span>
