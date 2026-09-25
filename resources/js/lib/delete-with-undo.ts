import { router } from '@inertiajs/react';
import { useSyncExternalStore } from 'react';
import { toast } from 'sonner';

const hidden = new Set<string>();
const listeners = new Set<() => void>();
const emit = () => listeners.forEach((l) => l());

/** True while an item waits for its delayed delete; hide it in the UI. */
export function useIsHidden(key: string): boolean {
    return useSyncExternalStore(
        (l) => (listeners.add(l), () => listeners.delete(l)),
        () => hidden.has(key),
    );
}

// ponytail: delete fires after 5s in this tab; closing the tab inside that window keeps the item. Soft deletes if that matters.
export function deleteWithUndo({ key, url, message }: { key: string; url: string; message: string }) {
    hidden.add(key);
    emit();

    const restore = () => {
        hidden.delete(key);
        emit();
    };
    const timer = setTimeout(() => {
        router.delete(url, { preserveScroll: true, preserveState: true, onFinish: restore });
    }, 5000);

    toast(message, {
        duration: 5000,
        action: {
            label: 'Rückgängig',
            onClick: () => {
                clearTimeout(timer);
                restore();
            },
        },
    });
}
