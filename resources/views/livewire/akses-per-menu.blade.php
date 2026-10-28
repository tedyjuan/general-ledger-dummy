<div class="card">
    <div class="card-header flex flex-wrap items-center justify-between gap-3">
        <div>
            <h5 class="card-title">Akses per Menu</h5>
            <p class="text-sm text-base-content/60">Pilih menu, lalu centang aksi yang boleh dilakukan tiap role.</p>
        </div>

        <select wire:model.live="menu" class="select max-w-xs" aria-label="Pilih menu">
            @foreach ($daftarMenu as $key => $def)
                <option value="{{ $key }}">{{ $def['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="card-body">
        @if ($tersimpan)
            <div class="alert alert-soft alert-success mb-4" role="alert">
                Akses untuk menu <b>{{ $daftarMenu[$menu]['label'] ?? $menu }}</b> berhasil disimpan.
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Role</th>
                        @foreach ($aksiMenu as $aksi)
                            <th class="text-center">{{ ucfirst($aksi) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($perans as $i => $peran)
                        @php($terkunci = $peran->code === 'ICTT' && $menu === 'permissions')

                        <tr wire:key="{{ $menu }}-{{ $peran->code }}">
                            <td>
                                <div class="font-medium">{{ $peran->name }}</div>
                                <div class="text-xs text-base-content/60">
                                    {{ $peran->code }}
                                </div>
                            </td>

                            @foreach ($aksiMenu as $aksi)
                                <td class="text-center">
                                    <input type="checkbox" class="checkbox checkbox-primary"
                                        wire:model="matrix.{{ $i }}.{{ $aksi }}"
                                        @disabled($terkunci)
                                        aria-label="{{ $peran->code }} - {{ $aksi }}">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <p class="text-sm text-base-content/60">
                Mencentang create, update, atau delete otomatis mengaktifkan read.
            </p>
            <button wire:click="simpan" class="btn btn-primary">
                <span wire:loading wire:target="simpan" class="loading loading-spinner loading-sm"></span>
                Simpan
            </button>
        </div>
    </div>
</div>
