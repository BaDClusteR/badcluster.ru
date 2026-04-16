import {QueryClient, QueryCache, MutationCache } from '@tanstack/react-query';
import { notify } from '@/lib/notify';
import { HttpError } from '@/utils/errors';

const globalErrorHandler = (error: any) => {
    // Проверяем, наша ли это ошибка
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
    // Настраиваем кэш для useQuery (GET)
    queryCache: new QueryCache({
        onError: globalErrorHandler,
    }),
    // Настраиваем кэш для useMutation (POST/PUT/DELETE)
    mutationCache: new MutationCache({
        onError: globalErrorHandler,
    }),
});
