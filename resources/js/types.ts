export type SiteCard = {
    id: number;
    name: string;
    url: string;
    preview_url: string;
    has_password: boolean;
    file_count: number;
    has_html: boolean;
    updated_at: string;
};

export type SiteDetail = SiteCard & { files: string[] };
