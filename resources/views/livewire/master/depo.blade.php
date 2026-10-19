<div>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                    Master Depo
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Kelola data lokasi dan informasi depo perusahaan
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="btn btn-outline btn-success ">
                    <span class="icon-[tabler--refresh]"></span>
                    Refresh
                </button>
                <button type="button" class="btn btn-success">
                    <span class="icon-[tabler--check]"></span>
                    Tambah
                </button>
            </div>
        </div>
    </x-slot>

    {{-- DAFTAR DEPO --}}
    <div
        class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div
            class="flex flex-col gap-4 border-b border-slate-100 p-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">
                    Daftar Depo
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Data master lokasi perusahaan
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama"
                    class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white sm:w-64">

                <select wire:model.live="filterStatus"
                    class="select select-sm bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                    <tr>
                        <th class="w-12">No.</th>
                        <th>Kode Depo</th>
                        <th>Nama Depo</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Dibuat Pada</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>


                @php
                    $dummyDepos = [
                        [
                            'id' => 1,
                            'code' => '336',
                            'name' => 'Serang',
                            'type' => 'Depo',
                            'is_active' => true,
                            'created_at' => '2026-10-01',
                        ],
                        [
                            'id' => 2,
                            'code' => '320',
                            'name' => 'Kelapa Dua',
                            'type' => 'Depo',
                            'is_active' => true,
                            'created_at' => '2026-10-05',
                        ],
                    ];
                @endphp

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($dummyDepos as $index => $depo)
                        <tr wire:key="depo-{{ $depo['id'] }}"
                            class="hover:bg-emerald-50/40 dark:hover:bg-slate-800/60">

                            <td class="text-slate-500 dark:text-slate-400">
                                {{ $index + 1 }}
                            </td>

                            <td class="font-semibold text-emerald-700 dark:text-emerald-400">
                                {{ $depo['code'] }}
                            </td>

                            <td class="font-medium text-slate-800 dark:text-white">
                                {{ $depo['name'] }}
                            </td>

                            <td>
                                <span class="badge badge-soft badge-info">
                                    {{ $depo['type'] }}
                                </span>
                            </td>

                            <td>
                                @if ($depo['is_active'])
                                    <span class="badge badge-soft badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-soft badge-secondary">Nonaktif</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($depo['created_at'])->format('d/m/Y') }}
                            </td>

                            <td>
                                <div class="flex justify-center gap-1">
                                    <button type="button" wire:click="edit({{ $depo['id'] }})"
                                        class="btn btn-text btn-sm text-emerald-700 dark:text-emerald-400">
                                        Edit
                                    </button>

                                    <button type="button" wire:click="confirmDelete({{ $depo['id'] }})"
                                        class="btn btn-text btn-sm text-red-600 dark:text-red-400">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

        <div
            class="flex flex-col gap-3 border-t border-slate-100 p-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Menampilkan 12 dari 21 depo
            </p>


        </div>
    </div>
</div>
