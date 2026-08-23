<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">إشارات التشغيل</x-slot>
        <x-slot name="description">تحديث تلقائي كل 30 ثانية عبر اتصال الويب المعتاد.</x-slot>

        <div class="grid gap-3 md:grid-cols-3">
            @foreach ($signals as $signal)
                <a href="{{ $signal['href'] }}" class="group rounded-2xl border border-gray-200 bg-gray-50 p-5 transition hover:-translate-y-0.5 hover:shadow-lg dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $signal['label'] }}</span>
                        <span @class([
                            'rounded-full px-3 py-1 text-sm font-bold',
                            'bg-amber-100 text-amber-800 dark:bg-amber-400/15 dark:text-amber-200' => $signal['tone'] === 'warning',
                            'bg-rose-100 text-rose-800 dark:bg-rose-400/15 dark:text-rose-200' => $signal['tone'] === 'danger',
                            'bg-sky-100 text-sky-800 dark:bg-sky-400/15 dark:text-sky-200' => $signal['tone'] === 'info',
                        ])>{{ $signal['value'] }}</span>
                    </div>
                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">فتح قائمة العمليات ذات الصلة</p>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
