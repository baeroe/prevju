export type SiteCard = {
    id: number;
    name: string;
    url: string;
    preview_url: string;
    has_password: boolean;
    project_id: number | null;
    file_count: number;
    has_html: boolean;
    updated_at: string;
};

export type SiteDetail = SiteCard & { files: string[] };

export type ProjectCard = {
    id: number;
    name: string;
    url: string;
    has_password: boolean;
    site_count: number;
    updated_at: string;
    sites: SiteCard[];
};

export type ProjectOption = { id: number; name: string };

/** A site on the client's project page; no preview where the viewer still needs the site's own password. */
export type PublicSite = Omit<SiteCard, 'preview_url'> & { preview_url: string | null };
