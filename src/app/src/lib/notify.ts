import { notifications } from '@mantine/notifications';
import type { NotificationData } from '@mantine/notifications';

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

function show(
  color: string,
  title: string,
  message?: string,
  options?: NotifyOptions,
) {
  notifications.show({
    color,
    title: message ? title : undefined,
    message: message ?? title,
    ...options,
  });
}

export const notify = {
  success: (title: string, message?: string, options?: NotifyOptions) =>
    show('teal', title, message, options),
  error: (title: string, message?: string, options?: NotifyOptions) =>
    show('red', title, message, options),
  info: (title: string, message?: string, options?: NotifyOptions) =>
    show('blue', title, message, options),
  warning: (title: string, message?: string, options?: NotifyOptions) =>
    show('yellow', title, message, options),
};