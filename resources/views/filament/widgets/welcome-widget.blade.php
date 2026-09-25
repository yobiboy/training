<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-up-maroon via-up-maroon to-up-maroon-dark text-white shadow-sm ring-1 ring-black/5">
        {{-- Soft decorative glows in UP green and gold --}}
        <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-24 size-72 rounded-full bg-up-gold/15 blur-3xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 left-1/3 size-72 rounded-full bg-up-green/40 blur-3xl"></div>
        <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-up-gold via-up-gold/60 to-up-green"></div>

        <div class="relative flex flex-col gap-6 p-6 sm:p-8 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 space-y-3">
                <p class="text-sm font-medium text-white/70">{{ $today }}</p>

                <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    {{ $greeting }}, {{ $firstName }}!
                </h2>

                @if ($roleLabel || $officeName)
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        @if ($roleLabel)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 font-medium ring-1 ring-white/20">
                                <x-filament::icon icon="heroicon-m-identification" class="size-4 text-up-gold" />
                                {{ $roleLabel }}
                            </span>
                        @endif

                        @if ($officeName)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 font-medium ring-1 ring-white/20">
                                <x-filament::icon icon="heroicon-m-building-office-2" class="size-4 text-up-gold" />
                                {{ $officeName }}
                            </span>
                        @endif
                    </div>
                @endif

                <p class="max-w-2xl text-base text-white/85">{{ $summary }}</p>
            </div>

            @if (count($actions))
                <div class="flex shrink-0 flex-wrap gap-3">
                    @foreach ($actions as $action)
                        <a
                            href="{{ $action['url'] }}"
                            @class([
                                'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-up-gold focus-visible:ring-offset-2 focus-visible:ring-offset-up-maroon',
                                'bg-up-gold text-up-maroon-dark hover:bg-amber-300' => $action['primary'],
                                'bg-white/10 text-white ring-1 ring-white/25 hover:bg-white/20' => ! $action['primary'],
                            ])
                        >
                            <x-filament::icon :icon="$action['icon']" class="size-5" />
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if (count($attention))
            <div class="relative border-t border-white/10 bg-black/10 px-6 py-4 sm:px-8">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-up-gold">Needs your attention</p>

                <ul class="divide-y divide-white/10">
                    @foreach ($attention as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="group flex items-center justify-between gap-4 py-2">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium">{{ $item['label'] }}</span>
                                    @if ($item['detail'])
                                        <span class="block truncate text-sm text-white/70">“{{ $item['detail'] }}”</span>
                                    @endif
                                </span>
                                <span class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-up-gold group-hover:underline">
                                    Fix &amp; resubmit
                                    <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" />
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($currentStep)
            <div class="relative border-t border-white/10 px-6 py-4 sm:px-8">
                <p class="sr-only">Where your work sits in the voucher workflow</p>
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-2 text-xs font-medium sm:text-sm">
                    @foreach ($workflowSteps as $step => $label)
                        <li class="flex items-center gap-2">
                            <span @class([
                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1',
                                'bg-up-gold text-up-maroon-dark' => $step === $currentStep,
                                'text-white/65' => $step !== $currentStep,
                            ])>
                                <span @class([
                                    'flex size-5 items-center justify-center rounded-full text-[0.7rem] font-bold',
                                    'bg-up-maroon-dark text-up-gold' => $step === $currentStep,
                                    'bg-white/10 text-white/80' => $step !== $currentStep,
                                ])>{{ $loop->iteration }}</span>
                                {{ $label }}
                                @if ($step === $currentStep)
                                    <span class="sr-only">(your step)</span>
                                @endif
                            </span>

                            @unless ($loop->last)
                                <x-filament::icon icon="heroicon-m-chevron-right" class="size-4 text-white/40" />
                            @endunless
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
