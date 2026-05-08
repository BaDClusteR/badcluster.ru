export default function isDevMode(): boolean {
    return String(import.meta.env.VITE_DEV_MODE || '') !== '0';
}
