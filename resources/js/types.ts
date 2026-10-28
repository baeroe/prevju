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
} & VisitStats;

export type VisitStats = { view_count: number; last_viewed_at: string | null };

export type Visit = {
    id: number;
    kind: 'view' | 'unlock' | 'unlock_failed';
    site_id: number | null;
    project_id: number | null;
    path: string | null;
    visitor: string;
    device: 'desktop' | 'mobile' | 'tablet';
    browser: string | null;
    os: string | null;
    referrer: string | null;
    created_at: string;
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
} & VisitStats;

export type ProjectOption = { id: number; name: string };

/** A site on the client's project page; no preview where the viewer still needs the site's own password. */
export type PublicSite = Omit<SiteCard, 'preview_url' | keyof VisitStats> & { preview_url: string | null };
