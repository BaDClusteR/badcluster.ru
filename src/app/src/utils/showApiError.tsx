import {ApiErrorContext} from "@/utils/types.ts";
import {Optional} from "@/types.ts";
import {notify} from "@/lib/notify.ts";

export default function showApiError(payload: Optional<ApiErrorContext>, code?: number) {
    console.error('API Error', payload);
    const errCode = code ?? payload?.errors?.[0]?.code;
    const errMessage = payload?.errors?.[0]?.message;
    let place = payload?.file
        ? `${payload.file}:${payload.line}`
        : null;

    if (place) {
      let placePieces = place.split('/src/');
      placePieces.shift();
      place = `src/${placePieces.join('/src/')}`;
    }

    const requestId = payload?.requestId
        ? <>
          <strong>Request ID: </strong>
          {payload.requestId}
        </> : null;

    const getErrorTitle = () => {
        let title = "Неизвестная ошибка";
        if (errCode && !errMessage) {
            title += ` ${errCode}`;
        } else if (errCode && errMessage) {
            title = `Ошибка ${errCode}`;
        }

        return title;
    }

    const paragraphs = [
        errMessage,
        place,
        requestId
    ].filter(Boolean);

    return notify.error(
        getErrorTitle(),
        <>
          {
            paragraphs.map(
              (paragraph, i) => <p
                key={`paragraph-${i}`}
                style={{wordBreak: 'break-word'}}
              >
                {paragraph}
              </p>
            )
          }
        </>,
        {
            autoClose: false,
            withCloseButton: true
        }
    )
}
