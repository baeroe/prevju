import { defineConfig } from 'vitepress';
import llmstxt from 'vitepress-plugin-llms';

export default defineConfig({
    title: 'prevju',
    description: 'Self-hosted previews for HTML drafts. Upload, send the link, optionally with a password.',
    lang: 'en',
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
    vite: { plugins: [llmstxt()] },
    themeConfig: {
        logo: { src: '/logo.svg', alt: '' },
        nav: [
            { text: 'Guide', link: '/setup' },
            { text: 'Releases', link: 'https://github.com/baeroe/prevju/releases' },
        ],
        sidebar: [
            {
                text: 'Guide',
                items: [
                    { text: 'Setup', link: '/setup' },
                    { text: 'Reverse proxy', link: '/reverse-proxy' },
                    { text: 'Uploading', link: '/uploading' },
                    { text: 'What works', link: '/what-works' },
                    { text: 'MCP', link: '/mcp' },
                    { text: 'Upgrading', link: '/upgrading' },
                ],
            },
        ],
        footer: { message: 'Released under the MIT License.', copyright: '© 2026 Rafael Haußmann' },
        socialLinks: [{ icon: 'github', link: 'https://github.com/baeroe/prevju' }],
        search: { provider: 'local' },
        editLink: { pattern: 'https://github.com/baeroe/prevju/edit/main/docs/:path', text: 'Edit this page' },
    },
});
