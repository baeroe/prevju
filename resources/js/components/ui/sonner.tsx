import { Toaster as Sonner } from 'sonner';

function Toaster() {
    return (
        <Sonner
            position="bottom-center"
            toastOptions={{
                unstyled: true,
                classNames: {
                    toast: 'flex w-[min(92vw,24rem)] items-center gap-3 border border-ink bg-ink px-4 py-3 text-sm text-sheet',
                    title: 'flex-1',
                    actionButton: 'cursor-pointer bg-signal px-2 py-1 text-xs font-medium text-signal-ink hover:bg-sheet',
                    error: '!border-danger !bg-sheet !text-danger',
                },
            }}
        />
    );
}

export { Toaster };
