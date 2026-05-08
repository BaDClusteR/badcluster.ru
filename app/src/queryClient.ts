import {QueryClient, QueryCache, MutationCache } from '@tanstack/react-query';
import { notify } from '@/lib/notify';
import { HttpError } from '@/utils/errors';

const globalErrorHandler = (error: any) => {
    if (error instanceof HttpError) {
        if (error.status === 503) {
            notify.error('Сервер недоступен. Попробуйте позже.');
            error.isHandled = true;
        }
    } else {
        error.status(`Случилось что-то странное: ${error.message}`);
    }
};

export const queryClient = new QueryClient({
    queryCache: new QueryCache({
        onError: globalErrorHandler,
    }),
    mutationCache: new MutationCache({
        onError: globalErrorHandler,
    }),
});
