import { inBrowser } from 'vitepress';
import DefaultTheme from 'vitepress/theme';
import '@fontsource/instrument-sans/400.css';
import '@fontsource/instrument-sans/500.css';
import '@fontsource/instrument-sans/600.css';
import '@fontsource/jetbrains-mono/400.css';
import './style.css';

// English moved under /en: old links (/, /setup) land there. Runs on GitHub Pages' 404 page too.
if (inBrowser && !/^\/(en|de)(\/|$)/.test(location.pathname)) {
    location.replace(`/en${location.pathname}${location.search}${location.hash}`);
}

export default DefaultTheme;
