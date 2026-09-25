<aside class="dv-login-aside relative hidden overflow-hidden bg-gradient-to-br from-up-maroon via-up-maroon to-up-maroon-dark text-white lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16">
    {{-- Soft decorative glows and a faint grid, in UP gold and green --}}
    <div aria-hidden="true" class="pointer-events-none absolute -right-32 -top-32 size-[28rem] rounded-full bg-up-gold/15 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-40 -left-24 size-[28rem] rounded-full bg-up-green/50 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-[0.07] [background-image:linear-gradient(white_1px,transparent_1px),linear-gradient(90deg,white_1px,transparent_1px)] [background-size:3rem_3rem]"></div>
    <div aria-hidden="true" class="absolute inset-y-0 right-0 w-1 bg-gradient-to-b from-up-gold via-up-gold/60 to-up-green"></div>

    <div class="relative flex items-center gap-3">
        <span class="flex size-11 items-center justify-center rounded-xl bg-white/10 text-sm font-bold tracking-tight text-up-gold ring-1 ring-white/20 backdrop-blur">
            DV
        </span>
        <span class="flex flex-col leading-tight">
            <span class="text-lg font-semibold">DV Tracker</span>
            <span class="text-xs font-medium uppercase tracking-widest text-white/60">Disbursement Vouchers</span>
        </span>
    </div>

    <div class="relative max-w-lg space-y-8">
        <div class="space-y-4">
            <h1 class="text-4xl font-semibold leading-tight tracking-tight xl:text-5xl">
                Every voucher,<br>
                <span class="text-up-gold">accounted for.</span>
            </h1>
            <p class="text-lg text-white/75">
                Prepare, review, and complete disbursement vouchers in one place, and always know exactly where each one is.
            </p>
        </div>

        <ol class="space-y-3">
            @foreach ([
                ['heroicon-o-pencil-square', 'Prepare & submit', 'Requesting units draft vouchers and send them to Finance.'],
                ['heroicon-o-clipboard-document-check', 'Review', 'The Finance Processor checks each voucher, then forwards or returns it.'],
                ['heroicon-o-check-badge', 'Complete', 'The Supervisor marks it paid, and every step stays on record.'],
            ] as [$icon, $title, $description])
                <li class="flex items-start gap-4 rounded-xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-sm">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-up-gold/15 text-up-gold ring-1 ring-up-gold/30">
                        <x-filament::icon :icon="$icon" class="size-5" />
                    </span>
                    <span>
                        <span class="block font-semibold">{{ $title }}</span>
                        <span class="block text-sm text-white/70">{{ $description }}</span>
                    </span>
                </li>
            @endforeach
        </ol>
    </div>

    <p class="relative text-sm text-white/50">
        &copy; {{ now()->year }} DV Tracker &middot; Finance Office
    </p>
</aside>

{{-- Compact brand strip for small screens, where the side panel is hidden --}}
<div class="dv-login-mobile-brand flex w-full items-center justify-center gap-2.5 bg-up-maroon px-6 py-4 text-white lg:hidden">
    <span class="flex size-8 items-center justify-center rounded-lg bg-white/10 text-xs font-bold text-up-gold ring-1 ring-white/20">DV</span>
    <span class="text-sm font-semibold">DV Tracker</span>
</div>
