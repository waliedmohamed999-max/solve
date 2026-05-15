@extends('layouts.admin')

@section('title', 'Solve Admin | Activity Log')

@section('admin-content')
<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <p class="text-sm font-bold text-brand-600">Audit Trail</p>
    <h2 class="mt-2 text-3xl font-extrabold text-slate-900">سجل الأنشطة</h2>
    <p class="mt-3 text-sm leading-8 text-slate-500">كل عمليات الإنشاء والتعديل وتسجيل الدخول وتغيير الصلاحيات تُسجل هنا مع نطاق المتجر والدور.</p>
</section>

<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <div class="overflow-hidden rounded-[28px] border border-slate-100">
        <table class="min-w-full divide-y divide-slate-100 text-right text-sm">
            <thead class="bg-slate-50 text-xs font-extrabold text-slate-500">
                <tr>
                    <th class="px-5 py-4">العملية</th>
                    <th class="px-5 py-4">المستخدم</th>
                    <th class="px-5 py-4">الدور</th>
                    <th class="px-5 py-4">المورد</th>
                    <th class="px-5 py-4">المتجر</th>
                    <th class="px-5 py-4">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-5 py-4 font-extrabold text-slate-900">{{ $log->action }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $log->actor_name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $log->role }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $log->subject_type }} / {{ $log->subject_id }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $log->store_id ?: '-' }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $log->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center font-bold text-slate-500">لا توجد أنشطة مسجلة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (method_exists($logs, 'links'))
        <div class="mt-5">{{ $logs->links() }}</div>
    @endif
</section>
@endsection
