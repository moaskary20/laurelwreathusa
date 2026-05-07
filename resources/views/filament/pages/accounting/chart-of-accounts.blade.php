<x-filament-panels::page>
    <div dir="rtl" class="-mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mb-4">
            <div class="text-xl font-semibold">شجرة الحسابات</div>
            <div class="text-sm text-gray-500">تعريف الحسابات، التحكم في الترحيل، والتفعيل/الإيقاف</div>
        </div>

        <div class="rounded-xl border bg-white p-3 shadow-sm dark:bg-gray-900">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
