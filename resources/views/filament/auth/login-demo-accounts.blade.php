{{-- Local development only: the seeded demo logins, one per role. --}}
<details class="group rounded-xl border border-dashed border-gray-300 bg-gray-50/80 px-4 py-3 text-sm dark:border-white/15 dark:bg-white/5">
    <summary class="flex cursor-pointer list-none items-center justify-between font-medium text-gray-700 dark:text-gray-200">
        <span class="inline-flex items-center gap-2">
            <x-filament::icon icon="heroicon-m-beaker" class="size-4 text-up-maroon dark:text-up-gold" />
            Demo accounts
        </span>
        <x-filament::icon icon="heroicon-m-chevron-down" class="size-4 text-gray-400 transition group-open:rotate-180" />
    </summary>

    <ul class="mt-3 space-y-1.5 text-gray-600 dark:text-gray-300">
        @foreach ([
            'requester@example.com' => 'Requesting Unit',
            'processor@example.com' => 'Finance Processor',
            'supervisor@example.com' => 'Finance Supervisor',
            'admin@example.com' => 'Administrator',
        ] as $email => $role)
            <li class="flex items-center justify-between gap-3">
                <code class="truncate text-xs">{{ $email }}</code>
                <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $role }}</span>
            </li>
        @endforeach
    </ul>

    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Password: <code>password</code></p>
</details>
