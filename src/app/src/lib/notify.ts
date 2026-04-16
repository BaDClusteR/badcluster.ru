import { notifications } from '@mantine/notifications';
import type { NotificationData } from '@mantine/notifications';
import {ReactNode} from "react";

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
  title: ReactNode,
  message?: ReactNode,
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
  success: (title: ReactNode, message?: ReactNode, options?: NotifyOptions) =>
    show('teal', title, message, options),
  error: (title: ReactNode, message?: ReactNode, options?: NotifyOptions) =>
    show('red', title, message, options),
  info: (title: ReactNode, message?: ReactNode, options?: NotifyOptions) =>
    show('blue', title, message, options),
  warning: (title: ReactNode, message?: ReactNode, options?: NotifyOptions) =>
    show('yellow', title, message, options),
};
