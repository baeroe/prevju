export type UploadItem = { file: File; path: string };

function xsrfToken(): string {
    const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return m ? decodeURIComponent(m[1]) : '';
}

/** POST one file; XHR instead of fetch because fetch has no upload progress. */
export function uploadFile(url: string, item: UploadItem, onProgress: (ratio: number) => void): Promise<void> {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const body = new FormData();
        body.append('file', item.file);
        body.append('path', item.path);

        xhr.open('POST', url);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-XSRF-TOKEN', xsrfToken());
        xhr.upload.onprogress = (e) => e.lengthComputable && onProgress(e.loaded / e.total);
        xhr.onload = () => {
            if (xhr.status < 300) return resolve();
            let message = `Upload fehlgeschlagen (${xhr.status}).`;
            try {
                const json = JSON.parse(xhr.responseText);
                message = Object.values(json.errors ?? {}).flat()[0] as string ?? json.message ?? message;
            } catch {
                if (xhr.status === 413) message = 'Datei ist zu groß für den Server.';
            }
            reject(new Error(message));
        };
        xhr.onerror = () => reject(new Error('Keine Verbindung zum Server.'));
        xhr.send(body);
    });
}

/** Files from a drop, including folders (read recursively, paths kept). */
export async function itemsFromDrop(dt: DataTransfer): Promise<UploadItem[]> {
    const entries = [...dt.items].map((i) => i.webkitGetAsEntry()).filter((e): e is FileSystemEntry => !!e);
    const out: UploadItem[] = [];

    async function walk(entry: FileSystemEntry, prefix: string): Promise<void> {
        if (entry.isFile) {
            const file = await new Promise<File>((res, rej) => (entry as FileSystemFileEntry).file(res, rej));
            out.push({ file, path: prefix + entry.name });
            return;
        }
        const reader = (entry as FileSystemDirectoryEntry).createReader();
        // readEntries returns batches, call until empty
        for (;;) {
            const batch = await new Promise<FileSystemEntry[]>((res, rej) => reader.readEntries(res, rej));
            if (!batch.length) break;
            for (const child of batch) await walk(child, `${prefix}${entry.name}/`);
        }
    }

    for (const entry of entries) await walk(entry, '');
    return clean(out);
}

export function itemsFromInput(files: FileList): UploadItem[] {
    return clean([...files].map((file) => ({ file, path: file.webkitRelativePath || file.name })));
}

function clean(items: UploadItem[]): UploadItem[] {
    return items.filter((i) => !i.path.split('/').some((part) => part.startsWith('.') || part === '__MACOSX'));
}

/** Drop a single wrapping folder ("dist/index.html" -> "index.html"), like the server does for zips. */
export function stripWrapper(items: UploadItem[]): UploadItem[] {
    const tops = new Set(items.map((i) => (i.path.includes('/') ? i.path.split('/')[0] : '')));
    if (tops.size !== 1 || tops.has('')) return items;
    const cut = [...tops][0].length + 1;
    return items.map((i) => ({ ...i, path: i.path.slice(cut) }));
}
