import { defineConfig, type DefaultTheme } from 'vitepress';
import llmstxt from 'vitepress-plugin-llms';

const pages = (prefix: string, t: Record<string, string>): DefaultTheme.SidebarItem[] => [
    {
        text: t.guide,
        items: ['setup', 'reverse-proxy', 'uploading', 'projects', 'what-works', 'mcp', 'upgrading'].map((p) => ({ text: t[p], link: `${prefix}/${p}` })),
    },
];

const en = {
    guide: 'Guide',
    setup: 'Setup',
    'reverse-proxy': 'Reverse proxy',
    uploading: 'Uploading',
    projects: 'Projects',
    'what-works': 'What works',
    mcp: 'MCP',
    upgrading: 'Upgrading',
};

const de = {
    guide: 'Anleitung',
    setup: 'Einrichtung',
    'reverse-proxy': 'Reverse-Proxy',
    uploading: 'Hochladen',
    projects: 'Projekte',
    'what-works': 'Was funktioniert',
    mcp: 'MCP',
    upgrading: 'Aktualisieren',
};

export default defineConfig({
    title: 'prevju',
    cleanUrls: true,
    lastUpdated: true,
    // docs/history is the project's internal memory, not part of the site
    srcExclude: ['history/**', 'README.md'],
    head: [
        ['link', { rel: 'icon', href: '/favicon.svg', type: 'image/svg+xml' }],
        ['meta', { name: 'theme-color', content: '#f4f4f1' }],
        ['meta', { property: 'og:title', content: 'prevju' }],
        ['meta', { property: 'og:description', content: 'Self-hosted previews for HTML drafts.' }],
        ['meta', { property: 'og:image', content: 'https://prevju.dev/screenshots/sites.png' }],
    ],
    appearance: false,
    // agents get the English docs only, without the legal pages
    vite: { plugins: [llmstxt({ workDir: 'en', ignoreFiles: ['imprint.md', 'privacy.md'] })] },

    locales: {
        root: {
            label: 'English',
            lang: 'en',
            // English lives under /en like German under /de; old links without a prefix are redirected in the theme
            link: '/en/',
            description: 'Self-hosted previews for HTML drafts. Upload, send the link, optionally with a password.',
            themeConfig: {
                nav: [
                    { text: 'Guide', link: '/en/setup' },
                    { text: 'Releases', link: 'https://github.com/baeroe/prevju/releases' },
                ],
                sidebar: pages('/en', en),
                editLink: { pattern: 'https://github.com/baeroe/prevju/edit/main/docs/:path', text: 'Edit this page' },
                footer: {
                    message: 'Released under the MIT License. <a href="/en/imprint">Imprint</a> · <a href="/en/privacy">Privacy policy</a>',
                    copyright: '© 2026 Rafael Haußmann',
                },
            },
        },
        de: {
            label: 'Deutsch',
            lang: 'de',
            link: '/de/',
            description: 'Selbst gehostete Vorschauen für HTML-Entwürfe. Hochladen, Link schicken, optional mit Passwort.',
            themeConfig: {
                nav: [
                    { text: 'Anleitung', link: '/de/setup' },
                    { text: 'Releases', link: 'https://github.com/baeroe/prevju/releases' },
                ],
                sidebar: pages('/de', de),
                editLink: { pattern: 'https://github.com/baeroe/prevju/edit/main/docs/:path', text: 'Seite bearbeiten' },
                footer: {
                    message: 'Veröffentlicht unter der MIT-Lizenz. <a href="/de/imprint">Impressum</a> · <a href="/de/privacy">Datenschutz</a>',
                    copyright: '© 2026 Rafael Haußmann',
                },
                outline: { label: 'Auf dieser Seite' },
                docFooter: { prev: 'Zurück', next: 'Weiter' },
                lastUpdated: { text: 'Zuletzt aktualisiert' },
                langMenuLabel: 'Sprache wechseln',
                returnToTopLabel: 'Nach oben',
                sidebarMenuLabel: 'Menü',
                skipToContentLabel: 'Zum Inhalt springen',
                notFound: {
                    title: 'Seite nicht gefunden',
                    quote: 'Diese Seite gibt es nicht (mehr).',
                    linkLabel: 'Zur Startseite',
                    linkText: 'Zur Startseite',
                },
            },
        },
    },

    themeConfig: {
        logo: { src: '/logo.svg', alt: '' },
        socialLinks: [{ icon: 'github', link: 'https://github.com/baeroe/prevju' }],
        search: {
            provider: 'local',
            options: {
                locales: {
                    de: {
                        translations: {
                            button: { buttonText: 'Suchen', buttonAriaLabel: 'Suchen' },
                            modal: {
                                noResultsText: 'Keine Ergebnisse für',
                                resetButtonTitle: 'Suche zurücksetzen',
                                displayDetails: 'Details anzeigen',
                                footer: { selectText: 'auswählen', navigateText: 'navigieren', closeText: 'schließen' },
                            },
                        },
                    },
                },
            },
        },
    },
});
