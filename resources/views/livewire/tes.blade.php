<div>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                    Jurnal Umum
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Kelola dan tinjau transaksi pencatatan jurnal umum
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button"
                    class="btn btn-outline btn-sm border-emerald-200 bg-white text-emerald-800 hover:bg-emerald-50 dark:border-emerald-800 dark:bg-slate-900 dark:text-emerald-400 dark:hover:bg-slate-800">
                    Export Excel
                </button>

                <button type="button"
                    class="btn btn-sm border-emerald-600 bg-emerald-600 text-white hover:border-emerald-700 hover:bg-emerald-700">
                    + Transaksi Baru
                </button>
            </div>
        </div>
    </x-slot>

    {{-- SUMMARY CARDS --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">

        <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="card-body gap-2 p-5">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                    Total Debit
                </p>

                <h2 class="text-2xl font-bold text-emerald-800 dark:text-emerald-400">
                    Rp124.500.000
                </h2>

                <span class="badge badge-soft badge-success">
                    Periode berjalan
                </span>
            </div>
        </div>

        <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="card-body gap-2 p-5">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                    Total Kredit
                </p>

                <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
                    Rp124.500.000
                </h2>

                <span class="badge badge-soft badge-success">
                    Seimbang
                </span>
            </div>
        </div>

        <div class="card card-border border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="card-body gap-2 p-5">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                    Status Jurnal
                </p>

                <h2 class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">
                    Balanced
                </h2>

                <span class="badge badge-soft badge-success">
                    Debit = Kredit
                </span>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div
        class="card card-border overflow-hidden border-emerald-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">

        <div
            class="flex flex-col gap-3 border-b border-emerald-100 bg-emerald-50/30 p-4 dark:border-slate-800 dark:bg-slate-800/50 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-white">
                    Daftar Transaksi Jurnal
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Riwayat transaksi jurnal umum
                </p>
            </div>

            <div class="flex gap-2">
                <input type="search" placeholder="Cari nomor jurnal..."
                    class="input input-sm w-full bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-white sm:w-64">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Jurnal</th>
                        <th>Keterangan</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Kredit</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr class="hover:bg-emerald-50/40 dark:hover:bg-slate-800/60">
                        <td class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                            17 Okt 2026
                        </td>

                        <td class="whitespace-nowrap font-medium text-emerald-700 dark:text-emerald-400">
                            JV-202610-001
                        </td>

                        <td class="text-slate-600 dark:text-slate-300">
                            Penerimaan dari PT Maju Bersama
                        </td>

                        <td class="whitespace-nowrap text-right font-medium text-slate-700 dark:text-slate-200">
                            Rp15.000.000
                        </td>

                        <td class="whitespace-nowrap text-right font-medium text-slate-700 dark:text-slate-200">
                            Rp15.000.000
                        </td>

                        <td class="text-center">
                            <button type="button"
                                class="btn btn-sm btn-outline border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400 dark:hover:bg-slate-800">
                                Detail
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 p-4 dark:border-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Menampilkan 1 transaksi
            </p>
        </div>
    </div>
</div>
