import type { NotificationData } from '@mantine/notifications';
import { ReactNode } from "react";
/**
 * Thin typed wrapper around @mantine/notifications.
 *
 * Usage from anywhere in the app (no hook required):
 *
 *   import { notify } from '@/lib/notify';
 *   notify.success('Saved', 'Your changes are live');
 *   notify.error('Failed to load users');
 *
 * Works outside React too (fetch interceptors, event handlers, etc).
 */
type NotifyOptions = Omit<NotificationData, 'message' | 'title' | 'color'>;
export declare const notify: {
    success: (title: ReactNode, message?: ReactNode, options?: NotifyOptions) => void;
    error: (title: ReactNode, message?: ReactNode, options?: NotifyOptions) => void;
    info: (title: ReactNode, message?: ReactNode, options?: NotifyOptions) => void;
    warning: (title: ReactNode, message?: ReactNode, options?: NotifyOptions) => void;
};
export {};
