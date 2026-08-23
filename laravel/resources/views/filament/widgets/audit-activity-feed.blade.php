<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">سجل النشاط التشغيلي</x-slot>

        <x-slot name="description">أحدث الإجراءات المسجلة داخل نطاقك الإداري.</x-slot>

        @if ($activities->isEmpty())
            <div class="fi-control-tower-empty">لا توجد أحداث تشغيلية موثقة في هذا النطاق بعد.</div>
        @else
            <div class="fi-control-tower-activity" role="list">
                @foreach ($activities as $activity)
                    <div class="fi-control-tower-activity__row" role="listitem">
                        <div>
                            <p class="fi-control-tower-activity__event">{{ $activity['event'] }}</p>
                            <p class="fi-control-tower-activity__meta">{{ $activity['actor'] }} · {{ $activity['subject'] }}</p>
                        </div>
                        <time class="fi-control-tower-activity__time" datetime="{{ $activity['occurred_at']->toAtomString() }}">{{ $activity['occurred_at']->diffForHumans() }}</time>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
