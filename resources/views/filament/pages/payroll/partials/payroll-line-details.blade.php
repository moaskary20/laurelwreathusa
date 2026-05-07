@php
    /** @var \App\Models\PayrollRunLine $line */
    $employee = $line->employee;
    $allowances = $line->items->where('type', 'allowance');
    $deductions = $line->items->where('type', 'deduction');
@endphp

<div class="space-y-4 text-right" dir="rtl">
    <div class="grid grid-cols-2 gap-3 text-sm">
        <div><span class="font-semibold">الموظف:</span> {{ $employee?->name_ar ?? '—' }}</div>
        <div><span class="font-semibold">الرقم الوظيفي:</span> {{ $employee?->job_number ?? '—' }}</div>
        <div><span class="font-semibold">الرقم الوطني:</span> {{ $employee?->national_id ?? '—' }}</div>
        <div><span class="font-semibold">رقم الضمان:</span> {{ $employee?->social_security_number ?? '—' }}</div>
        <div><span class="font-semibold">مركز الكلفة:</span> {{ $employee?->costCenter?->name_ar ?? '—' }}</div>
        <div><span class="font-semibold">شهر الدورة:</span> {{ $line->payrollRun?->period_month?->format('Y-m') ?? '—' }}</div>
    </div>

    <div class="rounded-lg border p-3">
        <div class="mb-2 font-semibold">مكونات الراتب</div>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div>الراتب الأساسي: {{ number_format((float) $line->basic_salary, 2) }}</div>
            <div>مجموع العلاوات: {{ number_format((float) $line->allowances_total, 2) }}</div>
            <div>مجموع الاقتطاعات: {{ number_format((float) $line->deductions_total, 2) }}</div>
            <div>حصة الموظف بالضمان: {{ number_format((float) $line->employee_social_security, 2) }}</div>
            <div>مساهمة الشركة بالضمان: {{ number_format((float) $line->company_social_security, 2) }}</div>
            <div>صافي الراتب: {{ number_format((float) $line->net_salary, 2) }}</div>
        </div>
    </div>

    <div class="rounded-lg border p-3">
        <div class="mb-2 font-semibold">تفصيل العلاوات</div>
        @forelse($allowances as $item)
            <div class="text-sm">{{ $item->label }}: {{ number_format((float) $item->amount, 2) }}</div>
        @empty
            <div class="text-sm text-gray-500">لا يوجد علاوات</div>
        @endforelse
    </div>

    <div class="rounded-lg border p-3">
        <div class="mb-2 font-semibold">تفصيل الاقتطاعات</div>
        @forelse($deductions as $item)
            <div class="text-sm">{{ $item->label }}: {{ number_format((float) $item->amount, 2) }}</div>
        @empty
            <div class="text-sm text-gray-500">لا يوجد اقتطاعات</div>
        @endforelse
    </div>
</div>
