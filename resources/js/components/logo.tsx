export function Logo() {
    return (
        <span className="inline-flex items-center gap-2 text-lg font-semibold tracking-tight" translate="no">
            <svg viewBox="0 0 24 24" className="size-5" aria-hidden>
                <circle cx="12" cy="12" r="6" fill="none" stroke="currentColor" strokeWidth="1.5" />
                <path d="M12 2v20M2 12h20" stroke="currentColor" strokeWidth="1.5" />
                <circle cx="12" cy="12" r="2" fill="var(--signal)" />
            </svg>
            prevju
        </span>
    );
}
