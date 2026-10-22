<div x-data="{
    toasts: [],
    styles: {
        success: { box: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800/40 dark:bg-emerald-950/80 dark:text-emerald-300', icon: 'icon-[tabler--circle-check]' },
        error: { box: 'border-red-200 bg-red-50 text-red-800 dark:border-red-800/40 dark:bg-red-950/80 dark:text-red-300', icon: 'icon-[tabler--alert-circle]' },
        warning: { box: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800/40 dark:bg-amber-950/80 dark:text-amber-300', icon: 'icon-[tabler--alert-triangle]' },
        info: { box: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-800/40 dark:bg-sky-950/80 dark:text-sky-300', icon: 'icon-[tabler--info-circle]' },
    },
    add(detail) {
        const d = Array.isArray(detail) ? detail[0] : detail;
        const id = Date.now() + Math.random();
        this.toasts.push({ id, type: d.type ?? 'success', message: d.message ?? '' });
        setTimeout(() => this.remove(id), d.duration ?? 4000);
    },
    remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
}" x-on:toast.window="add($event.detail)"
    class="pointer-events-none fixed right-4 top-4 z-[200] flex w-full max-w-sm flex-col gap-2">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-6 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            :class="(styles[t.type] ?? styles.info).box"
            class="pointer-events-auto flex items-start gap-2.5 rounded-xl border p-4 shadow-lg" role="alert">
            <span :class="(styles[t.type] ?? styles.info).icon" class="mt-0.5 size-5 shrink-0"></span>
            <p class="flex-1 text-sm font-medium" x-text="t.message"></p>
            <button type="button" @click="remove(t.id)" class="opacity-60 hover:opacity-100">
                <span class="icon-[tabler--x] size-4"></span>
            </button>
        </div>
    </template>
</div>
