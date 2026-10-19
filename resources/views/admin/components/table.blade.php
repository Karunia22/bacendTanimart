{{--
  Data table M3. Slot: thead / tbody.
  Contoh:
    <x-admin.table>
      <thead slot="head"><tr><th>Nama</th></tr></thead>
      <tbody><tr><td>...</td></tr></tbody>
    </x-admin.table>
--}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-lg border border-outline-variant bg-surface-container-low']) }}>
    <table class="w-full text-sm text-on-surface">
        <thead class="bg-surface-container-highest text-on-surface-variant">
            {{ $head ?? '' }}
        </thead>
        <tbody class="divide-y divide-outline-variant">
            {{ $slot }}
        </tbody>
    </table>
</div>
