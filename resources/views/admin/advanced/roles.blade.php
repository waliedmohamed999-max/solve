@extends('layouts.admin')

@section('title', 'الأدوار والصلاحيات - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')

    <section class="space-y-6">
        @include('admin.components.data-toolbar', ['eyebrow' => 'RBAC Matrix', 'title' => 'الأدوار والصلاحيات المتقدمة'])

        <div class="grid gap-4 lg:grid-cols-4">
            @foreach ($matrix['roles'] as $role)
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-card">
                    <h3 class="text-lg font-black text-slate-900">{{ $role['name'] }}</h3>
                    <p class="mt-2 text-sm font-bold text-slate-500">{{ $role['scope'] }}</p>
                    <p class="mt-5 text-3xl font-black text-slate-900">{{ $role['users'] }}</p>
                    <p class="text-sm text-slate-500">مستخدم</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black text-slate-900">مصفوفة الصلاحيات الدقيقة</h3>
                    <p class="mt-1 text-sm text-slate-500">تطبق على الواجهة والـ backend ولا تعتمد على إخفاء الأزرار فقط.</p>
                </div>
                <button class="rounded-2xl bg-brand-600 px-4 py-2 text-sm font-bold text-white" @click="$dispatch('solve-toast', 'تم حفظ نسخة الصلاحيات')">حفظ</button>
            </div>
            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-100">
                <table class="w-full text-right text-sm">
                    <thead class="bg-slate-50 text-slate-500"><tr><th class="p-3">الوحدة</th><th class="p-3">عرض</th><th class="p-3">إنشاء</th><th class="p-3">تعديل</th><th class="p-3">حذف</th><th class="p-3">تصدير</th></tr></thead>
                    <tbody>
                        @foreach ($matrix['permissions'] as $permission)
                            <tr class="border-t border-slate-100">
                                <td class="p-3 font-black">{{ $permission['module'] }}</td>
                                @foreach (['view', 'create', 'update', 'delete', 'export'] as $key)
                                    <td class="p-3"><span class="inline-flex h-7 w-7 items-center justify-center rounded-full {{ $permission[$key] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-400' }} text-xs font-black">{{ $permission[$key] ? '✓' : '-' }}</span></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @include('admin.components.skeleton')
            @include('admin.components.empty-state', ['icon' => 'API', 'title' => 'حماية API', 'description' => 'كل request يجب أن يمر عبر role و store_id قبل قراءة أو تعديل البيانات.'])
            @include('admin.components.empty-state', ['icon' => '2FA', 'title' => 'طبقة أمان', 'description' => 'جاهزة لربط Two-Factor Authentication و Session Management في المرحلة التالية.'])
        </div>
    </section>
@endsection
