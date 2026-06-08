import {useParams} from "react-router";
import {useAdminCore} from "../admin/useAdminCore";
import {type Chapter, ChapterContext} from "./types";
import fields from "./fields";
import {useQuery} from "@tanstack/react-query";
import {Book} from "../Book/types";

export default function Chapter() {
  const {id, bookId} = useParams<{ id: string, bookId: string }>();
  const {EntityForm, createEntityFormDataProvider, apiCall} = useAdminCore();

  const isCreateMode = !id;

  const {data} = useQuery({
    queryKey: ["book", bookId],
    queryFn: ({signal}) => apiCall(
      "GET",
      `book/${bookId}`,
      {},
      {signal}
    )
  });

  return (
    <EntityForm<Chapter, ChapterContext>
      fields={fields}
      context={{
        book: data as Book | undefined
      }}
      dataProvider={createEntityFormDataProvider<Chapter>("chapter", id, isCreateMode)}
      webPath={`books/${bookId}/chapters`}
      preprocessValues={
        (values, context, isCreateMode) => {
          if (String(values.position) === "") {
            delete (values as { position: unknown }).position;
          }

          if (!isCreateMode) {
            return values;
          }

          return {
            ...values,
            bookId: parseInt(bookId || "0")
          };
        }
      }
      apiEndpoint="chapter"
      labels={{
        notFound: {
          text: "Глава не найдена",
          btnCaption: "Назад к списку"
        },
        submit: {
          create: "Добавить",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Глава добавлена",
          onUpdate: "Изменения сохранены"
        }
      }}
    />
  );
}
